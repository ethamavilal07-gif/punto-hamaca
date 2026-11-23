<?php
require __DIR__ . '/includes/functions.php';
page_head('Términos y condiciones');
?>
<div class="wrap section legal">
  <h1>Términos y condiciones de uso</h1>
  <p><small>Última actualización: <?= e(LEGAL_ACTUALIZADO) ?></small></p>

  <h2>1. Aceptación</h2>
  <p>Al navegar en este sitio o crear una cuenta aceptas estos términos. Si no estás de acuerdo, te pedimos no utilizarlo. El sitio es operado por <b><?= e(LEGAL_TITULAR) ?></b>, con domicilio en <?= e(LEGAL_DOMICILIO) ?>.</p>

  <h2>2. Qué es este sitio</h2>
  <p>Es un catálogo informativo de hamacas, columpios, sillas y piezas decorativas fabricadas por Punto Hamaca. <b>El sitio no procesa pagos.</b> Los pedidos, formas de pago, tiempos y costos de envío se acuerdan directamente por WhatsApp u otro medio de contacto, conforme a nuestras <a href="<?= e(root_url('politicas-compra.php')) ?>">Políticas de compra</a> (tiempos de entrega, anticipo del 50% y envíos).</p>

  <h2>3. Productos, precios y colores</h2>
  <ul>
    <li>Los precios están expresados en pesos mexicanos (MXN) y pueden cambiar sin previo aviso. El precio aplicable es el confirmado por Punto Hamaca al momento de tu pedido.</li>
    <li>Las fotografías y los colores mostrados son referenciales; por la naturaleza artesanal del producto y la pantalla de cada dispositivo pueden existir ligeras variaciones de tono, medida o tejido.</li>
    <li>La disponibilidad está sujeta a existencias y tiempos de fabricación.</li>
  </ul>

  <h2>4. Cuenta de usuario</h2>
  <ul>
    <li>Debes proporcionar datos verdaderos y ser mayor de 18 años (o contar con autorización de tu madre, padre o tutor).</li>
    <li>Eres responsable de mantener la confidencialidad de tu contraseña y de la actividad realizada con tu cuenta.</li>
    <li>La cuenta permite guardar productos en “Me gusta”. Puedes eliminarla en cualquier momento desde <a href="<?= e(root_url('cuenta.php')) ?>">Mi cuenta</a>.</li>
    <li>Podemos suspender o cancelar cuentas que se usen de forma fraudulenta, abusiva o contraria a estos términos.</li>
  </ul>

  <h2>5. Uso permitido</h2>
  <p>Te comprometes a no: intentar acceder a áreas restringidas (como el panel de administración) o vulnerar la seguridad del sitio; usar robots o procesos automáticos para extraer contenido o realizar intentos masivos de acceso; introducir código malicioso; ni suplantar a otras personas.</p>

  <h2>6. Reseñas y fotos de usuarios</h2>
  <ul>
    <li>Solo las personas con cuenta pueden publicar reseñas, una por producto, con calificación de 1 a 5 estrellas, un comentario de hasta 200 caracteres y hasta 3 fotos.</li>
    <li>Las reseñas son públicas y se muestran con tu nombre de usuario. Puedes eliminar la tuya en cualquier momento.</li>
    <li>Publica solo opiniones sobre tu experiencia y fotos propias. No se permiten insultos, contenido ofensivo, ilegal o engañoso, datos personales de terceros, ni imágenes de las que no seas autor.</li>
    <li>Al publicar una reseña nos autorizas a mostrarla en este sitio. Podemos eliminar reseñas que incumplan estas reglas.</li>
  </ul>

  <h2>7. Propiedad intelectual</h2>
  <p>El nombre, logotipo, diseños, fotografías, textos y demás contenidos de Punto Hamaca están protegidos por la legislación de propiedad intelectual. No puedes copiarlos, reproducirlos ni usarlos con fines comerciales sin autorización por escrito.</p>

  <h2>8. Garantías y devoluciones</h2>
  <p>Las condiciones de garantía, cambios o devoluciones de cada producto se acuerdan al realizar el pedido y se rigen por la Ley Federal de Protección al Consumidor. Los productos hechos a la medida o con diseño personalizado pueden tener condiciones distintas, que se informarán antes de confirmar la compra.</p>

  <h2>9. Limitación de responsabilidad</h2>
  <p>Hacemos lo posible por mantener el sitio disponible y la información actualizada, pero no garantizamos que esté libre de errores o interrupciones. En la medida permitida por la ley, Punto Hamaca no será responsable por daños derivados del uso del sitio o de la imposibilidad de acceder a él. Para el uso seguro de las hamacas, respeta la capacidad de peso indicada y la instalación recomendada.</p>

  <h2>10. Enlaces de terceros</h2>
  <p>El sitio puede enlazar a servicios externos (como WhatsApp o Instagram), que se rigen por sus propios términos y políticas de privacidad.</p>

  <h2>11. Privacidad y cookies</h2>
  <p>El tratamiento de tus datos se describe en el <a href="<?= e(root_url('privacidad.php')) ?>">Aviso de privacidad</a> y en la <a href="<?= e(root_url('cookies.php')) ?>">Política de cookies</a>.</p>

  <h2>12. Modificaciones</h2>
  <p>Podemos actualizar estos términos en cualquier momento. La versión vigente es la publicada en esta página, con su fecha de actualización.</p>

  <h2>13. Legislación y jurisdicción</h2>
  <p>Estos términos se rigen por las leyes de los Estados Unidos Mexicanos. Para su interpretación y cumplimiento, las partes se someten a los tribunales competentes de Puerto Vallarta, Jalisco, sin perjuicio de los derechos que la ley otorga al consumidor.</p>

  <h2>14. Contacto</h2>
  <p>Dudas sobre estos términos: <a href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener">WhatsApp</a>.</p>
</div>
<?php page_foot();
