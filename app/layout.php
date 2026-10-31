<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

/** Ikon garis sederhana yang dipakai bersama oleh sidebar dan dashboard. */
function icon(string $name, int $size = 17): string
{
    static $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.8"/><rect x="14" y="3" width="7" height="7" rx="1.8"/><rect x="3" y="14" width="7" height="7" rx="1.8"/><rect x="14" y="14" width="7" height="7" rx="1.8"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5ZM4 17a2 2 0 0 1 2-2h14M8 7h8M8 10h6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/>',
        'chart' => '<path d="M4 19V5M4 19h17M8 15l4-4 3 2 5-6"/>',
        'wallet' => '<path d="M19 7V5.5A1.5 1.5 0 0 0 17.5 4h-12A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V15M3 6.5A2.5 2.5 0 0 0 5.5 9H20v6h-4a3 3 0 0 1 0-6"/>',
        'message' => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'people' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="m8.2 13-1.1 8 4.9-2.5 4.9 2.5-1.1-8"/>',
    ];
    $path = $paths[$name] ?? $paths['grid'];

    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function role_label(string $role): string
{
    return ['admin' => 'Admin', 'guru' => 'Guru', 'murid' => 'Murid'][$role] ?? ucfirst($role);
}

/**
 * Menu utama per role. Satu sumber untuk semua halaman, sehingga sidebar
 * selalu sama baik dibuka dari dashboard maupun langsung dari URL.
 *
 * @return list<array{label: string, icon: string, href: string}>
 */
function app_navigation(string $role): array
{
    return match ($role) {
        'admin' => [
            ['label' => 'Dashboard', 'icon' => 'grid', 'href' => '/'],
            ['label' => 'Kursus', 'icon' => 'book', 'href' => '/courses.php'],
            ['label' => 'Pengguna', 'icon' => 'users', 'href' => '/users.php'],
            ['label' => 'Jadwal', 'icon' => 'calendar', 'href' => '/schedule.php'],
            ['label' => 'Pesan', 'icon' => 'message', 'href' => '/messages.php'],
            ['label' => 'Keuangan', 'icon' => 'wallet', 'href' => '/finance.php'],
            ['label' => 'Laporan', 'icon' => 'chart', 'href' => '/reports.php'],
            ['label' => 'Pengaturan', 'icon' => 'settings', 'href' => '/settings.php'],
        ],
        'guru' => [
            ['label' => 'Dashboard', 'icon' => 'grid', 'href' => '/'],
            ['label' => 'Kelas saya', 'icon' => 'book', 'href' => '/courses.php'],
            ['label' => 'Jadwal mengajar', 'icon' => 'calendar', 'href' => '/schedule.php'],
            ['label' => 'Pesan', 'icon' => 'message', 'href' => '/messages.php'],
        ],
        default => [
            ['label' => 'Dashboard', 'icon' => 'grid', 'href' => '/'],
            ['label' => 'Kursus saya', 'icon' => 'book', 'href' => '/courses.php'],
            ['label' => 'Jadwal', 'icon' => 'calendar', 'href' => '/schedule.php'],
            ['label' => 'Pesan', 'icon' => 'message', 'href' => '/messages.php'],
            ['label' => 'Pembayaran', 'icon' => 'wallet', 'href' => '/finance.php'],
        ],
    };
}

function current_nav_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    return match ($path) {
        '/index.php' => '/',
        '/course.php' => '/courses.php',
        default => $path,
    };
}

function unread_message_count(PDO $connection, int $userId): int
{
    $statement = $connection->prepare('SELECT COUNT(*) FROM messages WHERE recipient_id = :user_id AND read_at IS NULL');
    $statement->execute(['user_id' => $userId]);

    return (int) $statement->fetchColumn();
}

function name_initials(string $name): string
{
    preg_match_all('/(?:^|\s)(\p{L})/u', $name, $matches);

    return text_upper(implode('', array_slice($matches[1] ?? [], 0, 2)));
}

// Helper teks yang tetap berjalan walau ekstensi mbstring tidak terpasang.
function text_upper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
}

function text_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function text_excerpt(string $value, int $length): string
{
    if (preg_match('/^.{0,' . $length . '}/us', $value, $match) !== 1 || $match[0] === $value) {
        return $value;
    }

    return rtrim($match[0]) . '…';
}

/** Format tanggal berbahasa Indonesia tanpa bergantung pada ekstensi intl. */
function indonesian_date(int $timestamp, bool $withDay = false, bool $shortMonth = false): string
{
    $months = $shortMonth
        ? ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
        : ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $date = date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp) - 1] . ($shortMonth ? '' : ' ' . date('Y', $timestamp));

    return $withDay ? $days[(int) date('w', $timestamp)] . ', ' . $date : $date;
}
