<?php
require __DIR__ . '/includes/functions.php';
page_head('Aviso de privacidad');
$contacto = LEGAL_EMAIL !== '' ? '<a href="mailto:' . e(LEGAL_EMAIL) . '">' . e(LEGAL_EMAIL) . '</a>, ' : '';
?>
<div class="wrap section legal">
  <h1>Aviso de privacidad</h1>
  <p><small>Última actualización: <?= e(LEGAL_ACTUALIZADO) ?></small></p>

  <h2>1. Responsable del tratamiento de tus datos</h2>
  <p><b><?= e(LEGAL_TITULAR) ?></b> (en adelante, “Punto Hamaca”), con domicilio en <?= e(LEGAL_DOMICILIO) ?>, es responsable del uso y protección de tus datos personales, conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP) y su normativa aplicable.</p>

  <h2>2. Datos personales que recabamos</h2>
  <table>
    <tr><th>Dato</th><th>Cuándo</th><th>Para qué</th></tr>
    <tr><td>Nombre de usuario</td><td>Al crear tu cuenta</td><td>Identificarte e iniciar sesión</td></tr>
    <tr><td>Celular (opcional al registrarte; se pide al hacer un pedido)</td><td>Al crear tu cuenta o al pedir</td><td>Contactarte para coordinar la entrega. Se incluye en el mensaje de WhatsApp solo cuando tú confirmas un pedido</td></tr>
    <tr><td>Nombre y apellido</td><td>Al crear tu cuenta</td><td>Identificarte y saber a nombre de quién preparar y entregar tu pedido</td></tr>
    <tr><td>Direcciones de entrega (calle, colonia, ciudad, estado, código postal y referencias)</td><td>Al crear tu cuenta o agregar una dirección</td><td>Coordinar la entrega de tus pedidos. Se incluyen en el mensaje de WhatsApp <b>solo cuando tú confirmas un pedido</b>. Si pides sin cuenta, la dirección no se guarda en el sitio</td></tr>
    <tr><td>Correo electrónico</td><td>Al crear tu cuenta</td><td>Identificarte, iniciar sesión y comunicarnos contigo sobre tu cuenta</td></tr>
    <tr><td>Contraseña (guardada de forma cifrada, nunca en texto legible)</td><td>Al crear tu cuenta</td><td>Proteger el acceso a tu cuenta</td></tr>
    <tr><td>Reseñas: calificación, comentario (máx. 200 caracteres) y fotos que subas</td><td>Al publicar una reseña</td><td>Mostrar opiniones sobre los productos. <b>Son públicas</b> y se muestran con tu nombre de usuario; puedes eliminarlas cuando quieras</td></tr>
    <tr><td>Productos marcados como “Me gusta”</td><td>Al usar el sitio con cuenta</td><td>Guardar tu lista de favoritos</td></tr>
    <tr><td>Dirección IP e intentos de inicio de sesión</td><td>Al iniciar sesión</td><td>Seguridad: prevenir accesos no autorizados. Se eliminan automáticamente en un máximo de 24 horas</td></tr>
  </table>
  <p>No solicitamos datos personales sensibles (salud, origen étnico, creencias, datos financieros, etc.). Los pedidos y pagos se acuerdan directamente con nosotros por WhatsApp; en este sitio <b>no</b> se capturan datos de tarjetas ni de pago.</p>

  <h2>3. Finalidades</h2>
  <p><b>Necesarias:</b> crear y administrar tu cuenta, permitirte iniciar sesión, guardar tus favoritos y mantener la seguridad del sitio.</p>
  <p><b>No usamos tus datos para</b> publicidad, perfilamiento comercial ni los vendemos o compartimos con terceros con fines de mercadotecnia.</p>

  <h2>4. Visitantes sin cuenta</h2>
  <p>Si navegas sin cuenta y marcas productos con “Me gusta”, esa información se guarda únicamente en una cookie técnica de sesión y se elimina al cerrar el navegador. No se asocia a ninguna persona identificada.</p>

  <h2>5. Transferencias</h2>
  <p>No transferimos tus datos personales a terceros, salvo cuando sea requerido por una autoridad competente conforme a la ley, o con proveedores de servicios técnicos (por ejemplo, el hospedaje del sitio) que los tratan únicamente por cuenta nuestra y bajo obligación de confidencialidad.</p>
  <p>Al confirmar un pedido, tú envías el mensaje con tu nombre y dirección de entrega a nuestro número de WhatsApp. Ese envío se realiza a través de WhatsApp (Meta), que trata los mensajes conforme a sus propias políticas de privacidad.</p>

  <h2>6. Derechos ARCO y revocación del consentimiento</h2>
  <p>Tienes derecho a <b>Acceder, Rectificar, Cancelar y Oponerte</b> al tratamiento de tus datos, así como a revocar tu consentimiento. Puedes:</p>
  <ul>
    <li>Cambiar tu contraseña o <b>eliminar tu cuenta y tus datos</b> directamente desde la sección <a href="<?= e(root_url('cuenta.php')) ?>">Mi cuenta</a>.</li>
    <li>Enviar tu solicitud a: <?= $contacto ?>por WhatsApp al <a href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener">+<?= WHATSAPP ?></a>, o en nuestro domicilio. Indica tu nombre de usuario, el derecho que deseas ejercer y una descripción clara de tu solicitud. Responderemos en un plazo máximo de 20 días hábiles.</li>
  </ul>

  <h2>7. Conservación y seguridad</h2>
  <p>Conservamos tus datos mientras tu cuenta esté activa; al eliminarla se borran de forma definitiva. Aplicamos medidas de seguridad administrativas, técnicas y físicas razonables (contraseñas cifradas, conexión protegida, control de accesos y límite de intentos de inicio de sesión) para protegerlos contra daño, pérdida, alteración o acceso no autorizado.</p>

  <h2>8. Cookies</h2>
  <p>Consulta nuestra <a href="<?= e(root_url('cookies.php')) ?>">Política de cookies</a>.</p>

  <h2>9. Menores de edad</h2>
  <p>Este sitio no está dirigido a menores de 18 años. Si eres menor, debes contar con la autorización de tu madre, padre o tutor para crear una cuenta.</p>

  <h2>10. Cambios a este aviso</h2>
  <p>Podemos modificar este aviso por cambios legales o de operación. Publicaremos la versión vigente en esta página con su fecha de actualización.</p>

  <h2>11. Autoridad</h2>
  <p>Si consideras que tu derecho a la protección de datos personales ha sido vulnerado, puedes acudir ante la autoridad competente en materia de protección de datos personales en México.</p>
</div>
<?php page_foot();
