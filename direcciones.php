<?php
require __DIR__ . '/includes/functions.php';
require_login();
$u = user();
$err = '';
$dv = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_ok()) throw new RuntimeException('Sesión expirada, intenta de nuevo.');
        $accion = $_POST['accion'] ?? '';
        $did = (int)($_POST['dir_id'] ?? 0);
        $pdo = db();

        if ($accion === 'agregar') {
            $dv = $_POST;
            [$c, $e1] = dir_validar($_POST);
            if ($e1) throw new RuntimeException($e1);
            dir_guardar($u['id'], $c, !empty($_POST['predeterminada']));
            flash('Dirección guardada.');
            redirect('direcciones.php');
        } elseif ($accion === 'predeterminar') {
            $own = $pdo->prepare('SELECT 1 FROM direcciones WHERE id=? AND usuario_id=?');
            $own->execute([$did, $u['id']]);
            if ($own->fetch()) {
                $pdo->prepare('UPDATE direcciones SET predeterminada=0 WHERE usuario_id=?')->execute([$u['id']]);
                $pdo->prepare('UPDATE direcciones SET predeterminada=1 WHERE id=?')->execute([$did]);
                flash('Dirección predeterminada actualizada.');
            }
            redirect('direcciones.php');
        } elseif ($accion === 'eliminar') {
            $st = $pdo->prepare('SELECT predeterminada FROM direcciones WHERE id=? AND usuario_id=?');
            $st->execute([$did, $u['id']]);
            if ($row = $st->fetch()) {
                $pdo->prepare('DELETE FROM direcciones WHERE id=?')->execute([$did]);
                if ($row['predeterminada']) { // otra pasa a ser la predeterminada
                    $pdo->prepare('UPDATE direcciones SET predeterminada=1 WHERE usuario_id=? ORDER BY id DESC LIMIT 1')->execute([$u['id']]);
                }
                flash('Dirección eliminada.');
            }
            redirect('direcciones.php');
        }
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
}

$dirs = dir_lista($u['id']);
$sn = db()->prepare('SELECT nombre, apellido FROM usuarios WHERE id=?');
$sn->execute([$u['id']]);
$n = $sn->fetch();
$yo = ['recibe' => trim(($n['nombre'] ?? '') . ' ' . ($n['apellido'] ?? ''))];

page_head('Mis direcciones');
?>
<div class="wrap section narrow">
  <div class="section-head"><h1>Mis direcciones</h1><a class="btn small ghost" href="<?= e(root_url('cuenta.php')) ?>">← Mi cuenta</a></div>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

  <?php if (!$dirs): ?><p class="empty">Aún no tienes direcciones guardadas.</p><?php endif; ?>
  <div class="addr-list">
    <?php foreach ($dirs as $d): ?>
      <div class="addr-opt" style="cursor:default">
        <span style="flex:1">
          <b><?= e($d['recibe']) ?></b>
          <?php if ($d['predeterminada']): ?><em class="badge-def">Predeterminada</em><?php endif; ?>
          <br><?= e(dir_texto($d)) ?>
        </span>
        <span class="row-actions" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end">
          <?php if (!$d['predeterminada']): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="predeterminar"><input type="hidden" name="dir_id" value="<?= (int)$d['id'] ?>">
              <button class="btn small ghost">Hacer predeterminada</button></form>
          <?php endif; ?>
          <form method="post" data-confirm="¿Eliminar esta dirección?" data-ok="Eliminar"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="dir_id" value="<?= (int)$d['id'] ?>">
            <button class="btn small danger">Eliminar</button></form>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (count($dirs) < MAX_DIRECCIONES): ?>
  <details class="addr-new" <?= $dv ? 'open' : '' ?>>
    <summary>+ Agregar una nueva dirección</summary>
    <form method="post" class="box wide">
      <?= csrf_field() ?><input type="hidden" name="accion" value="agregar">
      <?php dir_campos($dv ?: $yo); ?>
      <label class="chk"><input type="checkbox" name="predeterminada" value="1"> Usar como mi dirección predeterminada</label>
      <div><button class="btn">Guardar dirección</button></div>
    </form>
  </details>
  <?php else: ?><p class="hint">Llegaste al máximo de <?= MAX_DIRECCIONES ?> direcciones. Elimina una para agregar otra.</p><?php endif; ?>
</div>
<?php page_foot();
