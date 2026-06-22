<?php
/**
 * Auth - Session management and role-based access control
 */

namespace App\Core;

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['logged_in']  = true;
        $_SESSION['login_time'] = time();
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::isLoggedIn()) return null;
        return [
            'id'    => $_SESSION['user_id'],
            'role'  => $_SESSION['user_role'],
            'email' => $_SESSION['user_email'],
            'name'  => $_SESSION['user_name'],
        ];
    }

    public static function id(): ?int
    {
        return self::isLoggedIn() ? (int)$_SESSION['user_id'] : null;
    }

    public static function role(): ?string
    {
        return self::isLoggedIn() ? $_SESSION['user_role'] : null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isReviewer(): bool
    {
        return self::role() === 'reviewer';
    }

    public static function isAuthor(): bool
    {
        return self::role() === 'author';
    }

    // Redirect to login if not authenticated
    public static function require(string $role = ''): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        if ($role && self::role() !== $role && self::role() !== 'admin') {
            header('Location: ' . APP_URL . '/login.php?error=unauthorized');
            exit;
        }
    }

    // Redirect already-logged-in users away from login/register
    public static function redirectIfLoggedIn(): void
    {
        if (self::isLoggedIn()) {
            header('Location: ' . self::dashboardUrl());
            exit;
        }
    }

    public static function dashboardUrl(): string
    {
        return match (self::role()) {
            'admin'    => APP_URL . '/admin/dashboard.php',
            'reviewer' => APP_URL . '/reviewer/dashboard.php',
            default    => APP_URL . '/author/dashboard.php',
        };
    }

    public static function checkSessionTimeout(): void
    {
        self::startSession();
        if (self::isLoggedIn()) {
            if (time() - ($_SESSION['login_time'] ?? 0) > SESSION_LIFETIME) {
                self::logout();
                header('Location: ' . APP_URL . '/login.php?error=timeout');
                exit;
            }
            // Refresh timeout on activity
            $_SESSION['login_time'] = time();
        }
    }

    // CSRF token helpers
    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        self::startSession();
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
