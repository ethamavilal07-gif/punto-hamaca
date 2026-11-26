<?php
require __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'atendida') {
        db()->prepare('UPDATE mayoreo SET atendida = 1 - atendida WHERE id=?')->execute([$id]);
        flash('Solicitud actualizada.');
    } elseif ($accion === 'eliminar') {
        db()->prepare('DELETE FROM mayoreo WHERE id=?')->execute([$id]);
        flash('Solicitud eliminada.');
    }
    redirect('admin/mayoreo.php');
}

$items = db()->query('SELECT * FROM mayoreo ORDER BY atendida ASC, id DESC')->fetchAll();
$pend = count(array_filter($items, fn($x) => !$x['atendida']));
page_head('Admin · Mayoreo');
?>
<div class="wrap section">
  <div class="section-head"><h1>Solicitudes de mayoreo</h1><a class="btn small ghost" href="<?= e(root_url('admin/')) ?>">← Productos</a></div>
  <p class="hint"><?= $pend ?> pendiente<?= $pend === 1 ? '' : 's' ?> de <?= count($items) ?>. Las solicitudes llegan desde el formulario que aparece al final del catálogo.</p>

  <?php if (!$items): ?><p class="empty">Aún no hay solicitudes.</p><?php endif; ?>
  <?php foreach ($items as $x): ?>
    <article class="may-item<?= $x['atendida'] ? ' done' : '' ?>">
      <div class="may-head">
        <div>
          <b><?= e($x['empresa']) ?></b>
          <?= $x['atendida'] ? '<em class="badge-def">Atendida</em>' : '<em class="badge-def" style="background:#fff1c2;color:#6a4b00">Pendiente</em>' ?>
          <div class="hint"><?= e($x['giro']) ?> · <?= e($x['ciudad']) ?>, <?= e($x['estado']) ?> · <?= e(date('d/m/Y H:i', strtotime($x['creado']))) ?></div>
        </div>
        <div class="row-actions" style="display:flex;gap:6px;flex-wrap:wrap">
          <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="atendida"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
            <button class="btn small ghost"><?= $x['atendida'] ? 'Marcar pendiente' : 'Marcar atendida' ?></button></form>
          <form method="post" data-confirm="¿Eliminar la solicitud de «<?= e($x['empresa']) ?>»? No se puede deshacer." data-ok="Eliminar">
            <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
            <button class="btn small danger">Eliminar</button></form>
        </div>
      </div>
      <dl class="may-dl">
        <dt>Contacto</dt><dd><?= e($x['contacto']) ?><?= $x['puesto'] !== '' ? ' (' . e($x['puesto']) . ')' : '' ?></dd>
        <dt>Correo</dt><dd><a href="mailto:<?= e($x['correo']) ?>"><?= e($x['correo']) ?></a></dd>
        <dt>Celular</dt><dd><a href="https://wa.me/52<?= e($x['celular']) ?>" target="_blank" rel="noopener"><?= e(celular_texto($x['celular'])) ?></a></dd>
        <?php if ($x['rfc'] !== ''): ?><dt>RFC</dt><dd><?= e($x['rfc']) ?></dd><?php endif; ?>
        <dt>Interés</dt><dd><?= e($x['interes']) ?></dd>
        <dt>Cantidad</dt><dd><?= e($x['cantidad']) ?></dd>
        <?php if ($x['fecha']): ?><dt>Fecha requerida</dt><dd><?= e(date('d/m/Y', strtotime($x['fecha']))) ?></dd><?php endif; ?>
        <?php if (trim((string)$x['comentarios']) !== ''): ?><dt>Comentarios</dt><dd><?= nl2br(e($x['comentarios'])) ?></dd><?php endif; ?>
      </dl>
    </article>
  <?php endforeach; ?>
</div>
<?php page_foot();
