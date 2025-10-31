<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMailConfigRequest;
use App\Models\MailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class MailConfigController extends Controller
{
    // Devuelve la config actual (o vacía si no existe)
    public function show(Request $request)
    {
        $cfg = MailSetting::query()->first();

        return response()->json([
            'host'         => $cfg->host ?? '',
            'port'         => $cfg->port ?? null,
            'username'     => $cfg->username ?? '',
            'encryption'   => $cfg->encryption ?? 'tls',
            'from_email'   => $cfg->from_email ?? null,
            'from_name'    => $cfg->from_name ?? null,
            'admin_email'  => $cfg->admin_email ?? null,
            // Para el form: indicá si hay pass seteada, pero NO la envies
            'has_password' => (bool) ($cfg?->password),
        ]);
    }

    // Crea/actualiza (singleton)
public function update(UpdateMailConfigRequest $request)
{
    $data = $request->validated();

    $cfg = MailSetting::query()->first() ?? new MailSetting();

    $incomingPassword = $data['password'] ?? null;
    unset($data['password']);

    $cfg->fill($data);

    if ($incomingPassword !== null && $incomingPassword !== '') {
        $cfg->password = $incomingPassword; // mutator encripta
    }

    $cfg->save();

    // 1) Invalidar cache para próximos requests
    Cache::forget('mail_settings');

    // 2) Aplicar inmediatamente en este request (opcional pero útil)
    $encryption = $cfg->encryption === 'none' ? null : $cfg->encryption;

    Config::set('mail.default', 'smtp');
    Config::set('mail.mailers.smtp', array_filter([
        'transport'  => 'smtp',
        'host'       => $cfg->host,
        'port'       => $cfg->port,
        'encryption' => $encryption,
        'username'   => $cfg->username,
        'password'   => $cfg->password_decrypted,
    ], fn ($v) => $v !== null && $v !== ''));

    Config::set('mail.from.address', $cfg->from_email ?: $cfg->username);
    Config::set('mail.from.name',    $cfg->from_name  ?: config('app.name'));
    Config::set('mail.to_admin',     $cfg->admin_email ?: config('mail.from.address'));

    return response()->json([
        'message' => 'Configuración de correo guardada.',
    ]);
}
}
