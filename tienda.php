<?php
require __DIR__ . '/includes/functions.php';
$favs = fav_ids();
$cat = trim($_GET['cat'] ?? '');
$q = trim($_GET['q'] ?? '');
$orden = $_GET['orden'] ?? 'nuevo';
$orders = ['nuevo' => 'id DESC', 'menor' => 'precio ASC', 'mayor' => 'precio DESC', 'nombre' => 'nombre ASC'];

$sql = 'SELECT * FROM productos WHERE 1';
$args = [];
if ($cat !== '') { $sql .= ' AND categoria=?'; $args[] = $cat; }
if ($q !== '') { $sql .= ' AND (nombre LIKE ? OR descripcion LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
$sql .= ' ORDER BY ' . ($orders[$orden] ?? 'id DESC');
$st = db()->prepare($sql);
$st->execute($args);
$items = $st->fetchAll();
$cats = db()->query('SELECT DISTINCT categoria FROM productos ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);

page_head('Catálogo', 'tienda.php');
?>
<div class="wrap section">
  <h1>Catálogo</h1>
  <form class="filters" method="get">
    <input type="search" name="q" placeholder="Buscar producto…" value="<?= e($q) ?>">
    <select name="cat" onchange="this.form.submit()">
      <option value="">Todas las categorías</option>
      <?php foreach ($cats as $c): ?><option <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <select name="orden" onchange="this.form.submit()">
      <option value="nuevo" <?= $orden === 'nuevo' ? 'selected' : '' ?>>Más nuevos</option>
      <option value="menor" <?= $orden === 'menor' ? 'selected' : '' ?>>Precio: menor a mayor</option>
      <option value="mayor" <?= $orden === 'mayor' ? 'selected' : '' ?>>Precio: mayor a menor</option>
      <option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre A–Z</option>
    </select>
    <button class="btn small">Buscar</button>
  </form>
  <?php if (!$items): ?><p class="empty">No encontramos productos con esos filtros.</p>
  <?php else: ?><div class="grid"><?php foreach ($items as $p) echo product_card($p, $favs); ?></div><?php endif; ?>
</div>
<?php page_foot();
