@component('mail::message')
# ¡Gracias por tu pedido, {{ $pedido->usuario->name ?? 'cliente' }}! 🎉

Hemos recibido tu pedido **#{{ $pedido->id }}** y lo estamos procesando.

@component('mail::panel')
**Total a pagar:** ${{ number_format($pedido->total, 2, ',', '.') }}
@endcomponent

### 🧾 Detalles del pedido

@component('mail::table')
| Producto ID | Cantidad |
|-------------|----------|
@foreach ($pedido->carritos as $carrito)
| {{ $carrito->producto_id }} | {{ $carrito->cantidad }} |
@endforeach
@endcomponent

Si tenés alguna duda, no dudes en responder este correo.

Gracias por confiar en nosotros.  
**DECOIMANES**

@endcomponent
