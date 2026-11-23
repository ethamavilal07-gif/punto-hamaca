<?php
require __DIR__ . '/includes/functions.php';
$ids = fav_ids();
$items = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM productos WHERE id IN ($in) ORDER BY id DESC");
    $st->execute($ids);
    $items = $st->fetchAll();
}
page_head('Me gusta', 'fav');
?>
<div class="wrap section">
  <h1>&#10084; Mis productos guardados</h1>
  <?php if (!user()): ?>
    <p class="notice">Estás navegando sin cuenta: tus Me gusta se borran al cerrar el navegador.
      <a href="<?= e(root_url('registro.php')) ?>">Crea una cuenta</a> o <a href="<?= e(root_url('login.php')) ?>">ingresa</a> para guardarlos y conservar los que ya marcaste.</p>
  <?php endif; ?>
  <?php if (!$items): ?><p class="empty">Aún no guardas nada. Toca el corazón en cualquier producto del <a href="<?= e(root_url('tienda.php')) ?>">catálogo</a>.</p>
  <?php else: ?><div class="grid"><?php foreach ($items as $p) echo product_card($p, $ids); ?></div><?php endif; ?>
</div>
<?php page_foot();
