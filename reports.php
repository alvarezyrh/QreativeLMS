<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/lms.php';
require_once __DIR__ . '/app/security.php';

$user = require_auth(database());
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Laporan hanya tersedia untuk admin.');
}

$period = $_GET['period'] ?? date('Y-m');
if (!is_string($period) || preg_match('/\A\d{4}-(0[1-9]|1[0-2])\z/', $period) !== 1) {
    http_response_code(400);
    exit('Periode laporan harus menggunakan format YYYY-MM.');
}

function csv_safe_cell(mixed $value): string
{
    $cell = (string) $value;

    return preg_match('/\A[\t\r ]*[=+\-@]/', $cell) === 1 ? "'" . $cell : $cell;
}

$connection = database();
$summaryStatement = $connection->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN kind = 'income' AND status = 'paid' THEN amount_idr ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN kind = 'expense' AND status = 'paid' THEN amount_idr ELSE 0 END), 0) AS expense,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending
     FROM finance_transactions
     WHERE (status = 'paid' AND substr(paid_at, 1, 7) = :period)
        OR (status != 'paid' AND substr(created_at, 1, 7) = :period)"
);
$summaryStatement->execute(['period' => $period]);
$summary = $summaryStatement->fetch();
$summary['net'] = (int) $summary['income'] - (int) $summary['expense'];

$courseStatement = $connection->prepare(
    "SELECT c.id, c.title, c.level,
        (SELECT COUNT(DISTINCT e.student_id) FROM enrollments e WHERE e.course_id = c.id AND substr(e.enrolled_at, 1, 7) = :enrollment_period) AS students,
        (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lessons,
        (SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id WHERE l.course_id = c.id AND substr(lp.completed_at, 1, 7) = :progress_period) AS completed_lessons,
        (SELECT COALESCE(SUM(t.amount_idr), 0) FROM finance_transactions t
         WHERE t.course_id = c.id AND t.kind = 'income' AND t.status = 'paid' AND substr(t.paid_at, 1, 7) = :period) AS income
     FROM courses c
     ORDER BY students DESC, c.title"
);
$courseStatement->execute(['period' => $period, 'enrollment_period' => $period, 'progress_period' => $period]);
$courseReports = $courseStatement->fetchAll();

$activityStatement = $connection->prepare(
    "SELECT CASE WHEN status = 'paid' THEN substr(paid_at, 1, 10) ELSE substr(created_at, 1, 10) END AS date,
        COALESCE(SUM(CASE WHEN kind = 'income' AND status = 'paid' THEN amount_idr ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN kind = 'expense' AND status = 'paid' THEN amount_idr ELSE 0 END), 0) AS expense,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
     FROM finance_transactions
     WHERE (status = 'paid' AND substr(paid_at, 1, 7) = :period)
        OR (status != 'paid' AND substr(created_at, 1, 7) = :period)
     GROUP BY CASE WHEN status = 'paid' THEN substr(paid_at, 1, 10) ELSE substr(created_at, 1, 10) END
     ORDER BY date DESC"
);
$activityStatement->execute(['period' => $period]);
$dailyReports = $activityStatement->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="qreative-laporan-' . $period . '.csv"');
    header('Cache-Control: no-store');
    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('File laporan CSV tidak dapat dibuat.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Tanggal dicatat', 'Tanggal dibayar', 'Jenis', 'Nama', 'Kursus', 'Nominal IDR', 'Metode', 'Status', 'Referensi']);
    $export = $connection->prepare(
        "SELECT t.created_at, t.paid_at, t.kind, u.name, c.title, t.amount_idr, t.method, t.status, t.reference
         FROM finance_transactions t
         LEFT JOIN users u ON u.id = t.user_id
         LEFT JOIN courses c ON c.id = t.course_id
         WHERE (t.status = 'paid' AND substr(t.paid_at, 1, 7) = :period)
            OR (t.status != 'paid' AND substr(t.created_at, 1, 7) = :period)
         ORDER BY t.created_at, t.id"
    );
    $export->execute(['period' => $period]);
    while ($row = $export->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, array_map('csv_safe_cell', $row));
    }
    fclose($output);
    exit;
}

$csrfToken = csrf_token();
$flash = take_flash();
$pageTitle = 'Laporan platform';
$pageDescription = 'Ringkasan transaksi, aktivitas harian, dan performa kursus.';

require __DIR__ . '/views/reports.php';
