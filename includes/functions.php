<?php
// ---- Seguridad base: sin errores visibles, cookie de sesión endurecida, cabeceras ----
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('ph_session');
// lifetime 0 = cookie de sesión: se borra al cerrar el navegador
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if ($https) header('Strict-Transport-Security: max-age=31536000');
set_exception_handler(function (Throwable $e) {
    error_log($e);
    http_response_code(500);
    echo 'Ocurrió un error. Intenta de nuevo más tarde.';
});
require_once __DIR__ . '/db.php';

const SITE_NAME = 'Punto Hamaca';
// Número de WhatsApp del negocio: es el ÚNICO lugar donde hay que cambiarlo.
// Formato: código de país + número, solo dígitos y sin "+" (México: 52 + 10 dígitos; WhatsApp suele usar 521 + 10 dígitos).
const WHATSAPP = '5213222747001';
// Dirección completa del sitio tal como la abrió la persona (ej. https://www.tudominio.mx/Punto-hamaca/).
// Se usa para los enlaces que viajan en el mensaje de WhatsApp.
function site_url(string $ruta = ''): string {
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) $host = 'localhost';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . $host . root_url($ruta);
}

// Número "bonito" para mostrar en pantalla (usa los últimos 10 dígitos): 322 156 7531
function whatsapp_display(): string {
    $d = substr(preg_replace('/\D/', '', WHATSAPP), -10);
    return substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6);
}
// Datos legales: completa estos valores antes de publicar la página
const LEGAL_TITULAR = 'Punto Hamaca';
const LEGAL_DOMICILIO = 'C. Juárez 189 A, Centro, Puerto Vallarta, Jalisco, C.P. 48300';
const LEGAL_EMAIL = '';            // correo para solicitudes de privacidad (ej. privacidad@tudominio.mx)
const LEGAL_ACTUALIZADO = '18 de noviembre de 2026';

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return '$' . number_format((float)$n, 2) . ' MXN'; }

function root_url(string $p = ''): string {
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (str_ends_with($dir, '/admin') || str_ends_with($dir, '/api')) $dir = dirname($dir);
    return rtrim($dir, '/') . '/' . ltrim($p, '/');
}
function redirect(string $p): never { header('Location: ' . root_url($p)); exit; }

function user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return in_array(user()['rol'] ?? '', ['admin', 'superadmin'], true); }
// Solo la cuenta superadmin gestiona usuarios (ver y restablecer contraseñas)
function is_super(): bool { return (user()['rol'] ?? '') === 'superadmin'; }
function require_login(): void {
    if (!user()) { $_SESSION['next'] = $_SERVER['REQUEST_URI']; redirect('login.php'); }
}
// Cualquiera que no sea administrador (sin sesión o cliente) es enviado al inicio
// Quien no sea superadmin (cliente o admin normal) es enviado fuera
function require_super(): void {
    if (!is_super()) redirect(is_admin() ? 'admin/' : 'index.php');
    header('Cache-Control: no-store');
}
function require_admin(): void {
    if (!is_admin()) redirect('index.php');
    header('Cache-Control: no-store');
}

// Solo rutas internas relativas (evita redirecciones abiertas como //sitio-malo.com)
function safe_next(?string $n): ?string {
    if (!$n || $n[0] !== '/' || str_starts_with($n, '//') || str_contains($n, '\\') || preg_match('/[\r\n]/', $n)) return null;
    return $n;
}

// Limita intentos de login: 5 fallos por usuario o 15 por IP en 15 minutos
function login_blocked(string $id): bool {
    $pdo = db();
    $pdo->exec('DELETE FROM login_intentos WHERE creado < (NOW() - INTERVAL 1 DAY)');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $st = $pdo->prepare('SELECT SUM(usuario=?) u, COUNT(*) t FROM login_intentos WHERE ip=? AND creado > (NOW() - INTERVAL 15 MINUTE)');
    $st->execute([mb_strtolower($id), $ip]);
    $r = $st->fetch();
    $st2 = $pdo->prepare('SELECT COUNT(*) FROM login_intentos WHERE usuario=? AND creado > (NOW() - INTERVAL 15 MINUTE)');
    $st2->execute([mb_strtolower($id)]);
    return (int)$st2->fetchColumn() >= 5 || (int)$r['t'] >= 15;
}
function login_fail(string $id): void {
    db()->prepare('INSERT INTO login_intentos (ip, usuario) VALUES (?,?)')->execute([$_SERVER['REMOTE_ADDR'] ?? '', mb_strtolower($id)]);
}
function login_ok(string $id): void {
    db()->prepare('DELETE FROM login_intentos WHERE usuario=?')->execute([mb_strtolower($id)]);
}

// ---- Celular (México, 10 dígitos) ----
// Devuelve los 10 dígitos, '' si viene vacío, o null si no es válido. Acepta +52, espacios y guiones.
function celular_normalizar(string $in): ?string {
    $d = preg_replace('/\D/', '', $in);
    if ($d === '') return '';
    if (strlen($d) === 12 && str_starts_with($d, '52')) $d = substr($d, 2);
    elseif (strlen($d) === 13 && str_starts_with($d, '521')) $d = substr($d, 3);
    return preg_match('/^\d{10}$/', $d) ? $d : null;
}
function celular_texto(string $d): string { return '+52 ' . $d; }

// ---- Direcciones de entrega ----
const MAX_DIRECCIONES = 10;
function estados_mx(): array {
    return ['Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango',
        'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro',
        'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'];
}

// Valida los datos de una dirección. Devuelve [datos limpios, mensaje de error o null]
function dir_validar(array $in): array {
    $lim = ['recibe' => 120, 'calle' => 150, 'numero' => 30, 'colonia' => 100, 'ciudad' => 100, 'referencias' => 200];
    foreach (array_keys($lim) as $k) {
        if (!mb_check_encoding((string)($in[$k] ?? ''), 'UTF-8')) return [[], 'Hay caracteres no válidos en la dirección.'];
    }
    $c = [];
    foreach ($lim as $k => $n) $c[$k] = mb_substr(trim(preg_replace('/\s+/', ' ', (string)($in[$k] ?? ''))), 0, $n);
    $c['estado'] = trim((string)($in['estado'] ?? ''));
    $c['cp'] = preg_replace('/\D/', '', (string)($in['cp'] ?? ''));
    $err = null;
    if ($c['recibe'] === '') $err = 'Escribe el nombre de quien recibe.';
    elseif ($c['calle'] === '') $err = 'Escribe la calle.';
    elseif ($c['numero'] === '') $err = 'Escribe el número (o S/N si no tiene).';
    elseif ($c['colonia'] === '') $err = 'Escribe la colonia.';
    elseif ($c['ciudad'] === '') $err = 'Escribe la ciudad o municipio.';
    elseif (!in_array($c['estado'], estados_mx(), true)) $err = 'Elige un estado.';
    elseif (!preg_match('/^\d{5}$/', $c['cp'])) $err = 'El código postal debe tener 5 dígitos.';
    return [$c, $err];
}

function dir_texto(array $d): string {
    $t = trim($d['calle'] . ' ' . ($d['numero'] ?? '')) . ', Col. ' . $d['colonia'] . ', ' . $d['ciudad'] . ', ' . $d['estado'] . ', C.P. ' . $d['cp'];
    return $d['referencias'] !== '' ? $t . ' (Ref.: ' . $d['referencias'] . ')' : $t;
}

// Guarda una dirección; la primera siempre queda como predeterminada. Devuelve su id.
function dir_guardar(int $uid, array $c, bool $predeterminada = false): int {
    $pdo = db();
    $n = $pdo->prepare('SELECT COUNT(*) FROM direcciones WHERE usuario_id=?');
    $n->execute([$uid]);
    $total = (int)$n->fetchColumn();
    if ($total >= MAX_DIRECCIONES) throw new RuntimeException('Puedes guardar máximo ' . MAX_DIRECCIONES . ' direcciones.');
    $def = $total === 0 || $predeterminada;
    if ($def) $pdo->prepare('UPDATE direcciones SET predeterminada=0 WHERE usuario_id=?')->execute([$uid]);
    $pdo->prepare('INSERT INTO direcciones (usuario_id,recibe,calle,numero,colonia,ciudad,estado,cp,referencias,predeterminada) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$uid, $c['recibe'], $c['calle'], $c['numero'], $c['colonia'], $c['ciudad'], $c['estado'], $c['cp'], $c['referencias'], $def ? 1 : 0]);
    return (int)$pdo->lastInsertId();
}

function dir_lista(int $uid): array {
    $st = db()->prepare('SELECT * FROM direcciones WHERE usuario_id=? ORDER BY predeterminada DESC, id DESC');
    $st->execute([$uid]);
    return $st->fetchAll();
}

// Campos del formulario de dirección (se usa en registro, pedido y "Mis direcciones")
function dir_campos(array $v = [], string $pref = '', bool $conRecibe = true): void {
    $g = fn($k) => e($v[$k] ?? '');
    ?>
    <?php if ($conRecibe): ?><label>Nombre de quien recibe<input name="<?= $pref ?>recibe" maxlength="120" required value="<?= $g('recibe') ?>" autocomplete="name"></label><?php endif; ?>
    <div class="two">
      <label>Calle<input name="<?= $pref ?>calle" maxlength="150" required value="<?= $g('calle') ?>" placeholder="Juárez" autocomplete="address-line1"></label>
      <label>Número<input name="<?= $pref ?>numero" maxlength="30" required value="<?= $g('numero') ?>" placeholder="189 A (o S/N)"></label>
    </div>
    <div class="two">
      <label>Colonia<input name="<?= $pref ?>colonia" maxlength="100" required value="<?= $g('colonia') ?>" autocomplete="address-line2"></label>
      <label>Código postal<input name="<?= $pref ?>cp" maxlength="5" inputmode="numeric" pattern="\d{5}" required value="<?= $g('cp') ?>" autocomplete="postal-code"></label>
    </div>
    <div class="two">
      <label>Ciudad o municipio<input name="<?= $pref ?>ciudad" maxlength="100" required value="<?= $g('ciudad') ?>" autocomplete="address-level2"></label>
      <label>Estado
        <select name="<?= $pref ?>estado" required>
          <option value="">Elige…</option>
          <?php foreach (estados_mx() as $s): ?><option <?= ($v['estado'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
      </label>
    </div>
    <label>Referencias (opcional)<input name="<?= $pref ?>referencias" maxlength="200" value="<?= $g('referencias') ?>" placeholder="Entre calles, color de la casa…"></label>
    <?php
}

// ---- Reseñas ----
const RESENA_MAX_CHARS = 200;
const RESENA_MAX_FOTOS = 3;

// Estrellas con relleno parcial (ej. 4.3 de 5)
function stars_html(float $r): string {
    return '<span class="stars" style="--r:' . number_format(max(0, min(5, $r)), 2, '.', '') . '" aria-hidden="true">★★★★★</span>';
}

// Convierte $_FILES['fotos'] (formato por columnas) en una lista de archivos
function files_list(?array $f): array {
    if (!$f || !is_array($f['name'] ?? null)) return [];
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $name, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

// Borra del disco las fotos de reseñas que cumplan la condición (antes de borrar el producto o la cuenta)
function delete_review_files(string $where, array $args): void {
    $st = db()->prepare("SELECT f.archivo FROM resena_fotos f JOIN resenas r ON r.id=f.resena_id WHERE $where");
    $st->execute($args);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $a) @unlink(__DIR__ . '/../uploads/' . basename($a));
}

// ---- Colgaderas (solo para hamacas) ----
function es_hamaca(array $p): bool { return ($p['categoria'] ?? '') === 'Hamacas'; }
function colgaderas_activas(): array {
    return db()->query('SELECT * FROM colgaderas WHERE activa=1 ORDER BY orden, id')->fetchAll();
}
// Devuelve la colgadera activa con ese id (solo si el producto es hamaca) o null
function colgadera_por_id(int $id, array $p): ?array {
    if ($id <= 0 || !es_hamaca($p)) return null;
    $st = db()->prepare('SELECT * FROM colgaderas WHERE id=? AND activa=1');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
// Ilustración cuando la colgadera no tiene foto
function colgadera_svg(string $icono = 'hilo'): string {
    $c = '#8a6a4a';
    $base = '<svg class="hsvg" viewBox="0 0 200 200" aria-hidden="true"><rect x="40" y="26" width="120" height="8" rx="4" fill="#5b4a38"/>';
    switch ($icono) {
        case 'telar':
            $b = '<path d="M70 34 V160 M130 34 V160" stroke="' . $c . '" stroke-width="22" stroke-linecap="butt" opacity=".9"/>'
               . '<path d="M59 50 H81 M59 66 H81 M59 82 H81 M59 98 H81 M59 114 H81 M59 130 H81 M59 146 H81 M119 50 H141 M119 66 H141 M119 82 H141 M119 98 H141 M119 114 H141 M119 130 H141 M119 146 H141" stroke="#e8dcc4" stroke-width="3"/>';
            break;
        case 'macrame':
            $b = '<path d="M70 34 V164 M130 34 V164" stroke="' . $c . '" stroke-width="8" stroke-linecap="round"/>'
               . '<path d="M70 52 L100 76 L130 52 M70 84 L100 108 L130 84 M70 116 L100 140 L130 116" stroke="' . $c . '" stroke-width="5" fill="none" stroke-linecap="round"/>'
               . '<circle cx="100" cy="76" r="6" fill="#e8dcc4" stroke="' . $c . '" stroke-width="3"/><circle cx="100" cy="108" r="6" fill="#e8dcc4" stroke="' . $c . '" stroke-width="3"/><circle cx="100" cy="140" r="6" fill="#e8dcc4" stroke="' . $c . '" stroke-width="3"/>';
            break;
        case 'borlas':
            $b = '<path d="M70 34 V140 M130 34 V140" stroke="' . $c . '" stroke-width="8" stroke-linecap="round"/>'
               . '<path d="M70 140 l-12 36 M70 140 l0 40 M70 140 l12 36 M130 140 l-12 36 M130 140 l0 40 M130 140 l12 36" stroke="' . $c . '" stroke-width="5" stroke-linecap="round"/>'
               . '<circle cx="70" cy="140" r="9" fill="#e8dcc4" stroke="' . $c . '" stroke-width="3"/><circle cx="130" cy="140" r="9" fill="#e8dcc4" stroke="' . $c . '" stroke-width="3"/>';
            break;
        default: // hilo
            $b = '<path d="M62 34 V164 M70 34 V164 M78 34 V164 M122 34 V164 M130 34 V164 M138 34 V164" stroke="' . $c . '" stroke-width="5" stroke-linecap="round"/>';
    }
    return $base . $b . '</svg>';
}
function colgadera_media(array $c): string {
    if (!empty($c['imagen'])) return '<img src="' . e(root_url('uploads/' . $c['imagen'])) . '" alt="' . e($c['nombre']) . '">';
    return colgadera_svg($c['icono']);
}

// ---- Carrito (vive en la sesión; el pedido se confirma por WhatsApp) ----
function cart_items(): array {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];
    $ids = array_values(array_unique(array_map(fn($c) => (int)$c['id'], $cart)));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM productos WHERE id IN ($in)");
    $st->execute($ids);
    $prods = [];
    foreach ($st->fetchAll() as $p) $prods[(int)$p['id']] = $p;
    $out = [];
    foreach ($cart as $key => $c) {
        $p = $prods[(int)$c['id']] ?? null;
        if (!$p) { unset($_SESSION['cart'][$key]); continue; } // producto eliminado
        $out[] = ['key' => $key, 'p' => $p, 'color' => $c['color'], 'qty' => (int)$c['qty'], 'colg' => colgadera_por_id((int)($c['colg'] ?? 0), $p)];
    }
    return $out;
}
function cart_count(): int {
    return array_sum(array_map(fn($c) => (int)$c['qty'], $_SESSION['cart'] ?? []));
}

// Los "Me gusta" de visitantes viven solo en la sesión (se borran al cerrar el navegador)
function merge_guest_favs(int $uid): void {
    $ids = $_SESSION['guest_favs'] ?? [];
    $st = db()->prepare('INSERT IGNORE INTO favoritos (usuario_id, producto_id) SELECT ?, id FROM productos WHERE id=?');
    foreach ($ids as $pid) $st->execute([$uid, (int)$pid]);
    unset($_SESSION['guest_favs']);
}

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok(): bool {
    $s = $_SESSION['csrf'] ?? '';
    return $s !== '' && hash_equals($s, (string)($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '')));
}

function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// Categorías donde no aplica la "Capacidad de peso"
const CATS_SIN_CAPACIDAD = ['Accesorios', 'Lámparas'];
function sin_capacidad(string $cat): bool { return in_array($cat, CATS_SIN_CAPACIDAD, true); }

function colores(array $p): array { return json_decode($p['colores'] ?? '[]', true) ?: []; }

function fav_ids(): array {
    if (!user()) return array_map('intval', $_SESSION['guest_favs'] ?? []);
    $st = db()->prepare('SELECT producto_id FROM favoritos WHERE usuario_id=?');
    $st->execute([user()['id']]);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

// Ilustración que cambia de color (se usa cuando el producto no tiene foto)
function hamaca_svg(string $hex = '#c99a2e', string $cat = 'Hamacas'): string {
    $h = e($hex);
    if ($cat === 'Columpios' || $cat === 'Sillas') {
        return '<svg class="hsvg" viewBox="0 0 200 200" aria-hidden="true"><path d="M100 10 L55 110 M100 10 L145 110" stroke="#8a7a62" stroke-width="3" fill="none"/>'
            . '<ellipse cx="100" cy="125" rx="52" ry="18" fill="' . $h . '"/><path d="M48 125 Q100 185 152 125" fill="' . $h . '" opacity=".85"/>'
            . '<path d="M62 140 v26 M78 148 v28 M100 152 v30 M122 148 v28 M138 140 v26" stroke="' . $h . '" stroke-width="5" stroke-linecap="round"/></svg>';
    }
    if ($cat === 'Decoración') {
        return '<svg class="hsvg" viewBox="0 0 200 200" aria-hidden="true"><rect x="30" y="35" width="140" height="6" rx="3" fill="#8a7a62"/>'
            . '<path d="M40 41 Q100 120 160 41 Z" fill="' . $h . '"/><path d="M50 60 v95 M65 70 v100 M80 78 v105 M95 82 v108 M110 82 v108 M125 78 v105 M140 70 v100 M155 60 v95" stroke="' . $h . '" stroke-width="5" stroke-linecap="round"/></svg>';
    }
    return '<svg class="hsvg" viewBox="0 0 200 200" aria-hidden="true"><path d="M20 40 V150 M180 40 V150" stroke="#8a7a62" stroke-width="6" stroke-linecap="round"/>'
        . '<path d="M22 70 Q100 150 178 70 L178 88 Q100 168 22 88 Z" fill="' . $h . '"/>'
        . '<path d="M40 112 v34 M56 122 v34 M72 128 v36 M88 132 v38 M104 132 v38 M120 130 v36 M136 124 v34 M152 116 v32" stroke="' . $h . '" stroke-width="5" stroke-linecap="round"/></svg>';
}

function product_media(array $p, ?string $hex = null): string {
    if (!empty($p['imagen'])) {
        return '<img src="' . e(root_url('uploads/' . $p['imagen'])) . '" alt="' . e($p['nombre']) . '" loading="lazy">';
    }
    $c = colores($p);
    return hamaca_svg($hex ?? ($c[0]['hex'] ?? '#c99a2e'), $p['categoria']);
}

function product_card(array $p, array $favs): string {
    $fav = in_array((int)$p['id'], $favs, true);
    $url = e(root_url('producto.php?id=' . $p['id']));
    $out = '<article class="card"><div class="card-media"><a href="' . $url . '">' . product_media($p) . '</a>'
        . '<button class="heart' . ($fav ? ' on' : '') . '" data-id="' . (int)$p['id'] . '" aria-label="Me gusta" title="Guardar en Me gusta">&#10084;</button></div>'
        . '<div class="card-body"><span class="tag">' . e($p['categoria']) . '</span>'
        . '<h3><a href="' . $url . '">' . e($p['nombre']) . '</a></h3><div class="dots">';
    $cols = colores($p);
    foreach (array_slice($cols, 0, 5) as $col) { // máximo 5 colores en la tarjeta
        $out .= '<i style="background:' . e($col['hex']) . '" title="' . e($col['nombre']) . '"></i>';
    }
    if (count($cols) > 5) $out .= '<small class="more">+' . (count($cols) - 5) . '</small>';
    return $out . '</div><p class="price">' . money($p['precio']) . '</p></div></article>';
}

// Las fotos de inspiración pueden venir de assets/ (iniciales) o de uploads/ (subidas por el admin)
function insp_url(string $img): string {
    return root_url(str_starts_with($img, 'assets/') ? $img : 'uploads/' . $img);
}

function upload_image(array $f): ?string {
    if (empty($f['name']) || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024) throw new RuntimeException('La imagen no se pudo subir (máx. 5 MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Formato no válido. Usa JPG, PNG o WEBP.');
    if (@getimagesize($f['tmp_name']) === false) throw new RuntimeException('El archivo no es una imagen válida.');
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
    return $name;
}

function page_head(string $title, string $active = ''): void {
    $u = user();
    $f = flash();
    $nav = ['index.php' => 'Inicio', 'tienda.php' => 'Catálogo', 'inspiraciones.php' => 'Inspiraciones', 'index.php#nosotros' => 'Nosotros'];
    $nFav = count(fav_ids());
    $nCart = cart_count();
    ?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · <?= SITE_NAME ?></title>
<?php if (!empty($GLOBALS['og'])): $og = $GLOBALS['og']; // vista previa al compartir el enlace (WhatsApp, redes) ?>
<meta property="og:type" content="product">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($og['title']) ?>">
<meta property="og:description" content="<?= e($og['description']) ?>">
<meta property="og:image" content="<?= e($og['image']) ?>">
<meta property="og:url" content="<?= e($og['url']) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<link rel="icon" href="<?= e(root_url('assets/imagenes/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(root_url('assets/css/style.css')) ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>"></head>
<body class="<?= $active === 'index.php' ? 'home' : '' ?>" data-csrf="<?= e(csrf()) ?>" data-logged="<?= $u ? 1 : 0 ?>" data-root="<?= e(root_url()) ?>">
<div class="topbar">Fabricantes · Calidad y precios accesibles · Envíos a toda la República MX</div>
<header class="site-header"><div class="wrap nav">
  <a class="brand" href="<?= e(root_url()) ?>"><img src="<?= e(root_url('assets/imagenes/logo.png')) ?>" alt=""><span>Punto Hamaca</span></a>
  <button class="burger" aria-label="Menú" type="button">&#9776;</button>
  <div class="menu-backdrop"></div>
  <nav class="menu">
    <button class="menu-close" type="button" aria-label="Cerrar menú">&times;</button>
    <?php foreach ($nav as $href => $label): ?><a href="<?= e(root_url($href)) ?>" class="<?= $active === $href ? 'act' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
    <a href="<?= e(root_url('favoritos.php')) ?>" class="icon-link <?= $active === 'fav' ? 'act' : '' ?>" aria-label="Me gusta" title="Me gusta">
      <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.2-9.4C1.6 7.700 3.700 4.500 7 4.500c2 0 3.400 1 5 3 1.600-2 3-3 5-3 3.300 0 5.400 3.200 4.200 6.600-1.700 4.800-9.200 9.400-9.200 9.400z"/></svg>
      <span class="badge" id="favcount"<?= $nFav ? '' : ' hidden' ?>><?= $nFav ?></span><span class="lbl">Me gusta</span>
    </a>
    <a href="<?= e(root_url('carrito.php')) ?>" class="icon-link <?= $active === 'cart' ? 'act' : '' ?>" aria-label="Carrito de compras" title="Carrito">
      <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.500 8h13l1.200 11.200a1.500 1.500 0 0 1-1.500 1.800H5.800a1.500 1.500 0 0 1-1.500-1.800z"/><path d="M8.500 8V6.500a3.500 3.500 0 0 1 7 0V8"/></svg>
      <span class="badge" id="cartcount"<?= $nCart ? '' : ' hidden' ?>><?= $nCart ?></span><span class="lbl">Carrito</span>
    </a>
    <?php if ($u): ?>
      <a href="<?= e(root_url('cuenta.php')) ?>">Mi cuenta</a>
      <?php if (is_admin()): ?><a href="<?= e(root_url('admin/')) ?>" class="pill">Panel admin</a><?php endif; ?>
      <a href="<?= e(root_url('logout.php')) ?>">Salir</a>
    <?php else: ?>
      <a href="<?= e(root_url('login.php')) ?>" class="pill">Ingresar</a>
    <?php endif; ?>
  </nav></div></header>
<div class="toasts" id="toasts"><?php if ($f): ?><div class="flash <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif; ?></div>
<main><?php
}

function page_foot(): void { ?></main>
<footer class="site-footer"><div class="wrap cols">
  <div><h4>Punto Hamaca</h4><p>Somos fabricantes. Calidad y precios accesibles.</p></div>
  <div><h4>Visítanos</h4><p>C. Juárez 189 A, Centro,<br>Puerto Vallarta, Jalisco 48300</p></div>
  <div><h4>Contacto</h4><p><a href="https://wa.me/<?= WHATSAPP ?>" target="_blank" rel="noopener">WhatsApp</a><br><a href="https://instagram.com/hamacaspv" target="_blank" rel="noopener">@hamacaspv</a></p></div>
</div><div class="copy">© <?= date('Y') ?> Punto Hamaca ·
  <a href="<?= e(root_url('privacidad.php')) ?>">Aviso de privacidad</a> ·
  <a href="<?= e(root_url('politicas-compra.php')) ?>">Políticas de compra</a> ·
  <a href="<?= e(root_url('terminos.php')) ?>">Términos y condiciones</a> ·
  <a href="<?= e(root_url('cookies.php')) ?>">Política de cookies</a></div></footer>
<div id="cookiebar" class="cookiebar" hidden>
  <p>Este sitio usa únicamente una <b>cookie técnica de sesión</b>, necesaria para que funcionen el inicio de sesión y tus Me gusta. No usamos cookies de publicidad ni de rastreo. <a href="<?= e(root_url('cookies.php')) ?>">Más información</a></p>
  <button type="button" class="btn small light" id="cookieok">Entendido</button>
</div>
<script src="<?= e(root_url('assets/js/app.js')) ?>?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script></body></html>
<?php }
