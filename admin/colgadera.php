<?php
require __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$c = ['nombre' => '', 'descripcion' => '', 'imagen' => null, 'icono' => 'hilo', 'activa' => 1, 'orden' => 0];
if ($id) {
    $st = db()->prepare('SELECT * FROM colgaderas WHERE id=?');
    $st->execute([$id]);
    $c = $st->fetch() ?: redirect('admin/colgaderas.php');
}
$iconos = ['hilo' => 'Hilos rectos', 'telar' => 'Banda de telar', 'macrame' => 'Macramé con nudos', 'borlas' => 'Con borlas'];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c['nombre'] = mb_substr(trim($_POST['nombre'] ?? ''), 0, 80);
    $c['descripcion'] = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 300);
    $c['icono'] = array_key_exists($_POST['icono'] ?? '', $iconos) ? $_POST['icono'] : 'hilo';
    $c['activa'] = empty($_POST['activa']) ? 0 : 1;
    $c['orden'] = max(0, min(999, (int)($_POST['orden'] ?? 0)));
    try {
        if (!csrf_ok()) throw new RuntimeException('Sesión expirada, intenta de nuevo.');
        if ($c['nombre'] === '') throw new RuntimeException('El nombre es obligatorio.');
        $new = upload_image($_FILES['imagen'] ?? []);
        $old = $c['imagen'];
        if ($new) $c['imagen'] = $new;
        elseif (!empty($_POST['quitar_imagen'])) $c['imagen'] = null;
        $f = [$c['nombre'], $c['descripcion'], $c['imagen'], $c['icono'], $c['activa'], $c['orden']];
        if ($id) db()->prepare('UPDATE colgaderas SET nombre=?,descripcion=?,imagen=?,icono=?,activa=?,orden=? WHERE id=?')->execute([...$f, $id]);
        else db()->prepare('INSERT INTO colgaderas (nombre,descripcion,imagen,icono,activa,orden) VALUES (?,?,?,?,?,?)')->execute($f);
        if ($old && $old !== $c['imagen']) @unlink(__DIR__ . '/../uploads/' . basename($old));
        flash($id ? 'Colgadera actualizada.' : 'Colgadera agregada.');
        redirect('admin/colgaderas.php');
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
}
page_head($id ? 'Editar colgadera' : 'Nueva colgadera');
?>
<div class="wrap section narrow">
  <h1><?= $id ? 'Editar colgadera' : 'Agregar colgadera' ?></h1>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <form class="box wide" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Nombre<input name="nombre" required maxlength="80" value="<?= e($c['nombre']) ?>" placeholder="Telar"></label>
    <label>Descripción<textarea name="descripcion" rows="3" maxlength="300" placeholder="Largo, material, cómo se ve…"><?= e($c['descripcion']) ?></textarea></label>

    <label>Foto (JPG, PNG o WEBP · máx. 5 MB)<input type="file" id="imgfile" name="imagen" accept="image/jpeg,image/png,image/webp"></label>
    <?php if ($c['imagen']): ?>
      <div class="cur" id="curimg"><img src="<?= e(root_url('uploads/' . $c['imagen'])) ?>" alt="">
        <div><b>Foto actual</b><br><label class="chk"><input type="checkbox" name="quitar_imagen" value="1" id="quitar"> Borrar esta foto</label></div></div>
    <?php endif; ?>
    <div class="cur" id="preview" hidden><img id="previmg" alt="">
      <div><b>Foto nueva</b><br><button type="button" class="btn small danger" id="clearimg">Quitar esta imagen</button></div></div>

    <label>Ilustración (se usa mientras no haya foto)
      <select name="icono"><?php foreach ($iconos as $k => $n): ?><option value="<?= e($k) ?>" <?= $c['icono'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select>
    </label>
    <div class="two">
      <label>Orden en el carrusel (menor = primero)<input type="number" name="orden" min="0" max="999" value="<?= (int)$c['orden'] ?>"></label>
      <label class="chk" style="align-self:end"><input type="checkbox" name="activa" value="1" <?= $c['activa'] ? 'checked' : '' ?>> Mostrar a los clientes</label>
    </div>
    <div class="actions"><button class="btn">Guardar</button><a class="btn ghost" href="<?= e(root_url('admin/colgaderas.php')) ?>">Cancelar</a></div>
  </form>
</div>
<?php page_foot();
