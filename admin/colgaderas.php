<?php
require __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && ($_POST['accion'] ?? '') === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT imagen FROM colgaderas WHERE id=?');
    $st->execute([$id]);
    if ($row = $st->fetch()) {
        db()->prepare('DELETE FROM colgaderas WHERE id=?')->execute([$id]);
        if ($row['imagen']) @unlink(__DIR__ . '/../uploads/' . basename($row['imagen']));
        flash('Colgadera eliminada.');
    }
    redirect('admin/colgaderas.php');
}

$items = db()->query('SELECT * FROM colgaderas ORDER BY orden, id')->fetchAll();
page_head('Admin · Colgaderas');
?>
<div class="wrap section">
  <div class="section-head"><h1>Colgaderas</h1>
    <span><a class="btn small ghost" href="<?= e(root_url('admin/')) ?>">← Productos</a>
    <a class="btn small" href="<?= e(root_url('admin/colgadera.php')) ?>">+ Agregar colgadera</a></span></div>
  <p class="hint">Estos son los tipos de colgadera que las personas pueden elegir al personalizar una hamaca (aparecen en un carrusel debajo de cada hamaca). Solo se muestran las que están activas.</p>

  <?php if (!$items): ?><p class="empty">Aún no hay colgaderas.</p><?php else: ?>
  <div class="grid">
    <?php foreach ($items as $c): ?>
      <article class="card">
        <div class="card-media"><?= colgadera_media($c) ?></div>
        <div class="card-body">
          <p style="margin:0 0 4px"><b><?= e($c['nombre']) ?></b> <?= $c['activa'] ? '' : '<em class="badge-def" style="background:#f0dede;color:#7a231a">Oculta</em>' ?></p>
          <p class="hint" style="margin:0 0 10px"><?= e($c['descripcion']) ?></p>
          <div class="row-actions">
            <a class="btn small ghost" href="<?= e(root_url('admin/colgadera.php?id=' . $c['id'])) ?>">Editar</a>
            <form method="post" data-confirm="¿Eliminar la colgadera «<?= e($c['nombre']) ?>»? No se puede deshacer." data-ok="Eliminar">
              <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
