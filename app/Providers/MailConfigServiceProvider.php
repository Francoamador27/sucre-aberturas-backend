<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Para no golpear la DB en cada request
        $settings = Cache::remember('mail_settings', 300, function () {
            return MailSetting::query()->first();
        });

        if (!$settings) {
            return;
        }

        // Mapear "none" -> null
        $encryption = $settings->encryption === 'none' ? null : $settings->encryption;

        // Importante: tu mutator guarda encriptado, usamos el accessor para desencriptar
        $password = $settings->password_decrypted;

        // Mailer principal
        Config::set('mail.default', 'smtp');

        // Config del mailer SMTP
        Config::set('mail.mailers.smtp', array_filter([
            'transport'  => 'smtp',
            'host'       => $settings->host,
            'port'       => $settings->port,
            'encryption' => $encryption,              // null | 'tls' | 'ssl'
            'username'   => $settings->username,
            'password'   => $password,
            // 'timeout' => null,
            // 'auth_mode' => null,
        ], fn ($v) => $v !== null && $v !== ''));

        // Remitente global
        Config::set('mail.from.address', $settings->from_email ?: $settings->username);
        Config::set('mail.from.name', $settings->from_name ?: config('app.name'));

        Config::set('mail.to_admin', $settings->admin_email ?: config('mail.from.address'));

    }
}
