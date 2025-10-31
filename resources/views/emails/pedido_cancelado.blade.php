@component('mail::message')
# Tu pedido ha sido cancelado, {{ $pedido->usuario->name ?? 'cliente' }} ❌

Te informamos que el pedido **#{{ $pedido->id }}** ha sido **cancelado**.  
Lamentamos cualquier inconveniente y quedamos a disposición para ayudarte si necesitás más información.

@component('mail::panel')
**Total del pedido:** ${{ number_format($pedido->total, 2, ',', '.') }}
@endcomponent

### 🧾 Detalles del pedido

@component('mail::table')
| Producto ID | Cantidad |
|-------------|----------|
@foreach ($pedido->carritos as $carrito)
| {{ $carrito->producto_id }} | {{ $carrito->cantidad }} |
@endforeach
@endcomponent

---

### ℹ️ Información importante


Si tenés dudas sobre esta cancelación o querés más detalles, podés responder directamente a este correo.

Gracias por tu comprensión.  
**DECOIMANES**
@endcomponent
