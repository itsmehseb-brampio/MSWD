<?php

namespace App\Support;

use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountInvitation
{
    public static function issue(Admin $admin): void
    {
        $admin->forceFill([
            'invite_token' => Str::random(64),
            'invite_expires_at' => now()->addDays(7),
        ])->save();
    }

    public static function link(Admin $admin): string
    {
        return url('/invite/' . $admin->invite_token);
    }

    public static function send(Admin $admin): bool
    {
        if (!$admin->email || !$admin->invite_token) {
            return false;
        }

        try {
            Mail::raw(
                self::body($admin),
                function ($message) use ($admin) {
                    $message->to($admin->email, $admin->name)
                        ->subject('Invitation to the MSWD Data Management System');
                }
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function invite(Admin $admin): bool
    {
        static::issue($admin);

        return static::send($admin);
    }

    private static function body(Admin $admin): string
    {
        return implode("\n\n", [
            'Hello ' . ($admin->name ?: 'there'),
            'You have been invited to access the MSWD Data Management System for Malilipot, Albay.',
            'Click the link below to create your own username and password:',
            static::link($admin),
            'This invitation link expires in 7 days. If you did not expect this invitation, you can ignore this email.',
        ]);
    }
}