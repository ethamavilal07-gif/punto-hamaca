<?php
require __DIR__ . '/includes/functions.php';

// Precio sin ceros sobrantes: $99, $1,890, $1,450.50
function precio_corto(float $n): string { return '$' . number_format($n, fmod($n, 1) == 0 ? 0 : 2); }

// Mensaje de WhatsApp con el formato acordado
function pedido_mensaje(array $items, float $total, string $nombre, string $celular, array $dir): string {
    $lineas = [];
    foreach ($items as $it) {
        $l = $it['qty'] . 'x ' . $it['p']['nombre'] . ($it['color'] !== '' ? ' - ' . $it['color'] : '') . ' (' . precio_corto((float)$it['p']['precio']) . ')';
        if (!empty($it['colg'])) $l .= "
Colgadera: " . $it['colg']['nombre'];
        $lineas[] = $l;
    }
    $L = [];
    $L[] = '¡Hola, Punto Hamaca!';
    $L[] = '';
    $L[] = 'Estuve viendo su catálogo web y ' . (count($items) === 1 ? 'me encantó este artículo' : 'me encantaron estos artículos') . ':';
    $L[] = 'Nombre: ' . $nombre;
    $L[] = 'Celular: ' . celular_texto($celular);
    $L[] = '';
    $L[] = '---';
    $L[] = 'Pedido';
    foreach ($lineas as $i => $x) { $L[] = $x; $L[] = site_url('producto.php?id=' . (int)$items[$i]['p']['id']); }
    $L[] = '';
    $L[] = 'Resumen';
    $L[] = 'Productos: ' . precio_corto($total);
    $L[] = 'Envío: Por definir';
    $L[] = 'Total a pagar: ' . precio_corto($total) . ' + envío';
    $L[] = '';
    $L[] = 'Dirección';
    $L[] = 'Calle: ' . $dir['calle'];
    if ($dir['numero'] !== '') $L[] = 'Número: ' . $dir['numero']; // las direcciones guardadas antes traen el número junto a la calle
    $L[] = 'Colonia: ' . $dir['colonia'];
    $L[] = 'Ciudad: ' . $dir['ciudad'];
    $L[] = 'Estado: ' . $dir['estado'];
    $L[] = 'C.P.: ' . $dir['cp'];
    if ($dir['referencias'] !== '') $L[] = 'Referencias: ' . $dir['referencias'];
    if (mb_strtolower(trim($dir['recibe'])) !== mb_strtolower(trim($nombre))) $L[] = 'Recibe: ' . $dir['recibe'];
    return implode("\n", $L);
}

// ---- Qué se está pidiendo: todo el carrito, o un solo producto desde su página ----
function pedido_fuente(): array {
    $items = [];
    if (($_REQUEST['origen'] ?? '') === 'carrito') {
        foreach (cart_items() as $it) $items[] = ['p' => $it['p'], 'color' => $it['color'], 'qty' => $it['qty'], 'colg' => $it['colg']];
    } else {
        $st = db()->prepare('SELECT * FROM productos WHERE id=?');
        $st->execute([(int)($_REQUEST['producto'] ?? 0)]);
        if ($p = $st->fetch()) {
            $color = trim((string)($_REQUEST['color'] ?? ''));
            $nombres = array_column(colores($p), 'nombre');
            if (!$nombres) $color = '';
            elseif (!in_array($color, $nombres, true)) $color = $nombres[0];
            $items[] = ['p' => $p, 'color' => $color, 'qty' => max(1, min(20, (int)($_REQUEST['qty'] ?? 1))), 'colg' => colgadera_por_id((int)($_REQUEST['colg'] ?? 0), $p)];
        }
    }
    $total = 0;
    foreach ($items as $it) $total += $it['p']['precio'] * $it['qty'];
    return [$items, $total];
}
// Parámetros que se conservan entre pasos
function fuente_params(): array {
    if (($_REQUEST['origen'] ?? '') === 'carrito') return ['origen' => 'carrito'];
    return ['producto' => (int)($_REQUEST['producto'] ?? 0), 'color' => (string)($_REQUEST['color'] ?? ''), 'qty' => (int)($_REQUEST['qty'] ?? 1), 'colg' => (int)($_REQUEST['colg'] ?? 0)];
}
function fuente_hidden(): string {
    $h = '';
    foreach (fuente_params() as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return $h;
}

// ---- Pantalla final: el pedido ya está listo para abrir WhatsApp ----
if (isset($_GET['listo']) && !empty($_SESSION['pedido_listo'])) {
    $pl = $_SESSION['pedido_listo'];
    page_head('Pedido listo');
    ?>
<div class="wrap section narrow">
  <h1>¡Tu pedido está listo!</h1>
  <p>Revisa el mensaje. Cuando estés listo, presiona <b>Abrir WhatsApp</b> y envíalo desde el chat.</p>
  <div class="box wide">
    <pre class="msg-preview"><?= e($pl['mensaje']) ?></pre>
    <div class="actions msg-actions">
      <a class="btn accent" id="wa-go" href="<?= e($pl['url']) ?>">Abrir WhatsApp</a>
      <a class="btn ghost" href="<?= e(root_url('tienda.php')) ?>">Seguir viendo el catálogo</a>
    </div>
    <p class="hint">Recuerda: aquí no se cobra nada; el anticipo del 50% se acuerda por el chat.</p>
  </div>
</div>
<?php
    page_foot();
    exit;
}

[$items, $total] = pedido_fuente();
if (!$items) {
    flash(($_REQUEST['origen'] ?? '') === 'carrito' ? 'Tu carrito está vacío.' : 'Producto no encontrado.', 'err');
    redirect(($_REQUEST['origen'] ?? '') === 'carrito' ? 'carrito.php' : 'tienda.php');
}
$u = user();
$err = '';
$gv = []; // datos de dirección de visitante, para volver a mostrarlos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if (!csrf_ok()) throw new RuntimeException('Sesión expirada, intenta de nuevo.');

        // Guardar una dirección nueva (solo con cuenta) y volver a elegirla
        if ($accion === 'guardar_dir' && $u) {
            [$c, $e1] = dir_validar($_POST);
            if ($e1) throw new RuntimeException($e1);
            $nuevo = dir_guardar($u['id'], $c, !empty($_POST['predeterminada']));
            flash('Dirección guardada.');
            redirect('pedido.php?' . http_build_query(array_merge(fuente_params(), ['dir' => $nuevo])));
        }

        // Confirmar: armar el mensaje con el pedido y la dirección
        if ($accion === 'confirmar') {
            if ($u) {
                $sd = db()->prepare('SELECT * FROM direcciones WHERE id=? AND usuario_id=?');
                $sd->execute([(int)($_POST['dir_id'] ?? 0), $u['id']]);
                $dir = $sd->fetch();
                if (!$dir) throw new RuntimeException('Elige una dirección de entrega.');
                $sc = db()->prepare('SELECT nombre, apellido, celular FROM usuarios WHERE id=?');
                $sc->execute([$u['id']]);
                $cu = $sc->fetch();
                $cel = $cu['celular'];
                if ($cel === '') { // aún no tiene celular: se pide aquí y se guarda en su cuenta
                    $cel = celular_normalizar((string)($_POST['celular'] ?? ''));
                    if (!$cel) throw new RuntimeException('Escribe tu celular a 10 dígitos para que podamos contactarte.');
                    db()->prepare('UPDATE usuarios SET celular=? WHERE id=?')->execute([$cel, $u['id']]);
                }
                $nombre = trim($cu['nombre'] . ' ' . $cu['apellido']) ?: $dir['recibe'];
            } else {
                $gv = $_POST;
                [$dir, $e2] = dir_validar($_POST);
                if ($e2) throw new RuntimeException($e2);
                $cel = celular_normalizar((string)($_POST['celular'] ?? ''));
                if (!$cel) throw new RuntimeException('Escribe tu celular a 10 dígitos para que podamos contactarte.');
                $nombre = $dir['recibe'];
            }
            $msg = pedido_mensaje($items, (float)$total, $nombre, $cel, $dir);
            $_SESSION['pedido_listo'] = ['mensaje' => $msg, 'url' => 'https://wa.me/' . WHATSAPP . '?text=' . rawurlencode($msg)];
            redirect('pedido.php?listo=1');
        }
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
}

$dirs = $u ? dir_lista($u['id']) : [];
$sel = (int)($_GET['dir'] ?? 0);
if (!in_array($sel, array_map('intval', array_column($dirs, 'id')), true)) $sel = $dirs ? (int)$dirs[0]['id'] : 0; // la predeterminada va primero
$yo = [];
if ($u) {
    $sn = db()->prepare('SELECT nombre, apellido, celular FROM usuarios WHERE id=?');
    $sn->execute([$u['id']]);
    $n = $sn->fetch();
    $yo = ['recibe' => trim(($n['nombre'] ?? '') . ' ' . ($n['apellido'] ?? ''))];
    $celGuardado = (string)($n['celular'] ?? '');
}

page_head('Confirmar pedido', 'cart');
?>
<div class="wrap section">
  <h1>Confirma tu pedido</h1>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <div class="checkout">
    <div>
      <h2>¿A dónde lo enviamos?</h2>

      <?php if ($u): ?>
        <?php if ($dirs): ?>
        <form method="post" id="confirmar-form" class="addr-list">
          <?= csrf_field() ?><input type="hidden" name="accion" value="confirmar"><?= fuente_hidden() ?>
          <?php foreach ($dirs as $d): ?>
            <label class="addr-opt">
              <input type="radio" name="dir_id" value="<?= (int)$d['id'] ?>" <?= (int)$d['id'] === $sel ? 'checked' : '' ?> required data-msg="Elige una dirección de entrega.">
              <span>
                <b><?= e($d['recibe']) ?></b>
                <?php if ($d['predeterminada']): ?><em class="badge-def">Predeterminada</em><?php endif; ?>
                <br><?= e(dir_texto($d)) ?>
              </span>
            </label>
          <?php endforeach; ?>
          <?php if (($celGuardado ?? '') === ''): ?>
            <label class="cel-field">Tu celular <small class="hint">(10 dígitos; para coordinar tu entrega. Se guarda en tu cuenta)</small>
              <input name="celular" type="tel" inputmode="numeric" maxlength="16" required placeholder="322 123 4567" value="<?= e($_POST['celular'] ?? '') ?>" data-msg="Escribe tu celular a 10 dígitos."></label>
          <?php endif; ?>
        </form>
        <?php else: ?>
          <p class="hint">Aún no tienes direcciones guardadas. Agrega una para continuar.</p>
        <?php endif; ?>

        <details class="addr-new" <?= $dirs ? '' : 'open' ?>>
          <summary>+ Agregar una nueva dirección</summary>
          <form method="post" class="box wide">
            <?= csrf_field() ?><input type="hidden" name="accion" value="guardar_dir"><?= fuente_hidden() ?>
            <?php dir_campos($yo); ?>
            <label class="chk"><input type="checkbox" name="predeterminada" value="1"> Usar como mi dirección predeterminada</label>
            <div><button class="btn small">Guardar dirección</button></div>
          </form>
        </details>
        <p class="hint"><a href="<?= e(root_url('direcciones.php')) ?>">Administrar mis direcciones</a></p>

      <?php else: ?>
        <form method="post" id="confirmar-form" class="box wide">
          <?= csrf_field() ?><input type="hidden" name="accion" value="confirmar"><?= fuente_hidden() ?>
          <?php dir_campos($gv); ?>
          <label>Tu celular <small class="hint">(10 dígitos)</small><input name="celular" type="tel" inputmode="numeric" maxlength="16" required placeholder="322 123 4567" value="<?= e($gv['celular'] ?? '') ?>"></label>
          <p class="hint">Esta dirección solo se usa para este pedido y no se guarda.
            <a href="<?= e(root_url('registro.php')) ?>">Crea una cuenta</a> para guardar tus direcciones y no escribirlas cada vez.</p>
        </form>
      <?php endif; ?>
    </div>

    <aside class="cart-sum">
      <h3>Tu pedido</h3>
      <?php foreach ($items as $it): ?>
        <div class="co-item">
          <div class="co-media"><?= product_media($it['p']) ?></div>
          <div>
            <b><?= e($it['p']['nombre']) ?></b>
            <div class="hint"><?= $it['color'] !== '' ? e($it['color']) . ' · ' : '' ?><?= $it['qty'] ?> <?= $it['qty'] === 1 ? 'pieza' : 'piezas' ?></div>
            <?php if (!empty($it['colg'])): ?><div class="hint">Colgadera: <?= e($it['colg']['nombre']) ?></div><?php endif; ?>
          </div>
          <div class="co-sub"><?= money($it['p']['precio'] * $it['qty']) ?></div>
        </div>
      <?php endforeach; ?>
      <p class="sum-row"><span>Total</span><b><?= money($total) ?></b></p>
      <p class="hint">Aquí no se cobra nada: el pedido se confirma por WhatsApp con un <b>anticipo del 50%</b>. Entrega gratis en Bahía de Banderas y Puerto Vallarta; envíos nacionales se cotizan. <a href="<?= e(root_url('politicas-compra.php')) ?>">Ver políticas de compra</a></p>
      <?php if (!$u || $dirs): ?>
        <button class="btn accent" form="confirmar-form">Confirmar y pedir por WhatsApp</button>
      <?php endif; ?>
      <a class="btn small ghost" href="<?= e(root_url(($_REQUEST['origen'] ?? '') === 'carrito' ? 'carrito.php' : 'producto.php?id=' . (int)$items[0]['p']['id'])) ?>">← Volver</a>
    </aside>
  </div>
</div>
<?php page_foot();
