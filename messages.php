<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';

$user = require_auth(database());
$connection = database();

$conversationForUser = static function (int $conversationId) use ($connection, $user): array {
    $statement = $connection->prepare(
        'SELECT c.id, c.participant_one_id, c.participant_two_id, c.course_id,
            c.updated_at, course.title AS course_title, course.teacher_id AS course_teacher_id,
            one_user.role AS one_role, two_user.role AS two_role,
            CASE WHEN c.participant_one_id = :user_id THEN c.participant_two_id ELSE c.participant_one_id END AS other_id,
            other_user.name AS other_name, other_user.role AS other_role
         FROM conversations c
         JOIN users one_user ON one_user.id = c.participant_one_id
         JOIN users two_user ON two_user.id = c.participant_two_id
         JOIN users other_user ON other_user.id = CASE
            WHEN c.participant_one_id = :user_id THEN c.participant_two_id ELSE c.participant_one_id END
         LEFT JOIN courses course ON course.id = c.course_id
         WHERE c.id = :conversation_id
           AND (c.participant_one_id = :user_id OR c.participant_two_id = :user_id)'
    );
    $statement->execute(['user_id' => $user['id'], 'conversation_id' => $conversationId]);
    $conversation = $statement->fetch();
    if ($conversation === false) {
        http_response_code(404);
        exit('Percakapan tidak ditemukan.');
    }

    $roles = [$conversation['one_role'], $conversation['two_role']];
    sort($roles);
    $validDirectPair = $conversation['course_id'] === null && $roles === ['admin', 'guru'];
    $validCoursePair = $conversation['course_id'] !== null
        && $roles === ['guru', 'murid']
        && (int) $conversation['course_teacher_id'] === (int) (
            $conversation['one_role'] === 'guru' ? $conversation['participant_one_id'] : $conversation['participant_two_id']
        );
    if (!$validDirectPair && !$validCoursePair) {
        http_response_code(404);
        exit('Percakapan tidak ditemukan.');
    }

    return $conversation;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    try {
        $action = post_value('action', 24);
        if ($action === 'start_conversation') {
            $contactInput = post_value('contact', 32);
            if (preg_match('/\A([1-9][0-9]*):([0-9]+)\z/', $contactInput, $contactMatches) !== 1) {
                throw new InvalidArgumentException('Pilih kontak percakapan yang valid.');
            }
            $contactId = (int) $contactMatches[1];
            $courseId = (int) $contactMatches[2];
            $contact = $connection->prepare('SELECT id, role FROM users WHERE id = :id AND status = :status');
            $contact->execute(['id' => $contactId, 'status' => 'active']);
            $contactUser = $contact->fetch();
            if ($contactUser === false || $contactId === (int) $user['id']) {
                throw new InvalidArgumentException('Kontak tidak tersedia.');
            }

            $isDirectAdminTeacher = $courseId === 0
                && (($user['role'] === 'admin' && $contactUser['role'] === 'guru')
                    || ($user['role'] === 'guru' && $contactUser['role'] === 'admin'));
            $isCourseStudentTeacher = false;
            if ($courseId > 0 && (
                ($user['role'] === 'murid' && $contactUser['role'] === 'guru')
                || ($user['role'] === 'guru' && $contactUser['role'] === 'murid')
            )) {
                $studentId = $user['role'] === 'murid' ? (int) $user['id'] : $contactId;
                $teacherId = $user['role'] === 'guru' ? (int) $user['id'] : $contactId;
                $course = $connection->prepare(
                    "SELECT 1 FROM courses c
                     JOIN enrollments e ON e.course_id = c.id
                     WHERE c.id = :course_id AND c.teacher_id = :teacher_id
                       AND c.status = 'published' AND e.student_id = :student_id"
                );
                $course->execute([
                    'course_id' => $courseId,
                    'teacher_id' => $teacherId,
                    'student_id' => $studentId,
                ]);
                $isCourseStudentTeacher = $course->fetchColumn() !== false;
            }
            if (!$isDirectAdminTeacher && !$isCourseStudentTeacher) {
                throw new InvalidArgumentException('Pesan hanya dapat dikirim antara murid dan guru pada kursus yang diikuti, atau antara guru dan admin.');
            }

            $participantOne = min((int) $user['id'], $contactId);
            $participantTwo = max((int) $user['id'], $contactId);
            $insert = $connection->prepare(
                'INSERT OR IGNORE INTO conversations (participant_one_id, participant_two_id, course_id)
                 VALUES (:participant_one, :participant_two, :course_id)'
            );
            $insert->execute([
                'participant_one' => $participantOne,
                'participant_two' => $participantTwo,
                'course_id' => $courseId > 0 ? $courseId : null,
            ]);
            $find = $connection->prepare(
                'SELECT id FROM conversations
                 WHERE participant_one_id = :participant_one AND participant_two_id = :participant_two
                   AND course_id IS :course_id'
            );
            $find->execute([
                'participant_one' => $participantOne,
                'participant_two' => $participantTwo,
                'course_id' => $courseId > 0 ? $courseId : null,
            ]);
            $conversationId = (int) $find->fetchColumn();
            redirect('/messages.php?conversation=' . $conversationId);
        }

        if ($action === 'send_message') {
            $conversationId = post_id('conversation_id');
            $conversation = $conversationForUser($conversationId);
            $body = post_value('body', 4000);
            if ($body === '' || strlen($body) > 4000) {
                throw new InvalidArgumentException('Pesan wajib diisi dan maksimal 4.000 karakter.');
            }
            $insert = $connection->prepare(
                'INSERT INTO messages (conversation_id, sender_id, recipient_id, body)
                 VALUES (:conversation_id, :sender_id, :recipient_id, :body)'
            );
            $insert->execute([
                'conversation_id' => $conversationId,
                'sender_id' => $user['id'],
                'recipient_id' => $conversation['other_id'],
                'body' => $body,
            ]);
            $update = $connection->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute(['id' => $conversationId]);
            redirect('/messages.php?conversation=' . $conversationId);
        }

        http_response_code(400);
        exit('Aksi pesan tidak dikenal.');
    } catch (InvalidArgumentException $exception) {
        set_flash($exception->getMessage(), 'error');
        redirect('/messages.php');
    }
}

$contactStatement = match ($user['role']) {
    'murid' => $connection->prepare(
        "SELECT DISTINCT teacher.id AS contact_id, teacher.name AS contact_name,
            teacher.email AS contact_email, c.id AS course_id, c.title AS course_title, teacher.role AS contact_role
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id AND c.status = 'published'
         JOIN users teacher ON teacher.id = c.teacher_id AND teacher.role = 'guru' AND teacher.status = 'active'
         WHERE e.student_id = :user_id
         ORDER BY teacher.name, c.title"
    ),
    'guru' => $connection->prepare(
        "SELECT DISTINCT contact_id, contact_name, contact_email, course_id, course_title, contact_role
         FROM (
            SELECT student.id AS contact_id, student.name AS contact_name, student.email AS contact_email,
                c.id AS course_id, c.title AS course_title, student.role AS contact_role
            FROM courses c
            JOIN enrollments e ON e.course_id = c.id
            JOIN users student ON student.id = e.student_id AND student.status = 'active'
            WHERE c.teacher_id = :user_id AND c.status = 'published'
            UNION ALL
            SELECT admin.id, admin.name, admin.email, 0, '', admin.role
            FROM users admin WHERE admin.role = 'admin' AND admin.status = 'active'
         )
         ORDER BY contact_role, contact_name, course_title"
    ),
    default => $connection->prepare(
        "SELECT id AS contact_id, name AS contact_name, email AS contact_email,
            0 AS course_id, '' AS course_title, role AS contact_role
         FROM users WHERE role = 'guru' AND status = 'active'
         ORDER BY name"
    ),
};
if ($user['role'] !== 'admin') {
    $contactStatement->execute(['user_id' => $user['id']]);
} else {
    $contactStatement->execute();
}
$contacts = $contactStatement->fetchAll();

$conversationQuery = $connection->prepare(
    'SELECT c.id, c.course_id, c.updated_at, other_user.name AS other_name,
        other_user.role AS other_role, course.title AS course_title,
        (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
        (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_at,
        (SELECT COUNT(*) FROM messages unread
         WHERE unread.conversation_id = c.id AND unread.recipient_id = :unread_user_id AND unread.read_at IS NULL) AS unread_count
     FROM conversations c
     JOIN users other_user ON other_user.id = CASE
        WHEN c.participant_one_id = :user_id THEN c.participant_two_id ELSE c.participant_one_id END
     LEFT JOIN courses course ON course.id = c.course_id
     WHERE c.participant_one_id = :user_id OR c.participant_two_id = :user_id
     ORDER BY COALESCE(last_message_at, c.updated_at) DESC, c.id DESC'
);
$conversationQuery->execute(['user_id' => $user['id'], 'unread_user_id' => $user['id']]);
$conversations = $conversationQuery->fetchAll();

$selectedConversation = null;
$messages = [];
$selectedId = $_GET['conversation'] ?? null;
if ($selectedId !== null) {
    if (!is_string($selectedId) || filter_var($selectedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        http_response_code(400);
        exit('ID percakapan tidak valid.');
    }
    $selectedConversation = $conversationForUser((int) $selectedId);
    $markRead = $connection->prepare(
        'UPDATE messages SET read_at = CURRENT_TIMESTAMP
         WHERE conversation_id = :conversation_id AND recipient_id = :user_id AND read_at IS NULL'
    );
    $markRead->execute(['conversation_id' => $selectedConversation['id'], 'user_id' => $user['id']]);
    $messageQuery = $connection->prepare(
        'SELECT m.id, m.sender_id, m.recipient_id, m.body, m.created_at, m.read_at, sender.name AS sender_name
         FROM messages m JOIN users sender ON sender.id = m.sender_id
         WHERE m.conversation_id = :conversation_id ORDER BY m.id'
    );
    $messageQuery->execute(['conversation_id' => $selectedConversation['id']]);
    $messages = $messageQuery->fetchAll();
    foreach ($conversations as &$conversationItem) {
        if ((int) $conversationItem['id'] === (int) $selectedConversation['id']) {
            $conversationItem['unread_count'] = 0;
        }
    }
    unset($conversationItem);
}

$csrfToken = csrf_token();
$flash = take_flash();
$pageTitle = 'Pesan';
$pageDescription = match ($user['role']) {
    'murid' => 'Hubungi guru dari kursus yang sedang kamu ikuti.',
    'guru' => 'Berkomunikasi dengan murid di kelasmu dan admin platform.',
    default => 'Berkomunikasi langsung dengan guru di platform.',
};

require __DIR__ . '/views/messages.php';
