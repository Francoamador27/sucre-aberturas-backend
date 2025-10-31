@component('mail::message')
# Confirmación de Turno

**Hola {{ $event->patient?->nompa }} {{ $event->patient?->apepa }},**

Su cita ha sido agendada correctamente:

- 📅 **Fecha:** {{ optional($event->start)->format('d-m-Y H:i:s') }}
- 👩🏻‍💼 **Profesional:** {{ $event->doctor?->nodoc }} {{ $event->doctor?->apdoc }}
- 📍 **Lugar:** Carlos Pellegrini 464, Villa Carlos Paz

@component('mail::panel')
**Recordatorios importantes**
- Llegar puntualmente a su horario asignado (no es necesario llegar antes)  
- Tolerancia de 10 minutos de demora  
- Traer estudios previos si los tuviera  
- Traer cepillo y elementos de higiene que utilice
@endcomponent

⚠ **Importante:** Por favor respete su horario exacto de cita. Llegar muy temprano puede interrumpir la atención del paciente anterior.

Si necesita cancelar o reprogramar, por favor avísenos con **24 horas de anticipación**.

Gracias por confiar en nosotros para su atención odontológica.

Saludos,  
**{{ config('app.name') }}**
@endcomponent
