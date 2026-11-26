<?php
require __DIR__ . '/includes/functions.php';

const MAYOREO_GIROS = ['Hotel o resort', 'Restaurante, bar o club de playa', 'Tienda o boutique', 'Distribuidor o mayorista', 'Diseño de interiores o decoración', 'Eventos', 'Otro'];
const MAYOREO_CANTIDADES = ['10 a 24 piezas', '25 a 49 piezas', '50 a 99 piezas', '100 piezas o más'];

// ---- Pantalla de agradecimiento ----
if (isset($_GET['gracias']) && !empty($_SESSION['mayoreo_ok'])) {
    $ok = $_SESSION['mayoreo_ok'];
    $msg = 'Hola, soy ' . $ok['contacto'] . ' de ' . $ok['empresa'] . '. Acabo de enviar una solicitud de cotización por mayoreo desde su catálogo web.';
    page_head('Solicitud enviada');
    ?>
<div class="wrap section narrow">
  <h1>¡Recibimos tu solicitud!</h1>
  <p>Gracias, <b><?= e($ok['contacto']) ?></b>. Revisaremos lo que necesita <b><?= e($ok['empresa']) ?></b> y te contactaremos por correo o celular con tu cotización.</p>
  <div class="box wide">
    <p class="hint" style="margin:0">¿Quieres agilizarlo? También puedes avisarnos por WhatsApp.</p>
    <div class="actions msg-actions">
      <a class="btn accent" target="_blank" rel="noopener" href="https://wa.me/<?= WHATSAPP ?>?text=<?= rawurlencode($msg) ?>">Avisar por WhatsApp</a>
      <a class="btn ghost" href="<?= e(root_url('tienda.php')) ?>">Volver al catálogo</a>
    </div>
  </div>
</div>
<?php
    page_foot();
    exit;
}

$cats = db()->query('SELECT DISTINCT categoria FROM productos ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);
$err = '';
$v = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    try {
        if (!csrf_ok()) throw new RuntimeException('Sesión expirada, intenta de nuevo.');
        if (!empty($_POST['sitio_web'])) { // campo trampa para robots: se ignora sin avisar
            redirect('mayoreo.php');
        }
        if (login_blocked('mayoreo:' . $ip)) throw new RuntimeException('Enviaste varias solicitudes seguidas. Espera unos minutos e inténtalo de nuevo.');

        foreach (['empresa', 'contacto', 'puesto', 'correo', 'celular', 'rfc', 'ciudad', 'comentarios'] as $k) {
            if (!mb_check_encoding((string)($_POST[$k] ?? ''), 'UTF-8')) throw new RuntimeException('Hay caracteres no válidos en el formulario.');
        }
        $clean = fn($k, $n) => mb_substr(trim(preg_replace('/[ \t]+/', ' ', str_replace("\r", '', (string)($_POST[$k] ?? '')))), 0, $n);
        $d = [
            'empresa' => $clean('empresa', 150), 'giro' => (string)($_POST['giro'] ?? ''),
            'contacto' => $clean('contacto', 100), 'puesto' => $clean('puesto', 80),
            'correo' => mb_strtolower($clean('correo', 120)), 'celular' => celular_normalizar((string)($_POST['celular'] ?? '')),
            'rfc' => mb_strtoupper(preg_replace('/\s+/', '', (string)($_POST['rfc'] ?? ''))),
            'ciudad' => $clean('ciudad', 100), 'estado' => (string)($_POST['estado'] ?? ''),
            'cantidad' => (string)($_POST['cantidad'] ?? ''), 'fecha' => (string)($_POST['fecha'] ?? ''),
            'comentarios' => $clean('comentarios', 600),
        ];
        $int = array_values(array_intersect((array)($_POST['interes'] ?? []), array_merge($cats, ['Otro'])));
        $d['interes'] = implode(', ', $int);

        if ($d['empresa'] === '') throw new RuntimeException('Escribe el nombre de la empresa.');
        if (!in_array($d['giro'], MAYOREO_GIROS, true)) throw new RuntimeException('Elige el giro de la empresa.');
        if ($d['contacto'] === '') throw new RuntimeException('Escribe el nombre de la persona de contacto.');
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Escribe un correo válido.');
        if (!$d['celular']) throw new RuntimeException('Escribe un celular de 10 dígitos.');
        if ($d['rfc'] !== '' && !preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/u', $d['rfc'])) throw new RuntimeException('El RFC no es válido (12 o 13 caracteres) o déjalo vacío.');
        if ($d['ciudad'] === '') throw new RuntimeException('Escribe la ciudad.');
        if (!in_array($d['estado'], estados_mx(), true)) throw new RuntimeException('Elige el estado.');
        if ($d['interes'] === '') throw new RuntimeException('Elige al menos un producto de interés.');
        if (!in_array($d['cantidad'], MAYOREO_CANTIDADES, true)) throw new RuntimeException('Elige la cantidad aproximada.');
        if ($d['fecha'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha'])) $d['fecha'] = '';
        if (empty($_POST['acepto'])) throw new RuntimeException('Debes aceptar el Aviso de privacidad.');

        login_fail('mayoreo:' . $ip); // límite: 5 solicitudes cada 15 minutos por IP
        db()->prepare('INSERT INTO mayoreo (empresa,giro,contacto,puesto,correo,celular,rfc,ciudad,estado,interes,cantidad,fecha,comentarios) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$d['empresa'], $d['giro'], $d['contacto'], $d['puesto'], $d['correo'], $d['celular'], $d['rfc'], $d['ciudad'], $d['estado'], $d['interes'], $d['cantidad'], $d['fecha'] ?: null, $d['comentarios']]);
        $_SESSION['mayoreo_ok'] = ['empresa' => $d['empresa'], 'contacto' => $d['contacto']];
        redirect('mayoreo.php?gracias=1');
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
}
$g = fn($k) => e($v[$k] ?? '');
page_head('Cotización por mayoreo');
?>
<div class="wrap section narrow">
  <h1>Cotización por mayoreo</h1>
  <p>¿Tu empresa necesita hamacas por volumen? Llena este formulario y te enviamos una cotización. Todos los campos son obligatorios, salvo los marcados como opcionales.</p>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <form class="box wide" method="post">
    <?= csrf_field() ?>
    <div class="hp" aria-hidden="true"><label>No llenar<input name="sitio_web" tabindex="-1" autocomplete="off"></label></div>

    <fieldset class="addr-fs"><legend>Tu empresa</legend>
      <label>Nombre de la empresa<input name="empresa" required maxlength="150" value="<?= $g('empresa') ?>"></label>
      <div class="two">
        <label>Giro
          <select name="giro" required>
            <option value="">Elige…</option>
            <?php foreach (MAYOREO_GIROS as $x): ?><option <?= ($v['giro'] ?? '') === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>RFC <small class="hint">(opcional)</small><input name="rfc" maxlength="13" value="<?= $g('rfc') ?>" style="text-transform:uppercase" autocomplete="off"></label>
      </div>
      <div class="two">
        <label>Ciudad<input name="ciudad" required maxlength="100" value="<?= $g('ciudad') ?>"></label>
        <label>Estado
          <select name="estado" required>
            <option value="">Elige…</option>
            <?php foreach (estados_mx() as $x): ?><option <?= ($v['estado'] ?? '') === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
    </fieldset>

    <fieldset class="addr-fs"><legend>Persona de contacto</legend>
      <div class="two">
        <label>Nombre<input name="contacto" required maxlength="100" value="<?= $g('contacto') ?>" autocomplete="name"></label>
        <label>Puesto <small class="hint">(opcional)</small><input name="puesto" maxlength="80" value="<?= $g('puesto') ?>"></label>
      </div>
      <div class="two">
        <label>Correo<input type="email" name="correo" required maxlength="120" value="<?= $g('correo') ?>" autocomplete="email"></label>
        <label>Celular<input name="celular" type="tel" inputmode="numeric" required maxlength="16" value="<?= $g('celular') ?>" placeholder="322 123 4567" autocomplete="tel-national"></label>
      </div>
    </fieldset>

    <fieldset class="addr-fs"><legend>Lo que necesitas</legend>
      <div>
        <span class="lbl-t">Productos de interés</span>
        <div class="chk-grid">
          <?php foreach (array_merge($cats, ['Otro']) as $x): ?>
            <label class="chk"><input type="checkbox" name="interes[]" value="<?= e($x) ?>" <?= in_array($x, (array)($v['interes'] ?? []), true) ? 'checked' : '' ?>> <?= e($x) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="two">
        <label>Cantidad aproximada
          <select name="cantidad" required>
            <option value="">Elige…</option>
            <?php foreach (MAYOREO_CANTIDADES as $x): ?><option <?= ($v['cantidad'] ?? '') === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>Fecha en que los necesitas <small class="hint">(opcional)</small><input type="date" name="fecha" value="<?= $g('fecha') ?>" min="<?= date('Y-m-d') ?>"></label>
      </div>
      <label>Comentarios <small class="hint">(opcional, máx. 600 caracteres)</small>
        <textarea name="comentarios" rows="4" maxlength="600" placeholder="Colores, medidas, personalización, logotipo, lugar de entrega…"><?= $g('comentarios') ?></textarea></label>
    </fieldset>

    <label class="chk"><input type="checkbox" name="acepto" value="1" required data-msg="Debes aceptar el Aviso de privacidad." <?= !empty($v['acepto']) ? 'checked' : '' ?>>
      <span>Acepto el <a href="<?= e(root_url('privacidad.php')) ?>" target="_blank">Aviso de privacidad</a> para que usen estos datos para cotizarme.</span></label>
    <div><button class="btn">Enviar solicitud</button></div>
  </form>
</div>
<?php page_foot();
