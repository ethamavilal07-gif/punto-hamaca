<?php
require __DIR__ . '/../includes/functions.php';
require_admin();
$items = db()->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll();
page_head('Panel admin');
?>
<div class="wrap section">
  <div class="section-head"><h1>Panel de administración</h1>
    <span><?php if (is_super()): ?><a class="btn small ghost" href="<?= e(root_url('admin/usuarios.php')) ?>">Usuarios</a><?php endif; ?>
    <?php $pendMay = (int)db()->query('SELECT COUNT(*) FROM mayoreo WHERE atendida=0')->fetchColumn(); ?>
    <a class="btn small ghost" href="<?= e(root_url('admin/mayoreo.php')) ?>">Mayoreo<?= $pendMay ? ' (' . $pendMay . ')' : '' ?></a>
    <a class="btn small ghost" href="<?= e(root_url('admin/colgaderas.php')) ?>">Modelos</a>
    <a class="btn small ghost" href="<?= e(root_url('admin/inspiraciones.php')) ?>">Reseñas</a>
    <a class="btn small" href="<?= e(root_url('admin/producto.php')) ?>">+ Agregar producto</a></span></div>
  <div class="table-wrap"><table class="admin">
    <tr><th></th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Colores</th><th></th></tr>
    <?php foreach ($items as $p): ?>
    <tr>
      <td class="thumb"><?= product_media($p) ?></td>
      <td><b><?= e($p['nombre']) ?></b><?= $p['mas_vendido'] ? ' <span title="Best Seller">★</span>' : '' ?></td>
      <td><?= e($p['categoria']) ?></td>
      <td><?= money($p['precio']) ?></td>
      <td class="dots"><?php foreach (colores($p) as $c): ?><i style="background:<?= e($c['hex']) ?>" title="<?= e($c['nombre']) ?>"></i><?php endforeach; ?></td>
      <td class="row-actions">
        <a class="btn small ghost" href="<?= e(root_url('admin/producto.php?id=' . $p['id'])) ?>">Editar</a>
        <form method="post" action="<?= e(root_url('admin/eliminar.php')) ?>" data-confirm="¿Eliminar «<?= e($p['nombre']) ?>»? No se puede deshacer." data-ok="Eliminar">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn small danger">Eliminar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php page_foot();
