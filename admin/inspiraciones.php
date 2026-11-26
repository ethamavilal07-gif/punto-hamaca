<?php
require __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && ($_POST['accion'] ?? '') === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT imagen FROM inspiraciones WHERE id=?');
    $st->execute([$id]);
    if ($row = $st->fetch()) {
        db()->prepare('DELETE FROM inspiraciones WHERE id=?')->execute([$id]);
        if (!str_starts_with($row['imagen'], 'assets/')) @unlink(__DIR__ . '/../uploads/' . basename($row['imagen']));
        flash('Reseña eliminada.');
    }
    redirect('admin/inspiraciones.php');
}

$fotos = db()->query('SELECT i.*, p.nombre AS producto FROM inspiraciones i LEFT JOIN productos p ON p.id=i.producto_id ORDER BY i.id DESC')->fetchAll();
page_head('Admin · Reseñas');
?>
<div class="wrap section">
  <div class="section-head"><h1>Reseñas</h1>
    <span><a class="btn small ghost" href="<?= e(root_url('admin/')) ?>">← Productos</a>
    <a class="btn small" href="<?= e(root_url('admin/inspiracion.php')) ?>">+ Agregar reseña</a></span></div>

  <?php if (!$fotos): ?><p class="empty">Aún no hay reseñas.</p><?php else: ?>
  <div class="grid">
    <?php foreach ($fotos as $f): ?>
      <article class="card">
        <div class="card-media"><img src="<?= e(insp_url($f['imagen'])) ?>" alt=""></div>
        <div class="card-body">
          <p style="margin:0 0 4px"><b><?= e($f['titulo'] ?: 'Sin título') ?></b></p>
          <p class="hint" style="margin:0 0 10px"><?= $f['producto'] ? 'Hamaca: ' . e($f['producto']) : 'Sin hamaca asociada' ?></p>
          <div class="row-actions">
            <a class="btn small ghost" href="<?= e(root_url('admin/inspiracion.php?id=' . $f['id'])) ?>">Editar</a>
            <form method="post" data-confirm="¿Eliminar esta reseña? No se puede deshacer." data-ok="Eliminar">
              <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button class="btn small danger">Eliminar</button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php page_foot();
