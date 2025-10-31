@component('mail::message')
# Tu pedido fue impreso correctamente, {{ $pedido->usuario->name ?? 'cliente' }} 🖨️

Tu pedido **#{{ $pedido->id }}** ha sido impreso y se encuentra en la etapa final del proceso.

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

### 📌 Información importante

Dado que el pedido ya fue impreso, te informamos que **no es posible cancelar ni reembolsar el monto abonado**, ya que se han utilizado materiales y recursos de producción (como papel e impresión personalizada).

Agradecemos tu comprensión y compromiso con nuestro trabajo.

Si tenés alguna consulta, podés responder a este correo y con gusto te ayudaremos.

Gracias por confiar en nosotros.  
**DECOIMANES**
@endcomponent
