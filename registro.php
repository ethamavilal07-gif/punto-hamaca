<?php
require __DIR__ . '/includes/functions.php';
if (user()) redirect('tienda.php');
$err = '';
$v = ['nombre' => '', 'apellido' => '', 'usuario' => '', 'correo' => ''];
$dv = []; // datos de la dirección para volver a mostrarlos si hay un error
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['nombre'] = mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['nombre'] ?? '')), 0, 60);
    $v['apellido'] = mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['apellido'] ?? '')), 0, 60);
    $v['usuario'] = trim($_POST['usuario'] ?? '');
    $v['correo'] = mb_strtolower(trim($_POST['correo'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $cel = celular_normalizar((string)($_POST['celular'] ?? ''));
    $dv = $_POST;
    // La dirección predeterminada la recibe la persona que se registra
    [$dir, $errDir] = dir_validar(array_merge($_POST, ['recibe' => trim($v['nombre'] . ' ' . $v['apellido'])]));
    if (!csrf_ok()) $err = 'Sesión expirada, intenta de nuevo.';
    elseif (!mb_check_encoding($_POST['nombre'] ?? '', 'UTF-8') || !mb_check_encoding($_POST['apellido'] ?? '', 'UTF-8')) $err = 'Hay caracteres no válidos en tu nombre.';
    elseif ($v['nombre'] === '' || $v['apellido'] === '') $err = 'Escribe tu nombre y apellido.';
    elseif (!preg_match('/^[\w.\-]{3,30}$/u', $v['usuario'])) $err = 'El usuario debe tener 3–30 caracteres (letras, números, . _ -).';
    elseif (mb_strlen($v['correo']) > 120 || !filter_var($v['correo'], FILTER_VALIDATE_EMAIL)) $err = 'Correo no válido.';
    elseif ($cel === null) $err = 'El celular debe tener 10 dígitos (ej. 322 123 4567).';
    elseif (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) $err = 'La contraseña debe tener al menos 8 caracteres, con letras y números.';
    elseif (strlen($pass) > 200) $err = 'La contraseña es demasiado larga.';
    elseif ($pass !== ($_POST['password2'] ?? '')) $err = 'Las contraseñas no coinciden.';
    elseif ($errDir) $err = $errDir;
    elseif (empty($_POST['acepto'])) $err = 'Debes aceptar los Términos y condiciones y el Aviso de privacidad.';
    else {
        $st = db()->prepare('SELECT 1 FROM usuarios WHERE usuario=? OR correo=?');
        $st->execute([$v['usuario'], $v['correo']]);
        if ($st->fetch()) $err = 'Ese usuario o correo ya está registrado.';
        else {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO usuarios (usuario,correo,password,rol,nombre,apellido,celular) VALUES (?,?,?,'cliente',?,?,?)")
                    ->execute([$v['usuario'], $v['correo'], password_hash($pass, PASSWORD_DEFAULT), $v['nombre'], $v['apellido'], $cel]);
                $id = (int)$pdo->lastInsertId();
                dir_guardar($id, $dir, true); // dirección predeterminada
                $pdo->commit();
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                // Dos registros simultáneos: la base de datos (índice único) rechaza el segundo
                if ($ex->getCode() !== '23000') throw $ex;
                $err = 'Ese usuario o correo ya está registrado.';
            }
            if ($err === '') {
                merge_guest_favs($id);
                session_regenerate_id(true);
                $_SESSION['user'] = ['id' => $id, 'usuario' => $v['usuario'], 'rol' => 'cliente'];
                flash('¡Cuenta creada! Ya puedes guardar favoritos, dejar reseñas y pedir con tu dirección.');
                $next = safe_next($_SESSION['next'] ?? null); // p. ej. volver al producto que quería reseñar
                unset($_SESSION['next']);
                if ($next) { header('Location: ' . $next); exit; }
                redirect('tienda.php');
            }
        }
    }
}
page_head('Crear cuenta');
?>
<div class="wrap auth">
  <form class="box" method="post" style="max-width:520px">
    <h1>Crear cuenta</h1>
    <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <div class="two">
      <label>Nombre<input name="nombre" required maxlength="60" value="<?= e($v['nombre']) ?>" autocomplete="given-name"></label>
      <label>Apellido<input name="apellido" required maxlength="60" value="<?= e($v['apellido']) ?>" autocomplete="family-name"></label>
    </div>
    <label>Usuario<input name="usuario" required maxlength="30" value="<?= e($v['usuario']) ?>" autocomplete="username"></label>
    <label>Correo<input type="email" name="correo" required maxlength="120" value="<?= e($v['correo']) ?>" autocomplete="email"></label>
    <label>Celular <small class="hint">(opcional, 10 dígitos; lo usamos para coordinar tu entrega)</small><input name="celular" type="tel" inputmode="numeric" maxlength="16" value="<?= e($_POST['celular'] ?? '') ?>" placeholder="322 123 4567" autocomplete="tel-national"></label>
    <label>Contraseña (mín. 8, con letras y números)<input type="password" name="password" required minlength="8" maxlength="200" autocomplete="new-password"></label>
    <label>Repetir contraseña<input type="password" name="password2" required minlength="8" maxlength="200" autocomplete="new-password"></label>

    <fieldset class="addr-fs"><legend>Dirección de entrega (será tu dirección predeterminada)</legend>
      <?php dir_campos($dv, '', false); ?>
    </fieldset>

    <label class="chk"><input type="checkbox" name="acepto" value="1" required data-msg="Debes aceptar los Términos y condiciones y el Aviso de privacidad.">
      <span>Acepto los <a href="<?= e(root_url('terminos.php')) ?>" target="_blank">Términos y condiciones</a> y el <a href="<?= e(root_url('privacidad.php')) ?>" target="_blank">Aviso de privacidad</a>.</span></label>
    <button class="btn">Registrarme</button>
    <p class="hint">¿Ya tienes cuenta? <a href="<?= e(root_url('login.php')) ?>">Ingresa</a></p>
  </form>
</div>
<?php page_foot();
