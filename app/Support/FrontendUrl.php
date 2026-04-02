<?php

namespace App\Support;

/**
 * Absolute URLs to the Nuxt frontend (never use Laravel url() with APP_URL for these).
 */
final class FrontendUrl
{
    public static function base(): string
    {
        return rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
    }

    /**
     * Login page, optionally with a safe in-app redirect after authentication.
     */
    public static function login(?string $redirectPath = null): string
    {
        $login = self::base().'/login';
        $path = self::sanitizeRedirectPath($redirectPath ?? '');

        return $path === '' ? $login : $login.'?redirect='.rawurlencode($path);
    }

    public static function resetPassword(string $token, string $email): string
    {
        return self::base().'/reset-password?token='.rawurlencode($token).'&email='.rawurlencode($email);
    }

    /**
     * Only same-origin app paths (leading slash, no scheme / open-redirect).
     */
    public static function sanitizeRedirectPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '';
        }
        if (str_contains($path, '://') || str_contains($path, "\0")) {
            return '';
        }

        return $path;
    }
}
