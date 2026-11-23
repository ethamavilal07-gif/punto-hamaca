<?php
require __DIR__ . '/includes/functions.php';
if (user()) redirect(is_admin() ? 'admin/' : 'tienda.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = mb_substr(trim($_POST['usuario'] ?? ''), 0, 120);
    if (!csrf_ok()) { $err = 'Sesión expirada, intenta de nuevo.'; }
    elseif (login_blocked($id)) { $err = 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.'; }
    else {
        $st = db()->prepare('SELECT * FROM usuarios WHERE usuario=? OR correo=?');
        $st->execute([$id, $id]);
        $u = $st->fetch();
        // password_verify también se ejecuta si el usuario no existe, para no revelar su existencia por tiempo
        $hash = $u['password'] ?? '$2y$10$usesomesillystringforsaltuseusesomesillystringfore';
        if (password_verify($_POST['password'] ?? '', $hash) && $u) {
            login_ok($id);
            db()->prepare('UPDATE usuarios SET logins=logins+1 WHERE id=?')->execute([$u['id']]);
            // Desde el 5.º inicio de sesión, recordar cambiar la contraseña inicial
            if ((int)$u['pass_inicial'] === 1 && (int)$u['logins'] + 1 >= 5) {
                flash('Aún usas la contraseña inicial. Cámbiala en «Mi cuenta» para proteger tu acceso.', 'err');
            }
            merge_guest_favs((int)$u['id']);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$u['id'], 'usuario' => $u['usuario'], 'rol' => $u['rol']];
            $next = safe_next($_SESSION['next'] ?? null);
            unset($_SESSION['next']);
            if (in_array($u['rol'], ['admin', 'superadmin'], true)) redirect('admin/');
            if ($next) { header('Location: ' . $next); exit; }
            redirect('tienda.php');
        }
        login_fail($id);
        $err = 'Usuario o contraseña incorrectos.';
    }
}
page_head('Ingresar');
?>
<div class="wrap auth">
  <form class="box" method="post">
    <h1>Ingresar</h1>
    <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label>Usuario o correo<input name="usuario" required autofocus maxlength="120" value="<?= e($_POST['usuario'] ?? '') ?>"></label>
    <label>Contraseña<input type="password" name="password" required></label>
    <button class="btn">Entrar</button>
    <p class="hint">¿No tienes cuenta? <a href="<?= e(root_url('registro.php')) ?>">Regístrate</a></p>
  </form>
</div>
<?php page_foot();
