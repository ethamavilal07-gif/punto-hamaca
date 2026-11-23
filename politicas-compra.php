<?php
require __DIR__ . '/includes/functions.php';
page_head('Políticas de compra');
?>
<div class="wrap section legal">
  <h1>Políticas de compra</h1>
  <p><small>Última actualización: <?= e(LEGAL_ACTUALIZADO) ?></small></p>
  <p>Los pedidos se realizan directamente con nosotros por <a href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener">WhatsApp</a>; esta página no cobra ni procesa pagos.</p>

  <h2>1. Tiempos de entrega</h2>
  <table>
    <tr><th>Tipo de producto</th><th>Tiempo</th></tr>
    <tr><td><b>En stock</b></td><td>Se programa la entrega en <b>1 a 2 días</b>.</td></tr>
    <tr><td><b>Sobre pedido</b> (se fabrica para ti)</td><td><b>10 días hábiles</b>.</td></tr>
  </table>
  <p>En la página de cada producto indicamos si está en stock o se hace sobre pedido.</p>

  <h2>2. Anticipo</h2>
  <p>Para realizar tu pedido se solicita un <b>anticipo del 50%</b> del total.</p>

  <h2>3. Entregas y envíos</h2>
  <ul>
    <li><b>Entrega a domicilio gratis</b> en la zona de <b>Bahía de Banderas y Puerto Vallarta</b>.</li>
    <li><b>Envíos nacionales</b> (resto de la República Mexicana): se cotizan según tu ubicación.</li>
  </ul>

  <h2>4. Cotiza sin compromiso</h2>
  <p>¿Quieres un color o una pieza a tu medida? <a href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener">Escríbenos</a> y te cotizamos sin compromiso.</p>

  <p>Consulta también los <a href="<?= e(root_url('terminos.php')) ?>">Términos y condiciones</a> y el <a href="<?= e(root_url('privacidad.php')) ?>">Aviso de privacidad</a>.</p>
</div>
<?php page_foot();
