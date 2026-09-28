<?php

namespace App\Support;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

/**
 * System email helper for the flat-PHP pages in /public.
 *
 * The account-invitation mailer (AccountInvitation) already proves the mail
 * path works, so this class reuses that same Laravel kernel bootstrap rather
 * than adding a second, parallel mail stack.
 *
 * Transport selection:
 *   GMAIL_TRANSPORT=smtp        (default) Gmail SMTP + App Password
 *   GMAIL_TRANSPORT=gmail_api   reserved for the Gmail REST API + OAuth2
 *                                transport, see GmailApiTransport
 *
 * All credentials come from .env. Nothing is hard-coded here and no secret is
 * ever logged, echoed or included in a page.
 */
class SystemMail
{
    private static ?bool $booted = null;

    /**
     * Boot Laravel once per request so the Mail facade, config() and the
     * .env values are available from a plain PHP page.
     */
    public static function boot(): bool
    {
        if (self::$booted === true) return true;
        if (self::$booted === false) return false;

        try {
            $root = dirname(__DIR__, 2);
            if (!class_exists(\Illuminate\Foundation\Application::class, true)) {
                require_once $root . '/vendor/autoload.php';
            }
            if (!defined('LARAVEL_START')) {
                /** @var \Illuminate\Foundation\Application $app */
                $app = require $root . '/bootstrap/app.php';
                $app->make(ConsoleKernel::class)->bootstrap();
            }
            self::$booted = true;
        } catch (\Throwable $e) {
            self::$booted = false;
        }
        return self::$booted;
    }

    /**
     * Is a usable mail transport configured? Returns a human-readable reason
     * when it is not, so the settings page can explain what to do.
     *
     * @return array{ok:bool,reason:string}
     */
    public static function status(): array
    {
        if (!self::boot()) {
            return ['ok' => false, 'reason' => 'Mail library could not be loaded. Run "composer install".'];
        }

        $transport = strtolower((string) (SystemConfig::env('GMAIL_TRANSPORT') ?: 'smtp'));

        if ($transport === 'gmail_api') {
            $api = GmailApiTransport::status();
            if (!$api['ok']) {
                return ['ok' => false, 'reason' => 'GMAIL_TRANSPORT is set to gmail_api but ' . $api['reason']];
            }
            return ['ok' => true, 'reason' => 'Gmail REST API (OAuth2) transport selected.'];
        }

        $host     = (string) (SystemConfig::env('MAIL_HOST') ?: '');
        $username = (string) (SystemConfig::env('MAIL_USERNAME') ?: '');
        $password = (string) (SystemConfig::env('MAIL_PASSWORD') ?: '');

        if ($host === '' || $username === '' || $password === '') {
            return ['ok' => false, 'reason' => 'Set MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in .env.'];
        }
        if (strpos($password, 'YOUR_') === 0 || strpos($username, 'YOUR_') === 0) {
            return ['ok' => false, 'reason' => '.env still contains placeholder mail credentials.'];
        }
        return ['ok' => true, 'reason' => 'Gmail SMTP transport ready (' . $host . ').'];
    }

    /**
     * Send a plain-text (optionally HTML) email.
     *
     * @return bool true only when the message was accepted by the transport
     */
    public static function send(string $to, string $subject, string $text, ?string $html = null): bool
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        if (!self::status()['ok']) return false;
        if (!self::boot()) return false;

        try {
            $transport = strtolower((string) (SystemConfig::env('GMAIL_TRANSPORT') ?: 'smtp'));

            if ($transport === 'gmail_api') {
                return GmailApiTransport::send($to, $subject, $text, $html);
            }

            \Illuminate\Support\Facades\Mail::raw($text, function ($message) use ($to, $subject, $html) {
                $message->to($to)->subject($subject);
                if ($html !== null && $html !== '') {
                    $message->html($html);
                }
            });
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Email every account that holds the admin role and has a usable address.
     * Optionally exclude the acting account so staff are not notified of
     * actions they performed themselves.
     *
     * @return array{sent:int,failed:int,addresses:array<int,string>}
     */
    public static function notifyAdmins(string $subject, string $text, ?string $html = null, int $excludeAdminId = 0): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'addresses' => []];

        $targets = self::explicitRecipients();
        if ($targets === []) {
            $targets = self::adminRecipients($excludeAdminId);
        }
        if ($targets === []) return $result;

        foreach ($targets as $address) {
            if (self::send($address, $subject, $text, $html)) {
                $result['sent']++;
                $result['addresses'][] = $address;
            } else {
                $result['failed']++;
            }
        }
        return $result;
    }

    /**
     * Addresses from MSWD_NOTIFY_EMAILS in .env (comma separated). When set,
     * this takes priority over the admin account list.
     *
     * @return array<int,string>
     */
    private static function explicitRecipients(): array
    {
        $raw = (string) (SystemConfig::env('MSWD_NOTIFY_EMAILS') ?: '');
        if (trim($raw) === '') return [];

        $out = [];
        foreach (explode(',', $raw) as $piece) {
            $piece = trim($piece);
            if ($piece !== '' && filter_var($piece, FILTER_VALIDATE_EMAIL)) $out[] = $piece;
        }
        return $out;
    }

    /**
     * @return array<int,string>
     */
    private static function adminRecipients(int $excludeAdminId): array
    {
        global $conn;
        if (!$conn instanceof \mysqli) return [];

        $sql = "SELECT DISTINCT a.id, a.email
                  FROM admins a
                  JOIN model_has_roles mr ON mr.model_id = a.id AND mr.model_type = ?
                  JOIN roles r ON r.id = mr.role_id AND r.name = 'admin'
                 WHERE a.email IS NOT NULL AND a.email <> ''";
        $params = 'App\\Models\\Admin';
        $types = 's';

        if ($excludeAdminId > 0) {
            $sql .= " AND a.id <> ?";
            $params .= 'i';
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, $params);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) return [];

        $out = [];
        while ($row = $res->fetch_assoc()) {
            if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $out[] = $row['email'];
        }
        return $out;
    }
}
