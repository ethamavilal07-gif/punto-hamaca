<?php
require __DIR__ . '/includes/functions.php';
$fotos = db()->query('SELECT * FROM inspiraciones ORDER BY id DESC')->fetchAll();
page_head('Inspiraciones', 'inspiraciones.php');
?>
<div class="wrap section">
  <div class="section-head center">
    <h1>Ustedes</h1>
    <p class="muted">Ideas para colgar, decorar y disfrutar tu hamaca en casa, terraza o jardín.</p>
  </div>
  <?php if (!$fotos): ?>
    <p class="empty">Pronto compartiremos nuevas ideas.</p>
  <?php else: ?>
  <div class="insp-grid">
    <?php foreach ($fotos as $f): ?>
      <a class="insp-link" href="<?= e(root_url('inspiracion.php?id=' . $f['id'])) ?>">
        <figure class="insp-item">
          <img src="<?= e(insp_url($f['imagen'])) ?>" alt="<?= e($f['titulo'] ?: 'Inspiración Punto Hamaca') ?>" loading="lazy">
          <?php if ($f['titulo'] !== ''): ?><figcaption><?= e($f['titulo']) ?></figcaption><?php endif; ?>
        </figure>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <p class="center-text"><a class="btn accent" href="<?= e(root_url('tienda.php')) ?>">Ver catálogo</a></p>
</div>
<?php page_foot();
