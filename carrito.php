<?php
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_ok()) {
        $key = $_POST['key'] ?? '';
        switch ($_POST['accion'] ?? '') {
            case 'qty':
                if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty'] = max(1, min(20, (int)($_POST['qty'] ?? 1)));
                break;
            case 'quitar':
                unset($_SESSION['cart'][$key]);
                flash('Producto quitado del carrito.');
                break;
            case 'vaciar':
                $_SESSION['cart'] = [];
                flash('Carrito vaciado.');
                break;
        }
    }
    redirect('carrito.php');
}

$items = cart_items();
$total = 0;
foreach ($items as $it) $total += $it['p']['precio'] * $it['qty'];

page_head('Carrito', 'cart');
?>
<div class="wrap section">
  <h1>Carrito de compras</h1>
  <?php if (!$items): ?>
    <p class="empty">Tu carrito está vacío. Explora el <a href="<?= e(root_url('tienda.php')) ?>">catálogo</a> y agrega tus productos.</p>
  <?php else: ?>
  <div class="cart-layout">
    <div class="cart-list">
      <?php foreach ($items as $it): $p = $it['p'];
        $hex = '#c99a2e';
        foreach (colores($p) as $c) if ($c['nombre'] === $it['color']) $hex = $c['hex']; ?>
      <div class="cart-item">
        <a class="ci-media" href="<?= e(root_url('producto.php?id=' . $p['id'])) ?>"><?= product_media($p, $hex) ?></a>
        <div class="ci-info">
          <a href="<?= e(root_url('producto.php?id=' . $p['id'])) ?>"><b><?= e($p['nombre']) ?></b></a>
          <?php if ($it['color'] !== ''): ?><div class="hint"><i class="sw-mini" style="background:<?= e($hex) ?>"></i> <?= e($it['color']) ?></div><?php endif; ?>
          <?php if (!empty($it['colg'])): ?><div class="hint">Modelo: <?= e($it['colg']['nombre']) ?></div><?php endif; ?>
          <div class="hint"><?= money($p['precio']) ?> c/u</div>
        </div>
        <form method="post" class="ci-qty">
          <?= csrf_field() ?><input type="hidden" name="accion" value="qty"><input type="hidden" name="key" value="<?= e($it['key']) ?>">
          <div class="stepper">
            <button type="button" class="step" data-d="-1" aria-label="Disminuir">−</button>
            <input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" max="20" aria-label="Cantidad">
            <button type="button" class="step" data-d="1" aria-label="Aumentar">+</button>
          </div>
          <noscript><button class="btn small ghost">Actualizar</button></noscript>
        </form>
        <div class="ci-sub"><?= money($p['precio'] * $it['qty']) ?></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="quitar"><input type="hidden" name="key" value="<?= e($it['key']) ?>">
          <button class="x" aria-label="Quitar">×</button></form>
      </div>
      <?php endforeach; ?>
    </div>
    <aside class="cart-sum">
      <h3>Resumen</h3>
      <p class="sum-row"><span>Total (<?= cart_count() ?> piezas)</span><b><?= money($total) ?></b></p>
      <p class="hint">Aquí no se cobra nada: el pedido se confirma por WhatsApp con un <b>anticipo del 50%</b>. Entrega a domicilio gratis en Bahía de Banderas y Puerto Vallarta; envíos nacionales se cotizan. Si requieres factura, se suma 16% de IVA (lo eliges al confirmar). <a href="<?= e(root_url('politicas-compra.php')) ?>">Ver políticas de compra</a></p>
      <a class="btn accent" href="<?= e(root_url('pedido.php?origen=carrito')) ?>">Pedir por WhatsApp</a>
      <form method="post" data-confirm="¿Vaciar el carrito?" data-ok="Vaciar carrito"><?= csrf_field() ?><input type="hidden" name="accion" value="vaciar">
        <button class="btn small ghost">Vaciar carrito</button></form>
    </aside>
  </div>
  <?php endif; ?>
</div>
<?php page_foot();
