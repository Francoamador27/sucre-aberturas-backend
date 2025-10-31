@component('mail::message')
# Cancelación de Turno

**Hola {{ $event->patient?->nompa }} {{ $event->patient?->apepa }},**

Le informamos que su turno ha sido **cancelado**.

- 📅 **Fecha original:** {{ optional($event->start)->format('d-m-Y H:i:s') }}
- 👩🏻‍💼 **Profesional:** {{ $event->doctor?->nodoc }} {{ $event->doctor?->apdoc }}

Si desea **reprogramar** su turno, contáctenos respondiendo este correo o por WhatsApp.

Disculpe las molestias.  
**{{ config('app.name') }}**
@endcomponent
