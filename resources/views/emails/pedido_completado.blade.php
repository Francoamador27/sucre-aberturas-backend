@component('mail::message')
# ¡Tu pedido ha sido completado con éxito, {{ $pedido->usuario->name ?? 'cliente' }}! 🎉

Te confirmamos que tu pedido **#{{ $pedido->id }}** ha sido **entregado y finalizado correctamente**.  
Esperamos que hayas quedado satisfecho con tu compra.

@component('mail::panel')
**Total abonado:** ${{ number_format($pedido->total, 2, ',', '.') }}
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

### 🙌 Gracias por tu confianza

Tu experiencia es muy importante para nosotros.  
Si querés dejarnos tu opinión o tenés algún comentario, podés responder a este correo.

Esperamos volver a verte pronto.  
**DECOIMANES**
@endcomponent
