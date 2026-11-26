<?php
require __DIR__ . '/includes/functions.php';
$favs = fav_ids();
$cat = trim($_GET['cat'] ?? '');
$q = trim($_GET['q'] ?? '');
$orden = $_GET['orden'] ?? 'nuevo';
$soloStock = ($_GET['stock'] ?? '') === '1';
$orders = ['nuevo' => 'id DESC', 'menor' => 'precio ASC', 'mayor' => 'precio DESC', 'nombre' => 'nombre ASC'];

$sql = 'SELECT * FROM productos WHERE 1';
$args = [];
if ($cat !== '') { $sql .= ' AND categoria=?'; $args[] = $cat; }
if ($q !== '') { $sql .= ' AND (nombre LIKE ? OR descripcion LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($soloStock) $sql .= " AND disponibilidad='stock'";
// Los productos en stock salen primero; después, el orden elegido (por defecto, los más nuevos)
$sql .= " ORDER BY (disponibilidad='stock') DESC, " . ($orders[$orden] ?? 'id DESC');
$st = db()->prepare($sql);
$st->execute($args);
$items = $st->fetchAll();
$cats = db()->query('SELECT DISTINCT categoria FROM productos ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);
$nStock = (int)db()->query("SELECT COUNT(*) FROM productos WHERE disponibilidad='stock'")->fetchColumn();

page_head('Catálogo', 'tienda.php');
?>
<div class="wrap section">
  <h1>Catálogo</h1>
  <form class="filters" method="get">
    <input type="search" name="q" placeholder="Buscar producto…" value="<?= e($q) ?>">
    <select name="cat" data-autosubmit>
      <option value="">Todas las categorías</option>
      <?php foreach ($cats as $c): ?><option <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <select name="orden" data-autosubmit>
      <option value="nuevo" <?= $orden === 'nuevo' ? 'selected' : '' ?>>Más nuevos</option>
      <option value="menor" <?= $orden === 'menor' ? 'selected' : '' ?>>Precio: menor a mayor</option>
      <option value="mayor" <?= $orden === 'mayor' ? 'selected' : '' ?>>Precio: mayor a menor</option>
      <option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre A–Z</option>
    </select>
    <label class="stock-filter<?= $soloStock ? ' on' : '' ?>">
      <input type="checkbox" name="stock" value="1" <?= $soloStock ? 'checked' : '' ?> data-autosubmit>
      <span>En stock</span> <small>(<?= $nStock ?>)</small>
    </label>
    <button class="btn small">Buscar</button>
  </form>
  <?php if (!$items): ?>
    <p class="empty"><?= $soloStock ? 'Por ahora no hay productos en stock con esos filtros. El resto se fabrica sobre pedido (10 días hábiles).' : 'No encontramos productos con esos filtros.' ?></p>
  <?php else: ?>
    <div class="grid"><?php foreach ($items as $p) echo product_card($p, $favs); ?></div>
  <?php endif; ?>

  <!-- Al final del catálogo: invitación a cotizar por mayoreo -->
  <aside class="mayoreo-cta">
    <div>
      <h2>¿Eres empresa y necesitas hamacas por mayoreo?</h2>
      <p>Hoteles, restaurantes, tiendas y distribuidores: cuéntanos qué necesitas y te enviamos una cotización.</p>
    </div>
    <a class="btn accent" href="<?= e(root_url('mayoreo.php')) ?>">Cotizar por mayoreo</a>
  </aside>
</div>
<?php page_foot();
