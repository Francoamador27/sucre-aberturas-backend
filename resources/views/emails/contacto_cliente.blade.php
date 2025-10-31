@php
    $nombre   = $datos['nombre']   ?? '¡Hola!';
    $email    = $datos['email']    ?? null;
    $telefono = $datos['telefono'] ?? null;
    $asunto   = $datos['asunto']   ?? 'Consulta general';
    $mensaje  = trim($datos['mensaje'] ?? '');
@endphp

@component('mail::message')
# {{ $nombre }}

Gracias por escribirnos. **Recibimos tu consulta** y en breve un representante se pondrá en contacto contigo.

@component('mail::panel')
**Resumen de tu consulta**

- **Asunto:** {{ $asunto }}
@if($telefono)
- **Teléfono:** {{ $telefono }}
@endif
@if($email)
- **Email:** {{ $email }}
@endif
@endcomponent

@component('mail::panel')
**Mensaje:**
<br>
{!! nl2br(e($mensaje)) !!}
@endcomponent

@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
Visitar {{ config('app.name') }}
@endcomponent

> Si esta consulta fue un error, simplemente ignorá este mensaje.

@slot('subcopy')
Si necesitás adjuntar información o ampliar detalles, podés responder directamente a este email.  
**Referencia:** {{ now()->format('Ymd-His') }}
@endslot

Saludos,  
**{{ config('app.name') }}**
@endcomponent
