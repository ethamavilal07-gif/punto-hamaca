<?php
require __DIR__ . '/includes/functions.php';

// Precio sin ceros sobrantes: $99, $1,890, $1,450.50
function precio_corto(float $n): string { return '$' . number_format($n, fmod($n, 1) == 0 ? 0 : 2); }

// ---- Factura: se suma 16% de IVA al total de la compra ----
const IVA_FACTURA = 0.16;
function usos_cfdi(): array {
    return [
        'G01' => 'Adquisición de mercancías', 'G02' => 'Devoluciones, descuentos o bonificaciones', 'G03' => 'Gastos en general',
        'I01' => 'Construcciones', 'I02' => 'Mobiliario y equipo de oficina por inversiones', 'I03' => 'Equipo de transporte',
        'I04' => 'Equipo de cómputo y accesorios', 'I05' => 'Dados, troqueles, moldes, matrices y herramental', 'I06' => 'Comunicaciones telefónicas',
        'I07' => 'Comunicaciones satelitales', 'I08' => 'Otra maquinaria y equipo',
        'D01' => 'Honorarios médicos, dentales y hospitalarios', 'D02' => 'Gastos médicos por incapacidad o discapacidad', 'D03' => 'Gastos funerales',
        'D04' => 'Donativos', 'D05' => 'Intereses reales por créditos hipotecarios', 'D06' => 'Aportaciones voluntarias al SAR',
        'D07' => 'Primas por seguros de gastos médicos', 'D08' => 'Gastos de transportación escolar obligatoria', 'D09' => 'Depósitos en cuentas para el ahorro',
        'D10' => 'Pagos por servicios educativos (colegiaturas)', 'S01' => 'Sin efectos fiscales',
    ];
}
// Devuelve [datos de factura o null, mensaje de error o null]. Si no marcó "Requiero factura", no hay datos ni error.
function factura_validar(array $in): array {
    if (empty($in['factura'])) return [null, null];
    foreach (['fac_rfc', 'fac_razon', 'fac_cp', 'fac_uso', 'fac_correo'] as $k) {
        if (!mb_check_encoding((string)($in[$k] ?? ''), 'UTF-8')) return [null, 'Hay caracteres no válidos en los datos de factura.'];
    }
    $d = [
        'rfc' => mb_strtoupper(preg_replace('/\s+/', '', (string)($in['fac_rfc'] ?? ''))),
        'razon' => mb_substr(trim(preg_replace('/\s+/', ' ', (string)($in['fac_razon'] ?? ''))), 0, 150),
        'cp' => preg_replace('/\D/', '', (string)($in['fac_cp'] ?? '')),
        'uso' => (string)($in['fac_uso'] ?? ''),
        'correo' => mb_strtolower(trim((string)($in['fac_correo'] ?? ''))),
    ];
    $err = null;
    if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/u', $d['rfc'])) $err = 'El RFC no es válido (12 o 13 caracteres, sin espacios).';
    elseif ($d['razon'] === '') $err = 'Escribe la razón social para la factura.';
    elseif (!preg_match('/^\d{5}$/', $d['cp'])) $err = 'El código postal de la factura debe tener 5 dígitos.';
    elseif (!array_key_exists($d['uso'], usos_cfdi())) $err = 'Elige el uso de CFDI.';
    elseif (mb_strlen($d['correo']) > 120 || !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) $err = 'El correo de la factura no es válido.';
    return [$err ? null : $d, $err];
}
// Desglose de importes: subtotal de productos, IVA 16% y los dos totales posibles
function importes(float $subtotal): array {
    $iva = round($subtotal * IVA_FACTURA, 2);
    return ['subtotal' => $subtotal, 'iva' => $iva, 'total_sin' => $subtotal, 'total_con' => round($subtotal + $iva, 2)];
}
function factura_campos(array $v = []): void {
    $g = fn($k) => e($v[$k] ?? '');
    ?>
    <div class="factura-box">
      <label class="chk"><input type="checkbox" name="factura" value="1" id="factura-chk" <?= !empty($v['factura']) ? 'checked' : '' ?>>
        <span>Requiero factura <small class="hint">(se suma 16% de IVA al total de la compra)</small></span></label>
      <div id="factura-fields" <?= !empty($v['factura']) ? '' : 'hidden' ?>>
        <div class="two">
          <label>RFC<input name="fac_rfc" maxlength="13" value="<?= $g('fac_rfc') ?>" placeholder="XAXX010101000" style="text-transform:uppercase" autocomplete="off"></label>
          <label>Código postal fiscal<input name="fac_cp" maxlength="5" inputmode="numeric" value="<?= $g('fac_cp') ?>" placeholder="48300"></label>
        </div>
        <label>Razón social<input name="fac_razon" maxlength="150" value="<?= $g('fac_razon') ?>" placeholder="Nombre o razón social tal como está en tu constancia"></label>
        <label>Uso de CFDI
          <select name="fac_uso">
            <option value="">Elige…</option>
            <?php foreach (usos_cfdi() as $k => $n): ?><option value="<?= e($k) ?>" <?= ($v['fac_uso'] ?? '') === $k ? 'selected' : '' ?>><?= e($k . ' - ' . $n) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>Correo electrónico para la factura<input type="email" name="fac_correo" maxlength="120" value="<?= $g('fac_correo') ?>" placeholder="facturas@empresa.com"></label>
      </div>
    </div>
    <?php
}

// Mensaje de WhatsApp con el formato acordado
function pedido_mensaje(array $items, float $total, string $nombre, string $celular, array $dir, ?array $factura = null): string {
    $imp = importes($total);
    $lineas = [];
    foreach ($items as $it) {
        $l = $it['qty'] . 'x ' . $it['p']['nombre'] . ($it['color'] !== '' ? ' - ' . $it['color'] : '') . ' (' . precio_corto((float)$it['p']['precio']) . ')';
        if (!empty($it['colg'])) $l .= "
Modelo: " . $it['colg']['nombre'];
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
    if ($factura) {
        $L[] = 'Subtotal productos: ' . precio_corto($imp['subtotal']);
        $L[] = 'IVA (16%): ' . precio_corto($imp['iva']);
        $L[] = 'Envío: Por definir';
        $L[] = 'Total a pagar (con factura): ' . precio_corto($imp['total_con']) . ' + envío';
    } else {
        $L[] = 'Productos: ' . precio_corto($imp['subtotal']);
        $L[] = 'Envío: Por definir';
        $L[] = 'Total a pagar: ' . precio_corto($imp['total_sin']) . ' + envío';
    }
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
    if ($factura) {
        $L[] = '';
        $L[] = 'Factura';
        $L[] = 'RFC: ' . $factura['rfc'];
        $L[] = 'Razón social: ' . $factura['razon'];
        $L[] = 'Código postal: ' . $factura['cp'];
        $L[] = 'Uso de CFDI: ' . $factura['uso'] . ' - ' . usos_cfdi()[$factura['uso']];
        $L[] = 'Correo: ' . $factura['correo'];
    }
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
$gv = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : []; // lo escrito, para volver a mostrarlo si hay un error

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
                [$dir, $e2] = dir_validar($_POST);
                if ($e2) throw new RuntimeException($e2);
                $cel = celular_normalizar((string)($_POST['celular'] ?? ''));
                if (!$cel) throw new RuntimeException('Escribe tu celular a 10 dígitos para que podamos contactarte.');
                $nombre = $dir['recibe'];
            }
            [$fac, $ef] = factura_validar($_POST);
            if ($ef) throw new RuntimeException($ef);
            $msg = pedido_mensaje($items, (float)$total, $nombre, $cel, $dir, $fac);
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
          <?php factura_campos($_POST); ?>
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
          <?php factura_campos($gv); ?>
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
            <?php if (!empty($it['colg'])): ?><div class="hint">Modelo: <?= e($it['colg']['nombre']) ?></div><?php endif; ?>
          </div>
          <div class="co-sub"><?= money($it['p']['precio'] * $it['qty']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php $imp = importes((float)$total); $conFac = !empty($_POST['factura']); ?>
      <div class="totals" id="totals" data-sub="<?= $imp['subtotal'] ?>" data-iva="<?= $imp['iva'] ?>" data-con="<?= $imp['total_con'] ?>">
        <p class="sum-row"><span>Subtotal de productos</span><b><?= money($imp['subtotal']) ?></b></p>
        <p class="sum-row sum-iva"><span>IVA 16% (solo si pides factura)</span><b>+ <?= money($imp['iva']) ?></b></p>
        <p class="sum-row sum-tot<?= $conFac ? '' : ' on' ?>" id="tot-sin"><span>Total sin factura</span><b><?= money($imp['total_sin']) ?></b></p>
        <p class="sum-row sum-tot<?= $conFac ? ' on' : '' ?>" id="tot-con"><span>Total con factura (+16%)</span><b><?= money($imp['total_con']) ?></b></p>
        <p class="hint tot-note" id="tot-note"><?= $conFac ? 'Tu total con factura: ' . money($imp['total_con']) : 'Tu total: ' . money($imp['total_sin']) ?> · más envío</p>
      </div>
      <p class="hint">Aquí no se cobra nada: el pedido se confirma por WhatsApp con un <b>anticipo del 50%</b>. Entrega gratis en Bahía de Banderas y Puerto Vallarta; envíos nacionales se cotizan. <a href="<?= e(root_url('politicas-compra.php')) ?>">Ver políticas de compra</a></p>
      <?php if (!$u || $dirs): ?>
        <button class="btn accent" form="confirmar-form">Confirmar y pedir por WhatsApp</button>
      <?php endif; ?>
      <a class="btn small ghost" href="<?= e(root_url(($_REQUEST['origen'] ?? '') === 'carrito' ? 'carrito.php' : 'producto.php?id=' . (int)$items[0]['p']['id'])) ?>">← Volver</a>
    </aside>
  </div>
</div>
<?php page_foot();
