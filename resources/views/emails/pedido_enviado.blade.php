@component('mail::message')
# ¡Tu pedido está en camino, {{ $pedido->usuario->name ?? 'cliente' }}! 🚚

Te informamos que tu pedido **#{{ $pedido->id }}** ha sido **enviado**. Muy pronto estarás recibiéndolo en el domicilio que nos indicaste.

@component('mail::panel')
**Total abonado o a abonar:** ${{ number_format($pedido->total, 2, ',', '.') }}
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

### 🚨 Recordatorio importante

Recordá que una vez enviado, **no es posible cancelar el pedido ni solicitar un reembolso**, ya que ya ha sido despachado y puesto en distribución.

Te agradecemos por confiar en nuestro trabajo y en la calidad de nuestros productos.

Ante cualquier duda o consulta sobre el envío, podés responder a este correo y estaremos encantados de ayudarte.

Gracias por elegirnos.  
**DECOIMANES**
@endcomponent
