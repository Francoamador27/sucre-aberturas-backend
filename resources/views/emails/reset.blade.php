@component('mail::message')
{{-- Encabezado --}}
# Restablecé tu contraseña

Hola {{ $user->name ?? 'usuario' }},

Recibimos una solicitud para restablecer tu contraseña. Si fuiste vos, hacé clic en el siguiente botón:

{{-- Botón de acción --}}
@component('mail::button', ['url' => $resetUrl, 'color' => 'primary'])
Restablecer contraseña
@endcomponent

Este enlace expirará en 60 minutos.

Si no fuiste vos, podés ignorar este correo y tu contraseña seguirá igual.

Gracias por confiar en nosotros,<br>
**{{ config('app.name') }}**
@endcomponent
