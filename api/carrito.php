<?php
require __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); echo '{"error":"bad"}'; exit; }

$pid = (int)($_POST['id'] ?? 0);
$qty = max(1, min(20, (int)($_POST['qty'] ?? 1)));
$st = db()->prepare('SELECT * FROM productos WHERE id=?');
$st->execute([$pid]);
$p = $st->fetch();
if (!$p) { http_response_code(404); echo '{"error":"nf"}'; exit; }

// El color debe ser uno de los disponibles del producto
$color = trim($_POST['color'] ?? '');
$nombres = array_column(colores($p), 'nombre');
if ($nombres && !in_array($color, $nombres, true)) $color = $nombres[0];
if (!$nombres) $color = '';

// Colgadera (solo hamacas): debe ser una de las activas
$cg = colgadera_por_id((int)($_POST['colg'] ?? 0), $p);
$colgId = $cg ? (int)$cg['id'] : 0;

$key = $pid . '|' . $color . '|' . $colgId;
$cart = $_SESSION['cart'] ?? [];
$cart[$key] = ['id' => $pid, 'color' => $color, 'colg' => $colgId, 'qty' => min(20, ($cart[$key]['qty'] ?? 0) + $qty)];
if (count($cart) > 50) { http_response_code(400); echo '{"error":"full"}'; exit; }
$_SESSION['cart'] = $cart;
echo json_encode(['count' => cart_count(), 'name' => $p['nombre']]);
