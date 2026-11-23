<?php
require __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$p = ['nombre' => '', 'descripcion' => '', 'precio' => '', 'categoria' => 'Hamacas', 'medidas' => '', 'material' => '', 'capacidad' => '', 'colores' => '[]', 'imagen' => null, 'disponibilidad' => 'pedido'];
if ($id) {
    $st = db()->prepare('SELECT * FROM productos WHERE id=?');
    $st->execute([$id]);
    $p = $st->fetch() ?: redirect('admin/');
}
$err = '';
$catList = array_values(array_unique(array_merge(
    ['Hamacas', 'Columpios', 'Sillas', 'Decoración', 'Accesorios'],
    db()->query('SELECT DISTINCT categoria FROM productos ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN)
)));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $max = ['nombre' => 150, 'descripcion' => 3000, 'medidas' => 150, 'material' => 150, 'capacidad' => 60];
    foreach ($max as $k => $n) $p[$k] = mb_substr(trim($_POST[$k] ?? ''), 0, $n);
    $sel = $_POST['categoria_sel'] ?? '';
    $p['categoria'] = $sel === '__nueva' ? mb_substr(trim($_POST['categoria_nueva'] ?? ''), 0, 50) : $sel;
    if ($p['categoria'] !== '' && !in_array($p['categoria'], $catList, true)) $catList[] = $p['categoria'];
    if (sin_capacidad($p['categoria'])) $p['capacidad'] = ''; // no aplica a esta categoría
    $p['precio'] = $_POST['precio'] ?? '';
    $cols = [];
    foreach (($_POST['c_nombre'] ?? []) as $i => $n) {
        $n = mb_substr(trim($n), 0, 40);
        $hex = $_POST['c_hex'][$i] ?? '';
        if ($n !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) $cols[] = ['nombre' => $n, 'hex' => $hex];
    }
    $p['colores'] = json_encode($cols, JSON_UNESCAPED_UNICODE);
    try {
        if (!csrf_ok()) throw new RuntimeException('Sesión expirada, intenta de nuevo.');
        if ($p['nombre'] === '') throw new RuntimeException('El nombre es obligatorio.');
        if ($p['categoria'] === '') throw new RuntimeException('Escribe el nombre de la nueva categoría.');
        if (!is_numeric($p['precio']) || $p['precio'] < 0 || $p['precio'] > 9999999) throw new RuntimeException('Precio no válido.');
        $new = upload_image($_FILES['imagen'] ?? []);
        $old = $p['imagen'];
        if ($new) $p['imagen'] = $new;
        elseif (!empty($_POST['quitar_imagen'])) $p['imagen'] = null;
        $f = [$p['nombre'], $p['descripcion'], $p['precio'], $p['categoria'], $p['medidas'], $p['material'], $p['capacidad'], $p['colores'], $p['imagen'], empty($_POST['mas_vendido']) ? 0 : 1,
              ($_POST['disponibilidad'] ?? '') === 'stock' ? 'stock' : 'pedido'];
        if ($id) {
            db()->prepare('UPDATE productos SET nombre=?,descripcion=?,precio=?,categoria=?,medidas=?,material=?,capacidad=?,colores=?,imagen=?,mas_vendido=?,disponibilidad=? WHERE id=?')->execute([...$f, $id]);
        } else {
            db()->prepare('INSERT INTO productos (nombre,descripcion,precio,categoria,medidas,material,capacidad,colores,imagen,mas_vendido,disponibilidad) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($f);
        }
        if ($old && $old !== $p['imagen']) @unlink(__DIR__ . '/../uploads/' . basename($old));
        flash($id ? 'Producto actualizado.' : 'Producto agregado.');
        redirect('admin/');
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
}
$cols = json_decode($p['colores'], true) ?: [['nombre' => '', 'hex' => '#c99a2e']];
page_head($id ? 'Editar producto' : 'Nuevo producto');
?>
<div class="wrap section narrow">
  <h1><?= $id ? 'Editar producto' : 'Agregar producto' ?></h1>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <form class="box wide" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Nombre<input name="nombre" required value="<?= e($p['nombre']) ?>"></label>
    <label>Descripción<textarea name="descripcion" rows="4"><?= e($p['descripcion']) ?></textarea></label>
    <div class="two">
      <label>Precio (MXN)<input type="number" step="0.01" min="0" name="precio" required value="<?= e($p['precio']) ?>"></label>
      <label>Categoría
        <select id="catsel" name="categoria_sel">
          <?php foreach ($catList as $c): ?><option <?= $c === $p['categoria'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
          <option value="__nueva">+ Nueva categoría…</option>
        </select>
        <input id="catnew" name="categoria_nueva" placeholder="Nombre de la nueva categoría" maxlength="50" hidden>
      </label>
    </div>
    <div class="two">
      <label>Medidas<input name="medidas" value="<?= e($p['medidas']) ?>" placeholder="Largo 2.20 m + 80 cm de colgadera por lado"></label>
      <label>Material<input name="material" value="<?= e($p['material']) ?>"></label>
    </div>
    <label id="caplabel" data-hide-for="<?= e(implode(',', CATS_SIN_CAPACIDAD)) ?>">Capacidad de peso<input name="capacidad" value="<?= e($p['capacidad']) ?>" placeholder="250 kg"></label>

    <fieldset><legend>Colores disponibles</legend>
      <div id="cols"><?php foreach ($cols as $c): ?>
        <div class="crow"><input name="c_nombre[]" placeholder="Nombre del color" value="<?= e($c['nombre']) ?>"><input type="color" name="c_hex[]" value="<?= e($c['hex']) ?>"><button type="button" class="x">×</button></div>
      <?php endforeach; ?></div>
      <p class="hint" style="margin:8px 0 6px">Colores oficiales del hilo (clic para agregar):</p>
      <div class="palette">
        <?php foreach (paleta_oficial() as $n => $h): ?>
          <button type="button" class="chip" data-name="<?= e($n) ?>" data-hex="<?= e($h) ?>"><i style="background:<?= e($h) ?>"></i><?= e($n) ?></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn small ghost" id="addcol">+ Color personalizado</button>
    </fieldset>

    <label>Foto del producto (JPG, PNG o WEBP · máx. 5 MB)<input type="file" id="imgfile" name="imagen" accept="image/jpeg,image/png,image/webp"></label>
    <?php if ($p['imagen']): ?>
      <div class="cur" id="curimg"><img src="<?= e(root_url('uploads/' . $p['imagen'])) ?>" alt="">
        <div><b>Foto actual</b><br><label class="chk"><input type="checkbox" name="quitar_imagen" value="1" id="quitar"> Borrar esta foto</label></div></div>
    <?php endif; ?>
    <div class="cur" id="preview" hidden><img id="previmg" alt="">
      <div><b>Foto nueva</b><br><button type="button" class="btn small danger" id="clearimg">Quitar esta imagen</button>
      <?php if ($p['imagen']): ?><div class="hint">Reemplazará la foto actual al guardar.</div><?php endif; ?></div></div>
    <label>Disponibilidad
      <select name="disponibilidad">
        <option value="pedido" <?= ($p['disponibilidad'] ?? 'pedido') === 'pedido' ? 'selected' : '' ?>>Sobre pedido — entrega en 10 días hábiles</option>
        <option value="stock" <?= ($p['disponibilidad'] ?? '') === 'stock' ? 'selected' : '' ?>>En stock — entrega en 1 a 2 días</option>
      </select>
    </label>
    <label class="chk"><input type="checkbox" name="mas_vendido" value="1" <?= !empty($p['mas_vendido']) ? 'checked' : '' ?>> Mostrar en «Best Sellers» del inicio</label>
    <div class="actions"><button class="btn">Guardar</button><a class="btn ghost" href="<?= e(root_url('admin/')) ?>">Cancelar</a></div>
  </form>
</div>
<?php page_foot();
