<?php
require __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$i = ['titulo' => '', 'descripcion' => '', 'producto_id' => null, 'imagen' => null];
if ($id) {
    $st = db()->prepare('SELECT * FROM inspiraciones WHERE id=?');
    $st->execute([$id]);
    $i = $st->fetch() ?: redirect('admin/inspiraciones.php');
}
$productos = db()->query('SELECT id, nombre, categoria FROM productos ORDER BY categoria, nombre')->fetchAll();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $i['titulo'] = mb_substr(trim($_POST['titulo'] ?? ''), 0, 120);
    $i['descripcion'] = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 3000);
    // Solo se puede elegir un producto que ya exista en el catálogo
    $pid = (int)($_POST['producto_id'] ?? 0);
    $i['producto_id'] = in_array($pid, array_column($productos, 'id'), false) ? $pid : null;
    try {
        if (!csrf_ok()) throw new RuntimeException('Sesión expirada, intenta de nuevo.');
        $new = upload_image($_FILES['imagen'] ?? []);
        $old = $i['imagen'];
        if (!$new && !$old) throw new RuntimeException('Elige una foto (JPG, PNG o WEBP).');
        if ($new) $i['imagen'] = $new;
        $f = [$i['titulo'], $i['descripcion'], $i['producto_id'], $i['imagen']];
        if ($id) {
            db()->prepare('UPDATE inspiraciones SET titulo=?, descripcion=?, producto_id=?, imagen=? WHERE id=?')->execute([...$f, $id]);
            if ($new && $old && !str_starts_with($old, 'assets/')) @unlink(__DIR__ . '/../uploads/' . basename($old));
        } else {
            db()->prepare('INSERT INTO inspiraciones (titulo, descripcion, producto_id, imagen) VALUES (?,?,?,?)')->execute($f);
        }
        flash($id ? 'Reseña actualizada.' : 'Reseña agregada.');
        redirect('admin/inspiraciones.php');
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
}
page_head($id ? 'Editar reseña' : 'Nueva reseña');
?>
<div class="wrap section narrow">
  <h1><?= $id ? 'Editar reseña' : 'Agregar reseña' ?></h1>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <form class="box wide" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Título<input name="titulo" maxlength="120" value="<?= e($i['titulo']) ?>" placeholder="Hamaca de macramé en terraza"></label>
    <label>Descripción<textarea name="descripcion" rows="6" maxlength="3000" placeholder="Cuenta la idea: dónde está, qué la hace especial…"><?= e($i['descripcion']) ?></textarea></label>
    <label>Hamaca que aparece en la foto
      <select name="producto_id">
        <option value="0">— Sin hamaca asociada —</option>
        <?php foreach ($productos as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= (int)$i['producto_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?> (<?= e($p['categoria']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <span class="hint">Solo productos que ya están en el catálogo. Para agregar uno nuevo, créalo primero en Productos.</span>
    </label>

    <label>Foto (JPG, PNG o WEBP · máx. 5 MB)<?= $id ? ' — deja vacío para conservar la actual' : '' ?>
      <input type="file" id="imgfile" name="imagen" accept="image/jpeg,image/png,image/webp" <?= $i['imagen'] ? '' : 'required' ?>></label>
    <?php if ($i['imagen']): ?>
      <div class="cur" id="curimg"><img src="<?= e(insp_url($i['imagen'])) ?>" alt=""><div><b>Foto actual</b></div></div>
    <?php endif; ?>
    <div class="cur" id="preview" hidden><img id="previmg" alt="">
      <div><b>Foto nueva</b><br><button type="button" class="btn small danger" id="clearimg">Quitar esta imagen</button>
      <?php if ($i['imagen']): ?><div class="hint">Reemplazará la foto actual al guardar.</div><?php endif; ?></div></div>

    <div class="actions"><button class="btn">Guardar</button><a class="btn ghost" href="<?= e(root_url('admin/inspiraciones.php')) ?>">Cancelar</a></div>
  </form>
</div>
<?php page_foot();
