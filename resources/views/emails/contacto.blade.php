@component('mail::message')
# Nuevo mensaje de contacto

**Nombre:** {{ $datos['nombre'] }}  
**Email:** {{ $datos['email'] }}  
**Teléfono:** {{ $datos['telefono'] }}

**Mensaje:**

{{ $datos['mensaje'] }}

@endcomponent
