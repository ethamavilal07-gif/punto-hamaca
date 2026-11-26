<?php
require __DIR__ . '/includes/functions.php';
$st = db()->prepare('SELECT * FROM inspiraciones WHERE id=?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$i = $st->fetch();
if (!$i) { http_response_code(404); redirect('inspiraciones.php'); }

$p = null;
if ($i['producto_id']) {
    $sp = db()->prepare('SELECT * FROM productos WHERE id=?');
    $sp->execute([$i['producto_id']]);
    $p = $sp->fetch() ?: null;
}
$fav = $p ? in_array((int)$p['id'], fav_ids(), true) : false;

page_head($i['titulo'] ?: 'Reseña', 'inspiraciones.php');
?>
<div class="insp-photo" role="img" aria-label="<?= e($i['titulo'] ?: 'Reseña Punto Hamaca') ?>" style="background-image:url('<?= e(insp_url($i['imagen'])) ?>')"></div>

<div class="wrap section">
  <p><a href="<?= e(root_url('inspiraciones.php')) ?>">← Más reseñas</a></p>
  <div class="<?= $p ? 'insp-detail' : '' ?>">
    <div class="insp-text">
      <?php if ($i['titulo'] !== ''): ?><h1><?= e($i['titulo']) ?></h1><?php endif; ?>
      <h2>Sobre esta reseña</h2>
      <?php if (trim((string)$i['descripcion']) !== ''): ?>
        <p><?= nl2br(e($i['descripcion'])) ?></p>
      <?php else: ?>
        <p class="muted">Una idea para llevar el descanso a tu espacio.</p>
      <?php endif; ?>
    </div>

    <?php if ($p): $cols = colores($p); ?>
    <aside class="insp-prod">
      <div class="pm"><?= product_media($p) ?></div>
      <div class="pb">
        <span class="tag">En esta foto · <?= e($p['categoria']) ?></span>
        <h3><?= e($p['nombre']) ?></h3>
        <p class="price"><?= money($p['precio']) ?></p>
        <?php if ($p['descripcion'] !== ''): ?><p class="hint"><?= e(mb_strimwidth($p['descripcion'], 0, 140, '…')) ?></p><?php endif; ?>
        <?php if ($cols): ?><div class="dots"><?php foreach (array_slice($cols, 0, 5) as $c): // máximo 5, como en las tarjetas ?><i style="background:<?= e($c['hex']) ?>" title="<?= e($c['nombre']) ?>"></i><?php endforeach; ?><?php if (count($cols) > 5): ?><small class="more">+<?= count($cols) - 5 ?></small><?php endif; ?></div><?php endif; ?>
        <table class="specs">
          <?php foreach (['Medidas' => 'medidas', 'Material' => 'material', 'Capacidad' => 'capacidad'] as $l => $k): if ($p[$k] !== '' && !($k === 'capacidad' && sin_capacidad($p['categoria']))): ?>
            <tr><th><?= $l ?></th><td><?= e($p[$k]) ?></td></tr>
          <?php endif; endforeach; ?>
        </table>
        <div class="actions">
          <a class="btn accent" href="<?= e(root_url('producto.php?id=' . $p['id'])) ?>">Ver y personalizar</a>
          <button type="button" class="btn ghost heart-btn heart<?= $fav ? ' on' : '' ?>" data-id="<?= (int)$p['id'] ?>" aria-label="Me gusta" title="Me gusta">&#10084;</button>
        </div>
        <p class="hint">Elige color y cantidad en la página del producto.</p>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</div>
<?php page_foot();
