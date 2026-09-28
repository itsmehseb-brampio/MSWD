<?php

namespace App\Support;

/**
 * Reserved transport for the Gmail REST API with OAuth2.
 *
 * WHY THIS EXISTS
 * Sending notification email does not need the REST API - Gmail SMTP with an
 * App Password is simpler and already working in this project. The REST API
 * is only worth wiring up if the system later needs to read, thread or label
 * mail, or send as a delegated user.
 *
 * This class is the documented seam for that work. It validates the OAuth
 * settings so the Backups/Settings page can report readiness, and it refuses
 * to pretend it can send until the API client is actually installed.
 *
 * To finish it:
 *   1. composer require google/apiclient:^2.15
 *   2. Fill in GMAIL_OAUTH_* in .env
 *   3. Implement send() below using
 *      Gmail::getClient()->get Gmail service with the stored refresh token
 *   4. Set GMAIL_TRANSPORT=gmail_api
 *
 * Credentials, client secret and tokens are read from .env only and are never
 * written to source control.
 */
class GmailApiTransport
{
    /**
     * @return array{ok:bool,reason:string}
     */
    public static function status(): array
    {
        if (!class_exists(\Google\Client::class)) {
            return ['ok' => false, 'reason' => 'the Gmail API client library is not installed (run: composer require google/apiclient).'];
        }

        $required = [
            'GMAIL_OAUTH_CLIENT_ID',
            'GMAIL_OAUTH_CLIENT_SECRET',
            'GMAIL_OAUTH_REDIRECT_URI',
        ];

        foreach ($required as $key) {
            $value = (string) (SystemConfig::get($key) ?? '');
            if ($value === '' || strpos($value, 'YOUR_') === 0) {
                return ['ok' => false, 'reason' => $key . ' is not configured in .env.'];
            }
        }

        $token = (string) (SystemConfig::get('GMAIL_OAUTH_REFRESH_TOKEN') ?? '');
        if ($token === '' || strpos($token, 'YOUR_') === 0) {
            return ['ok' => false, 'reason' => 'GMAIL_OAUTH_REFRESH_TOKEN is not configured in .env.'];
        }

        return ['ok' => true, 'reason' => 'Gmail REST API credentials are present.'];
    }

    /**
     * Send via the Gmail REST API.
     *
     * Deliberately not implemented yet - see the class docblock. Returning false
     * makes SystemMail fall back to reporting the message as undelivered rather
     * than silently losing it.
     */
    public static function send(string $to, string $subject, string $text, ?string $html = null): bool
    {
        if (!self::status()['ok']) {
            return false;
        }
        // TODO: build a MIME message, base64url-encode it and POST it to
        // https://gmail.googleapis.com/gmail/v1/users/me/messages/send
        return false;
    }
}
