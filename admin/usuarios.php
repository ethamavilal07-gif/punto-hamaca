<?php
require __DIR__ . '/../includes/functions.php';
require_super(); // solo la cuenta superadmin

// Contraseña temporal aleatoria (letras y números, sin caracteres que se confundan)
function pass_temporal(int $n = 10): string {
    $a = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    do {
        $p = '';
        for ($i = 0; $i < $n; $i++) $p .= $a[random_int(0, strlen($a) - 1)];
    } while (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p));
    return $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && ($_POST['accion'] ?? '') === 'reset') {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT id, usuario, rol FROM usuarios WHERE id=?');
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) flash('Usuario no encontrado.', 'err');
    elseif ((int)$u['id'] === user()['id']) flash('Tu propia contraseña se cambia desde «Mi cuenta».', 'err');
    else {
        $nueva = pass_temporal();
        // pass_inicial=1 hace que se le recuerde cambiarla; se muestra una sola vez
        db()->prepare('UPDATE usuarios SET password=?, pass_inicial=1 WHERE id=?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['pass_temporal'] = ['usuario' => $u['usuario'], 'pass' => $nueva];
    }
    redirect('admin/usuarios.php');
}

$temp = $_SESSION['pass_temporal'] ?? null;
unset($_SESSION['pass_temporal']); // solo se ve una vez

$users = db()->query('SELECT u.id,u.usuario,u.correo,u.rol,u.nombre,u.apellido,u.logins,u.creado,
    (SELECT COUNT(*) FROM direcciones d WHERE d.usuario_id=u.id) AS dirs FROM usuarios u ORDER BY u.id')->fetchAll();
page_head('Admin · Usuarios');
?>
<div class="wrap section">
  <div class="section-head"><h1>Usuarios</h1><a class="btn small ghost" href="<?= e(root_url('admin/')) ?>">← Productos</a></div>

  <?php if ($temp): ?>
  <div class="box wide" style="margin-bottom:20px;border-color:var(--accent)">
    <h2 style="margin:0">Contraseña temporal de «<?= e($temp['usuario']) ?>»</h2>
    <p class="temp-pass"><?= e($temp['pass']) ?></p>
    <p class="hint">Cópiala y envíasela por un medio privado. <b>Solo se muestra esta vez</b>: al salir de esta página ya no se puede volver a ver. Pídele que la cambie en «Mi cuenta» al entrar.</p>
  </div>
  <?php endif; ?>

  <p class="hint">Por seguridad las contraseñas se guardan cifradas y nadie puede verlas, ni tú. Si una persona la olvida, genera una temporal con «Restablecer contraseña».</p>
  <div class="table-wrap"><table class="admin">
    <tr><th>Usuario</th><th>Correo</th><th>Nombre</th><th>Rol</th><th>Registro</th><th>Inicios</th><th>Dir.</th><th></th></tr>
    <?php foreach ($users as $x): ?>
    <tr>
      <td><b><?= e($x['usuario']) ?></b></td>
      <td><?= e($x['correo']) ?></td>
      <td><?= e(trim($x['nombre'] . ' ' . $x['apellido'])) ?: '—' ?></td>
      <td><?= e($x['rol']) ?></td>
      <td><?= e(date('d/m/Y', strtotime($x['creado']))) ?></td>
      <td><?= (int)$x['logins'] ?></td>
      <td><?= (int)$x['dirs'] ?></td>
      <td class="row-actions">
        <?php if ((int)$x['id'] !== user()['id']): ?>
        <form method="post" data-confirm="¿Generar una contraseña temporal para «<?= e($x['usuario']) ?>»? La actual dejará de funcionar." data-ok="Restablecer">
          <?= csrf_field() ?><input type="hidden" name="accion" value="reset"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
          <button class="btn small ghost">Restablecer contraseña</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php page_foot();
