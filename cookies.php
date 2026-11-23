<?php
require __DIR__ . '/includes/functions.php';
page_head('Política de cookies');
?>
<div class="wrap section legal">
  <h1>Política de cookies</h1>
  <p><small>Última actualización: <?= e(LEGAL_ACTUALIZADO) ?></small></p>

  <h2>1. ¿Qué son las cookies?</h2>
  <p>Son pequeños archivos que un sitio web guarda en tu navegador para recordar información durante tu visita.</p>

  <h2>2. Cookies que usa este sitio</h2>
  <table>
    <tr><th>Nombre</th><th>Tipo</th><th>Finalidad</th><th>Duración</th></tr>
    <tr><td><code>ph_session</code></td><td>Técnica / estrictamente necesaria (propia)</td><td>Mantener tu sesión iniciada, proteger los formularios contra ataques (CSRF) y recordar tus “Me gusta” si navegas sin cuenta.</td><td>Sesión: se elimina al cerrar el navegador</td></tr>
  </table>
  <p>Además, el aviso de cookies guarda en el almacenamiento local de tu navegador una marca (<code>ph_cookies_ok</code>) para no volver a mostrártelo. No contiene datos personales.</p>

  <h2>3. Lo que NO hacemos</h2>
  <ul>
    <li>No usamos cookies de publicidad ni de seguimiento entre sitios.</li>
    <li>No usamos herramientas de analítica de terceros ni píxeles de redes sociales.</li>
  </ul>
  <p>Como solo usamos cookies técnicas indispensables, no se requiere tu consentimiento para instalarlas; por eso el aviso es únicamente informativo.</p>

  <h2>4. Cómo controlarlas</h2>
  <p>Puedes bloquear o borrar las cookies desde la configuración de tu navegador. Si bloqueas la cookie técnica, no podrás iniciar sesión ni guardar “Me gusta”, pero podrás seguir viendo el catálogo.</p>

  <h2>5. Más información</h2>
  <p>Consulta nuestro <a href="<?= e(root_url('privacidad.php')) ?>">Aviso de privacidad</a> y los <a href="<?= e(root_url('terminos.php')) ?>">Términos y condiciones</a>.</p>
</div>
<?php page_foot();
