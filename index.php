<?php
require __DIR__ . '/includes/functions.php';
$favs = fav_ids();
// Best Sellers: primero los marcados por el admin; si faltan, los que tienen más "Me gusta"
$destacados = db()->query('SELECT p.*, (SELECT COUNT(*) FROM favoritos f WHERE f.producto_id=p.id) AS likes
    FROM productos p ORDER BY p.mas_vendido DESC, likes DESC, p.id DESC LIMIT 8')->fetchAll();
page_head('Inicio', 'index.php');
?>
<section class="hero" id="hero" style="background-image:url('<?= e(root_url('assets/imagenes/imagen-portada.jpg')) ?>')"><div class="wrap hero-in">
  <span class="kicker">Fabricantes · Puerto Vallarta, Jalisco</span>
  <h1>Hamacas hechas a mano para tu descanso</h1>
  <p>Calidad y precios accesibles, con envíos a toda la República. Elige tu modelo y el color que más te guste.</p>
  <div class="hero-cta">
    <a class="btn light" href="<?= e(root_url('tienda.php')) ?>">Ver catálogo</a>
    <a class="btn ghost light" href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener">Escríbenos por WhatsApp</a>
  </div>
</div></section>

<section class="wrap section">
  <div class="section-head center"><h2>Best Sellers</h2>
    <div class="head-links"><a href="<?= e(root_url('inspiraciones.php')) ?>">Reseñas →</a><a href="<?= e(root_url('tienda.php')) ?>">Ver todo →</a></div></div>
  <div class="carousel" id="bestsellers">
    <button type="button" class="car-btn prev" aria-label="Anterior">&#8249;</button>
    <div class="cf-track"><?php foreach ($destacados as $p) echo product_card($p, $favs); ?></div>
    <button type="button" class="car-btn next" aria-label="Siguiente">&#8250;</button>
  </div>
</section>

<section id="nosotros" class="about"><div class="wrap about-in">
  <img src="<?= e(root_url('assets/imagenes/logo.png')) ?>" alt="Punto Hamaca">
  <div><h2>Sobre Punto Hamaca</h2>
  <p>Bienvenido a Punto hamaca, donde la artesanía se une en cada detalle. Descubre la belleza de lo personalizado en nuestras creaciones únicas.</p>
  <p>Cotizamos sin compromiso, ¡escríbenos y crea tu pieza especial con nosotros! Estamos aquí para responder todas tus preguntas y diseñar lo que sueñas.</p>
  <p>Realizamos envíos a toda la República Mexicana.</p></div>
</div></section>

<section id="contacto" class="contact"><div class="wrap">
  <h2>Contáctanos</h2>
  <p>Si deseas cotizar uno de nuestros productos o quieres una hamaca especial, comunícate con nosotros para resolver tus dudas o comentarios.</p>
  <p class="contact-line"><b>WhatsApp</b> <a href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener"><?= e(whatsapp_display()) ?></a> · <b>Correo</b> <a href="mailto:<?= e(LEGAL_EMAIL) ?>"><?= e(LEGAL_EMAIL) ?></a> · <b>Instagram</b> <a href="https://instagram.com/hamacaspv" target="_blank" rel="noopener">@hamacaspv</a></p>
  <p class="contact-addr"><?= e(LEGAL_DOMICILIO) ?></p>
  <a class="btn accent" href="https://wa.me/<?= WHATSAPP ?>?text=<?= rawurlencode('Hola, me gustaría cotizar una hamaca.') ?>" target="_blank" rel="noopener">CONTÁCTANOS</a>
</div></section>
<?php page_foot();
