<?php

declare(strict_types=1);

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(mixed $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function current_user(PDO $connection): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT id, name, email, role FROM users WHERE id = :id AND status = :status LIMIT 1'
    );
    $statement->execute(['id' => (int) $userId, 'status' => 'active']);
    $user = $statement->fetch();

    return $user ?: null;
}

function require_auth(PDO $connection): array
{
    $user = current_user($connection);
    if ($user === null) {
        unset($_SESSION['user_id']);
        header('Location: /login.php');
        exit;
    }

    return $user;
}

function require_role(array $user, string ...$allowedRoles): void
{
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Akses ditolak.';
        exit;
    }
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}
