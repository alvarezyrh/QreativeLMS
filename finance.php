<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';
require_once __DIR__ . '/app/settings.php';

$user = require_auth(database());
$connection = database();
$isAdmin = $user['role'] === 'admin';
$isStudent = $user['role'] === 'murid';
if (!$isAdmin && !$isStudent) {
    http_response_code(403);
    exit('Halaman keuangan hanya tersedia untuk admin dan murid.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    try {
        $action = post_value('action', 32);
        if ($action === 'submit_reference' && $isStudent) {
            $transactionId = post_id('transaction_id');
            $reference = post_value('reference', 160);
            if ($reference === '') {
                throw new InvalidArgumentException('Masukkan nomor referensi atau catatan transfer.');
            }
            $statement = $connection->prepare(
                "UPDATE finance_transactions SET reference = :reference
                 WHERE id = :id AND user_id = :user_id AND status = 'pending' AND kind = 'income'"
            );
            $statement->execute(['reference' => $reference, 'id' => $transactionId, 'user_id' => $user['id']]);
            if ($statement->rowCount() === 0) {
                throw new InvalidArgumentException('Transaksi pending tidak ditemukan.');
            }
            set_flash('Referensi pembayaran dikirim. Tunggu verifikasi admin.');
        } elseif ($action === 'record_transaction' && $isAdmin) {
            $kind = post_value('kind', 16);
            $method = post_value('method', 24);
            $status = post_value('status', 16);
            $amount = filter_var($_POST['amount_idr'] ?? null, FILTER_VALIDATE_INT);
            $notes = post_value('notes', 1000);
            if (!in_array($kind, ['income', 'expense'], true) || !in_array($status, ['paid', 'pending'], true)) {
                throw new InvalidArgumentException('Jenis atau status transaksi tidak valid.');
            }
            if ($kind === 'expense' && $status === 'pending') {
                throw new InvalidArgumentException('Pengeluaran hanya dapat dicatat sebagai transaksi yang sudah dibayar.');
            }
            if (!in_array($method, ['qris_bca', 'bank_transfer', 'cash', 'other'], true)) {
                throw new InvalidArgumentException('Metode pembayaran tidak valid.');
            }
            if ($amount === false || $amount < 1 || $amount > 100000000000) {
                throw new InvalidArgumentException('Nominal harus diisi dalam rupiah.');
            }

            $studentId = null;
            $courseId = null;
            if ($kind === 'income') {
                $studentId = post_id('user_id');
                $student = $connection->prepare("SELECT id FROM users WHERE id = :id AND role = 'murid' AND status = 'active'");
                $student->execute(['id' => $studentId]);
                if ($student->fetchColumn() === false) {
                    throw new InvalidArgumentException('Pilih akun murid aktif.');
                }
                if (isset($_POST['course_id']) && $_POST['course_id'] !== '') {
                    $courseId = post_id('course_id');
                    $course = $connection->prepare("SELECT id FROM courses WHERE id = :id AND status = 'published'");
                    $course->execute(['id' => $courseId]);
                    if ($course->fetchColumn() === false) {
                        throw new InvalidArgumentException('Pilih kursus terbit yang valid.');
                    }
                }
            }

            $createdAt = post_value('created_at', 10);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $createdAt);
            if ($date === false || $date->format('Y-m-d') !== $createdAt) {
                throw new InvalidArgumentException('Tanggal transaksi tidak valid.');
            }
            $statement = $connection->prepare(
                'INSERT INTO finance_transactions
                    (kind, user_id, course_id, amount_idr, method, status, notes, created_by, reviewed_by, created_at, paid_at, reviewed_at)
                 VALUES
                    (:kind, :user_id, :course_id, :amount_idr, :method, :status, :notes, :created_by, :reviewed_by, :created_at, :paid_at, :reviewed_at)'
            );
            $statement->execute([
                'kind' => $kind,
                'user_id' => $studentId,
                'course_id' => $courseId,
                'amount_idr' => $amount,
                'method' => $method,
                'status' => $status,
                'notes' => $notes,
                'created_by' => $user['id'],
                'reviewed_by' => $status === 'paid' ? $user['id'] : null,
                'created_at' => $createdAt . ' 12:00:00',
                'paid_at' => $status === 'paid' ? $createdAt . ' 12:00:00' : null,
                'reviewed_at' => $status === 'paid' ? $createdAt . ' 12:00:00' : null,
            ]);
            if ($kind === 'income' && $status === 'paid' && $courseId !== null) {
                $enroll = $connection->prepare('INSERT OR IGNORE INTO enrollments (course_id, student_id) VALUES (:course_id, :student_id)');
                $enroll->execute(['course_id' => $courseId, 'student_id' => $studentId]);
            }
            set_flash('Transaksi berhasil dicatat.');
        } elseif ($action === 'review_payment' && $isAdmin) {
            $transactionId = post_id('transaction_id');
            $decision = post_value('decision', 16);
            if (!in_array($decision, ['paid', 'rejected'], true)) {
                throw new InvalidArgumentException('Keputusan pembayaran tidak valid.');
            }
            $connection->beginTransaction();
            if ($decision === 'paid') {
                $linkedCourse = $connection->prepare(
                    "SELECT c.status FROM finance_transactions t
                     JOIN courses c ON c.id = t.course_id
                     WHERE t.id = :id AND t.kind = 'income' AND t.status = 'pending'"
                );
                $linkedCourse->execute(['id' => $transactionId]);
                $courseStatus = $linkedCourse->fetchColumn();
                if ($courseStatus !== false && $courseStatus !== 'published') {
                    throw new InvalidArgumentException('Kursus terkait belum terbit. Terbitkan kursus sebelum menyetujui pembayaran.');
                }
            }
            $statement = $connection->prepare(
                "UPDATE finance_transactions
                 SET status = :status, reviewed_by = :reviewed_by,
                     reviewed_at = CURRENT_TIMESTAMP,
                     paid_at = CASE WHEN :paid_status = 'paid' THEN CURRENT_TIMESTAMP ELSE NULL END
                 WHERE id = :id AND kind = 'income' AND status = 'pending'"
            );
            $statement->execute([
                'status' => $decision,
                'paid_status' => $decision,
                'reviewed_by' => $user['id'],
                'id' => $transactionId,
            ]);
            if ($statement->rowCount() === 0) {
                $connection->rollBack();
                throw new InvalidArgumentException('Pembayaran pending tidak ditemukan atau sudah ditinjau.');
            }
            if ($decision === 'paid') {
                $payment = $connection->prepare(
                    'SELECT user_id, course_id FROM finance_transactions WHERE id = :id'
                );
                $payment->execute(['id' => $transactionId]);
                $details = $payment->fetch();
                if ($details['user_id'] !== null && $details['course_id'] !== null) {
                    $course = $connection->prepare("SELECT 1 FROM courses WHERE id = :id AND status = 'published'");
                    $course->execute(['id' => $details['course_id']]);
                    if ($course->fetchColumn() !== false) {
                        $enroll = $connection->prepare(
                            'INSERT OR IGNORE INTO enrollments (course_id, student_id) VALUES (:course_id, :student_id)'
                        );
                        $enroll->execute(['course_id' => $details['course_id'], 'student_id' => $details['user_id']]);
                    }
                }
            }
            $connection->commit();
            set_flash($decision === 'paid'
                ? 'Pembayaran disetujui; pendaftaran kursus murid sudah diperbarui.'
                : 'Pembayaran ditolak. Murid dapat mengirim permintaan pembayaran baru.');
        } else {
            http_response_code(403);
            exit('Aksi keuangan tidak tersedia untuk role ini.');
        }
        redirect('/finance.php');
    } catch (InvalidArgumentException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        set_flash($exception->getMessage(), 'error');
        redirect('/finance.php');
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}

$flash = take_flash();
$csrfToken = csrf_token();
$settings = app_settings($connection);
$pageTitle = $isAdmin ? 'Manajemen keuangan' : 'Pembayaran saya';
$pageDescription = $isAdmin
    ? 'Catat pendapatan dan pengeluaran serta verifikasi pembayaran murid.'
    : 'Lihat status pembayaran kursus dan instruksi pembayaran QRIS BCA.';

if ($isAdmin) {
    $summaryQuery = $connection->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN kind = 'income' AND status = 'paid' AND substr(paid_at, 1, 7) = :period THEN amount_idr ELSE 0 END), 0) AS monthly_income,
            COALESCE(SUM(CASE WHEN kind = 'income' AND status = 'paid' THEN amount_idr ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN kind = 'expense' AND status = 'paid' THEN amount_idr ELSE 0 END), 0) AS total_expense,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count
         FROM finance_transactions"
    );
    $summaryQuery->execute(['period' => date('Y-m')]);
    $summary = $summaryQuery->fetch();
    $transactions = $connection->query(
        "SELECT t.*, u.name AS student_name, u.email AS student_email, c.title AS course_title, reviewer.name AS reviewer_name
         FROM finance_transactions t
         LEFT JOIN users u ON u.id = t.user_id
         LEFT JOIN courses c ON c.id = t.course_id
         LEFT JOIN users reviewer ON reviewer.id = t.reviewed_by
         ORDER BY CASE WHEN t.status = 'pending' THEN 0 ELSE 1 END, t.created_at DESC, t.id DESC
         LIMIT 200"
    )->fetchAll();
    $students = $connection->query("SELECT id, name, email FROM users WHERE role = 'murid' AND status = 'active' ORDER BY name")->fetchAll();
    $courses = $connection->query("SELECT id, title, price_idr FROM courses WHERE status = 'published' ORDER BY title")->fetchAll();
} else {
    $statement = $connection->prepare(
        "SELECT t.*, c.title AS course_title
         FROM finance_transactions t
         LEFT JOIN courses c ON c.id = t.course_id
         WHERE t.user_id = :user_id
         ORDER BY t.created_at DESC, t.id DESC"
    );
    $statement->execute(['user_id' => $user['id']]);
    $transactions = $statement->fetchAll();
}

require __DIR__ . '/views/finance.php';
