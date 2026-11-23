<?php
require __DIR__ . '/includes/functions.php';
$st = db()->prepare('SELECT * FROM productos WHERE id=?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { http_response_code(404); flash('Producto no encontrado.', 'err'); redirect('tienda.php'); }
$cols = colores($p);
$fav = in_array((int)$p['id'], fav_ids(), true);
$first = $cols[0] ?? ['nombre' => '', 'hex' => '#c99a2e'];
$colgs = es_hamaca($p) ? colgaderas_activas() : []; // tipos de colgadera para personalizar la hamaca

// Reseñas del producto
$sr = db()->prepare('SELECT r.*, u.usuario FROM resenas r JOIN usuarios u ON u.id=r.usuario_id WHERE r.producto_id=? ORDER BY r.id DESC LIMIT 100');
$sr->execute([$p['id']]);
$resenas = $sr->fetchAll();
$fotosPor = [];
if ($resenas) {
    $ids = array_column($resenas, 'id');
    $sf = db()->prepare('SELECT resena_id, archivo FROM resena_fotos WHERE resena_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id');
    $sf->execute($ids);
    foreach ($sf->fetchAll() as $f) $fotosPor[$f['resena_id']][] = $f['archivo'];
}
$nRes = count($resenas);
$prom = $nRes ? array_sum(array_column($resenas, 'calificacion')) / $nRes : 0;
$dist = array_fill(1, 5, 0);
foreach ($resenas as $r) $dist[(int)$r['calificacion']]++;
$mia = null;
if (user()) foreach ($resenas as $r) if ((int)$r['usuario_id'] === user()['id']) $mia = $r;

// Vista previa del enlace (WhatsApp muestra la foto del producto cuando el sitio es público)
$GLOBALS['og'] = [
    'title' => $p['nombre'] . ' · ' . money($p['precio']),
    'description' => mb_strimwidth(trim(preg_replace('/\s+/', ' ', (string)$p['descripcion'])), 0, 160, '…'),
    'image' => site_url($p['imagen'] ? 'uploads/' . $p['imagen'] : 'assets/imagenes/logo.png'),
    'url' => site_url('producto.php?id=' . (int)$p['id']),
];
page_head($p['nombre']);
?>
<div class="wrap section product">
  <div class="p-left">
  <?php /* foto */ ?>
  <div class="p-media" id="pmedia" data-photo="<?= $p['imagen'] ? 1 : 0 ?>"><?= product_media($p, $first['hex']) ?></div>
  <?php if ($colgs): ?>
  <div class="colg-box" id="colgaderas">
    <h3>Elige tu colgadera</h3>
    <p class="hint">Desliza y presiona el botón para agregarla a tu hamaca.</p>
    <div class="carousel" id="colg-carousel" data-auto="0" data-size="sm" data-default="<?= (int)$colgs[0]['id'] ?>" data-default-name="<?= e($colgs[0]['nombre']) ?>">
      <button type="button" class="car-btn prev" aria-label="Anterior">&#8249;</button>
      <div class="cf-track">
        <?php foreach ($colgs as $i => $c): ?>
          <article class="card colg-card<?= $i === 0 ? ' sel' : '' ?>" data-id="<?= (int)$c['id'] ?>" data-name="<?= e($c['nombre']) ?>">
            <div class="card-media"><?= colgadera_media($c) ?></div>
            <div class="card-body">
              <h3><?= e($c['nombre']) ?></h3>
              <?php if ($c['descripcion'] !== ''): ?><p class="hint"><?= e($c['descripcion']) ?></p><?php endif; ?>
              <button type="button" class="btn small pick-colg" data-id="<?= (int)$c['id'] ?>" data-name="<?= e($c['nombre']) ?>"><?= $i === 0 ? '✓ Elegida' : 'Elegir' ?></button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <button type="button" class="car-btn next" aria-label="Siguiente">&#8250;</button>
    </div>
  </div>
  <?php endif; ?>
</div>
  <div class="p-info">
    <span class="tag"><?= e($p['categoria']) ?></span>
    <h1><?= e($p['nombre']) ?></h1>
    <a class="rating-top" href="#resenas">
      <?= stars_html($prom) ?>
      <?php if ($nRes): ?><b><?= number_format($prom, 1) ?></b> <span>(<?= $nRes ?> <?= $nRes === 1 ? 'reseña' : 'reseñas' ?>)</span>
      <?php else: ?><span>Aún sin reseñas</span><?php endif; ?>
    </a>
    <p class="price big"><?= money($p['precio']) ?></p>
    <p><?= nl2br(e($p['descripcion'])) ?></p>

    <?php if ($cols): ?>
    <h4>Color: <span id="colname"><?= e($first['nombre']) ?></span></h4>
    <div class="swatches">
      <?php foreach ($cols as $i => $c): ?>
        <button type="button" class="sw<?= $i === 0 ? ' on' : '' ?><?= $i >= 6 ? ' sw-extra' : '' ?>" style="background:<?= e($c['hex']) ?>" data-hex="<?= e($c['hex']) ?>" data-name="<?= e($c['nombre']) ?>" title="<?= e($c['nombre']) ?>" aria-label="<?= e($c['nombre']) ?>"></button>
      <?php endforeach; ?>
      <?php if (count($cols) > 6): ?>
        <button type="button" class="more-colors" id="morecolors" aria-expanded="false" data-more="Ver más colores (+<?= count($cols) - 6 ?>)" data-less="Ver menos colores">Ver más colores (+<?= count($cols) - 6 ?>)</button>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($colgs): ?>
      <p class="colg-line">Colgadera: <b id="colgname"><?= e($colgs[0]['nombre']) ?></b> · <a href="#colgaderas">Cambiar</a></p>
    <?php endif; ?>

    <table class="specs">
      <?php foreach (['Medidas' => 'medidas', 'Material' => 'material', 'Capacidad' => 'capacidad'] as $l => $k): if ($p[$k] !== '' && !($k === 'capacidad' && sin_capacidad($p['categoria']))): ?>
        <tr><th><?= $l ?></th><td><?= e($p[$k]) ?></td></tr>
      <?php endif; endforeach; ?>
    </table>

    <div class="delivery">
      <p class="dl-main"><?= ($p['disponibilidad'] ?? 'pedido') === 'stock'
          ? '<b>En stock</b> · se programa la entrega en 1 a 2 días'
          : '<b>Sobre pedido</b> · se fabrica en 10 días hábiles' ?></p>
      <ul>
        <li>Anticipo del 50%</li>
        <li>Entrega a domicilio gratis en Bahía de Banderas y Puerto Vallarta</li>
        <li>Envíos nacionales: se cotizan</li>
      </ul>
      <a href="<?= e(root_url('politicas-compra.php')) ?>">Ver políticas de compra</a>
    </div>

    <div class="actions">
      <input type="number" id="qty" value="1" min="1" max="20" aria-label="Cantidad" class="qty-input">
      <button type="button" class="btn accent addcart" data-id="<?= (int)$p['id'] ?>">Agregar al carrito</button>
    </div>
    <div class="actions">
      <a class="btn ghost" id="wa" data-id="<?= (int)$p['id'] ?>" href="<?= e(root_url('pedido.php?producto=' . (int)$p['id'])) ?>">Pedir por WhatsApp</a>
      <button class="btn ghost heart-btn heart<?= $fav ? ' on' : '' ?>" data-id="<?= (int)$p['id'] ?>" aria-label="Me gusta" title="Me gusta">&#10084;</button>
    </div>
    <?php if (!user()): ?><p class="hint">Tus Me gusta se borran al cerrar el navegador. <a href="<?= e(root_url('registro.php')) ?>">Crea una cuenta</a> para guardarlos.</p><?php endif; ?>
  </div>
</div>

<section class="wrap section reviews" id="resenas">
  <h2>Reseñas</h2>
  <div class="rv-summary">
    <div class="rv-avg">
      <div class="rv-num"><?= $nRes ? number_format($prom, 1) : '—' ?></div>
      <?= stars_html($prom) ?>
      <div class="hint"><?= $nRes ? $nRes . ($nRes === 1 ? ' reseña' : ' reseñas') : 'Todavía no hay reseñas' ?></div>
    </div>
    <div class="rv-bars">
      <?php for ($s = 5; $s >= 1; $s--): $pc = $nRes ? round($dist[$s] / $nRes * 100) : 0; ?>
        <div class="rv-bar"><span><?= $s ?> ★</span><i><b style="width:<?= $pc ?>%"></b></i><span><?= $dist[$s] ?></span></div>
      <?php endfor; ?>
    </div>
    <div class="rv-cta">
      <?php if (!user()): ?>
        <a class="btn accent" href="<?= e(root_url('resena.php?id=' . $p['id'])) ?>">Escribir una reseña</a>
        <p class="hint">Necesitas una cuenta. Te llevamos a crearla y regresas aquí.</p>
      <?php elseif ($mia): ?>
        <p class="hint">Ya dejaste tu reseña de este producto. ¡Gracias!</p>
      <?php else: ?>
        <a class="btn accent" href="#nueva-resena" id="openreview">Escribir una reseña</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (user() && !$mia): ?>
  <form class="box wide rv-form" id="nueva-resena" method="post" action="<?= e(root_url('resena.php')) ?>" enctype="multipart/form-data" hidden>
    <?= csrf_field() ?><input type="hidden" name="accion" value="publicar"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
    <h3>Tu reseña</h3>
    <div>
      <span class="lbl-t">Tu calificación</span>
      <div class="star-input" role="radiogroup" aria-label="Calificación">
        <?php for ($s = 5; $s >= 1; $s--): ?>
          <input type="radio" id="st<?= $s ?>" name="calificacion" value="<?= $s ?>" required data-msg="Elige una calificación de 1 a 5 estrellas.">
          <label for="st<?= $s ?>" title="<?= $s ?> <?= $s === 1 ? 'estrella' : 'estrellas' ?>">★</label>
        <?php endfor; ?>
      </div>
    </div>
    <label>Comentario (opcional, máx. <?= RESENA_MAX_CHARS ?> caracteres)
      <textarea name="comentario" rows="3" maxlength="<?= RESENA_MAX_CHARS ?>" id="rvtext" placeholder="Cuéntanos qué te pareció"></textarea>
      <span class="hint"><span id="rvcount">0</span>/<?= RESENA_MAX_CHARS ?></span>
    </label>
    <label>Fotos (opcional, hasta <?= RESENA_MAX_FOTOS ?> · JPG, PNG o WEBP · máx. 5 MB c/u)
      <input type="file" name="fotos[]" id="rvfotos" accept="image/jpeg,image/png,image/webp" multiple data-max="<?= RESENA_MAX_FOTOS ?>">
    </label>
    <div class="rv-previews" id="rvprev"></div>
    <p class="hint">Tu reseña será pública y se mostrará con tu nombre de usuario.</p>
    <div class="actions"><button class="btn">Publicar reseña</button><button type="button" class="btn ghost" id="cancelreview">Cancelar</button></div>
  </form>
  <?php endif; ?>

  <div class="rv-list">
    <?php if (!$resenas): ?>
      <p class="empty">Aún no hay reseñas. ¡Sé la primera persona en opinar!</p>
    <?php endif; ?>
    <?php foreach ($resenas as $r): ?>
      <article class="rv-item">
        <div class="rv-head">
          <span class="rv-av"><?= e(mb_strtoupper(mb_substr($r['usuario'], 0, 1))) ?></span>
          <div>
            <b><?= e($r['usuario']) ?></b>
            <div><?= stars_html((float)$r['calificacion']) ?> <small class="hint"><?= e(date('d/m/Y', strtotime($r['creado']))) ?></small></div>
          </div>
          <?php if (user() && ((int)$r['usuario_id'] === user()['id'] || is_admin())): ?>
            <form method="post" action="<?= e(root_url('resena.php')) ?>" class="rv-del" data-confirm="¿Eliminar esta reseña?" data-ok="Eliminar">
              <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="resena_id" value="<?= (int)$r['id'] ?>">
              <button class="x" aria-label="Eliminar reseña" title="Eliminar reseña">×</button>
            </form>
          <?php endif; ?>
        </div>
        <?php if ($r['comentario'] !== ''): ?><p class="rv-text"><?= nl2br(e($r['comentario'])) ?></p><?php endif; ?>
        <?php if (!empty($fotosPor[$r['id']])): ?>
          <div class="rv-photos"><?php foreach ($fotosPor[$r['id']] as $a): ?>
            <img src="<?= e(root_url('uploads/' . $a)) ?>" alt="Foto de la reseña de <?= e($r['usuario']) ?>" loading="lazy">
          <?php endforeach; ?></div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php page_foot();
