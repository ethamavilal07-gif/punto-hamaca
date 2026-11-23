<?php
require __DIR__ . '/includes/functions.php';
require_login();
$err = '';
$accion = $_POST['accion'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = db()->prepare('SELECT * FROM usuarios WHERE id=?');
    $st->execute([user()['id']]);
    $u = $st->fetch();
    if (!csrf_ok()) $err = 'Sesión expirada, intenta de nuevo.';
    elseif ($accion === 'datos') { // nombre, apellido, usuario y correo
        $nom = mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['nombre'] ?? '')), 0, 60);
        $ape = mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['apellido'] ?? '')), 0, 60);
        $usr = trim($_POST['usuario'] ?? '');
        $cor = mb_strtolower(trim($_POST['correo'] ?? ''));
        $cel = celular_normalizar((string)($_POST['celular'] ?? ''));
        $cambiaAcceso = ($usr !== $u['usuario'] || $cor !== mb_strtolower($u['correo']));
        if (!mb_check_encoding($_POST['nombre'] ?? '', 'UTF-8') || !mb_check_encoding($_POST['apellido'] ?? '', 'UTF-8')) $err = 'Hay caracteres no válidos en tu nombre.';
        elseif ($nom === '' || $ape === '') $err = 'Escribe tu nombre y apellido.';
        elseif ($cel === null) $err = 'El celular debe tener 10 dígitos (ej. 322 123 4567).';
        elseif (!preg_match('/^[\w.\-]{3,30}$/u', $usr)) $err = 'El usuario debe tener 3–30 caracteres (letras, números, . _ -).';
        elseif (mb_strlen($cor) > 120 || !filter_var($cor, FILTER_VALIDATE_EMAIL)) $err = 'Correo no válido.';
        // Cambiar usuario o correo (con lo que se inicia sesión) exige la contraseña actual
        elseif ($cambiaAcceso && !password_verify($_POST['actual_datos'] ?? '', $u['password'])) $err = 'Para cambiar tu usuario o correo escribe tu contraseña actual.';
        else {
            $dup = db()->prepare('SELECT 1 FROM usuarios WHERE (usuario=? OR correo=?) AND id<>?');
            $dup->execute([$usr, $cor, $u['id']]);
            if ($dup->fetch()) $err = 'Ese usuario o correo ya está registrado.';
            else {
                try {
                    db()->prepare('UPDATE usuarios SET nombre=?, apellido=?, usuario=?, correo=?, celular=? WHERE id=?')->execute([$nom, $ape, $usr, $cor, $cel, $u['id']]);
                    $_SESSION['user']['usuario'] = $usr;
                    flash('Datos actualizados.');
                    redirect('cuenta.php');
                } catch (PDOException $ex) {
                    if ($ex->getCode() !== '23000') throw $ex;
                    $err = 'Ese usuario o correo ya está registrado.';
                }
            }
        }
    }
    elseif (!$u || !password_verify($_POST['actual'] ?? '', $u['password'])) $err = 'La contraseña actual no es correcta.';
    elseif ($accion === 'password') {
        $n = $_POST['nueva'] ?? '';
        if (strlen($n) < 8 || strlen($n) > 200 || !preg_match('/[A-Za-z]/', $n) || !preg_match('/\d/', $n)) $err = 'La nueva contraseña debe tener al menos 8 caracteres, con letras y números.';
        elseif ($n !== ($_POST['nueva2'] ?? '')) $err = 'Las contraseñas nuevas no coinciden.';
        else {
            db()->prepare('UPDATE usuarios SET password=?, pass_inicial=0 WHERE id=?')->execute([password_hash($n, PASSWORD_DEFAULT), $u['id']]);
            session_regenerate_id(true);
            flash('Contraseña actualizada.');
            redirect('cuenta.php');
        }
    } elseif ($accion === 'eliminar') {
        if (in_array($u['rol'], ['admin', 'superadmin'], true)) $err = 'La cuenta de administrador no se puede eliminar desde aquí.';
        else {
            delete_review_files('r.usuario_id=?', [$u['id']]); // fotos de sus reseñas
            db()->prepare('DELETE FROM usuarios WHERE id=?')->execute([$u['id']]);
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('Tu cuenta y tus datos fueron eliminados.');
            redirect('index.php');
        }
    }
}
$st = db()->prepare('SELECT usuario, correo, creado, nombre, apellido, celular FROM usuarios WHERE id=?');
$st->execute([user()['id']]);
$me = $st->fetch();
$guardado = $me; // lo que realmente está guardado (para el saludo)
// Si el envío de "Mis datos" falló, se vuelven a mostrar lo que la persona escribió
if ($accion === 'datos' && $err !== '') {
    foreach (['nombre', 'apellido', 'usuario', 'correo', 'celular'] as $k) $me[$k] = (string)($_POST[$k] ?? $me[$k]);
}
page_head('Mi cuenta');
?>
<div class="wrap section narrow">
  <h1>Mi cuenta</h1>
  <?php $nombreCompleto = trim(($guardado['nombre'] ?? '') . ' ' . ($guardado['apellido'] ?? '')); ?>
  <p class="cuenta-hola"><?= $nombreCompleto !== ''
      ? 'Hola, <b>' . e($nombreCompleto) . '</b>'
      : 'Aún no tenemos tu nombre y apellido. <b>Complétalos abajo</b> para que tus pedidos salgan a tu nombre.' ?></p>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

  <form class="box wide" method="post" style="margin-bottom:20px">
    <h2>Mis datos</h2>
    <?= csrf_field() ?><input type="hidden" name="accion" value="datos">
    <div class="two">
      <label>Nombre<input name="nombre" required maxlength="60" value="<?= e($me['nombre']) ?>" autocomplete="given-name"></label>
      <label>Apellido<input name="apellido" required maxlength="60" value="<?= e($me['apellido']) ?>" autocomplete="family-name"></label>
    </div>
    <label>Usuario<input name="usuario" required maxlength="30" value="<?= e($me['usuario']) ?>" autocomplete="username"></label>
    <label>Correo<input type="email" name="correo" required maxlength="120" value="<?= e($me['correo']) ?>" autocomplete="email"></label>
    <label>Celular <small class="hint">(10 dígitos)</small><input name="celular" type="tel" inputmode="numeric" maxlength="16" value="<?= e($me['celular']) ?>" placeholder="322 123 4567" autocomplete="tel-national"></label>
    <label>Contraseña actual <small class="hint">(solo si cambias tu usuario o correo)</small>
      <input type="password" name="actual_datos" autocomplete="current-password"></label>
    <div><button class="btn small">Guardar datos</button>
      <a class="btn small ghost" href="<?= e(root_url('direcciones.php')) ?>">Mis direcciones</a></div>
  </form>

  <form class="box wide" method="post">
    <h2>Cambiar contraseña</h2>
    <?= csrf_field() ?><input type="hidden" name="accion" value="password">
    <label>Contraseña actual<input type="password" name="actual" required autocomplete="current-password"></label>
    <label>Nueva contraseña (mín. 8, con letras y números)<input type="password" name="nueva" required minlength="8" maxlength="200" autocomplete="new-password"></label>
    <label>Repetir nueva contraseña<input type="password" name="nueva2" required minlength="8" maxlength="200" autocomplete="new-password"></label>
    <div><button class="btn">Guardar contraseña</button></div>
  </form>

  <?php if (!is_admin()): ?>
  <form class="box wide" method="post" data-confirm="¿Eliminar tu cuenta y todos tus datos? Esta acción no se puede deshacer." data-ok="Eliminar cuenta" style="margin-top:20px">
    <h2>Eliminar mi cuenta</h2>
    <p class="hint">Borra tu usuario, correo y Me gusta de forma definitiva (derecho de cancelación, ver <a href="<?= e(root_url('privacidad.php')) ?>">Aviso de privacidad</a>).</p>
    <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar">
    <label>Confirma con tu contraseña<input type="password" name="actual" required autocomplete="current-password"></label>
    <div><button class="btn danger">Eliminar mi cuenta</button></div>
  </form>
  <?php endif; ?>
</div>
<?php page_foot();
