<?php
require __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); echo '{"error":"bad"}'; exit; }
$pid = (int)($_POST['id'] ?? 0);
$ex = db()->prepare('SELECT 1 FROM productos WHERE id=?');
$ex->execute([$pid]);
if (!$ex->fetch()) { http_response_code(404); echo '{"error":"nf"}'; exit; }

if (!user()) {
    // Visitante: se guarda solo en la sesión (se borra al cerrar el navegador)
    $g = array_map('intval', $_SESSION['guest_favs'] ?? []);
    if (in_array($pid, $g, true)) { $g = array_values(array_diff($g, [$pid])); $fav = false; }
    else { $g[] = $pid; $fav = true; }
    $_SESSION['guest_favs'] = array_slice($g, 0, 200);
    echo json_encode(['fav' => $fav]);
    exit;
}

$uid = user()['id'];
$st = db()->prepare('SELECT 1 FROM favoritos WHERE usuario_id=? AND producto_id=?');
$st->execute([$uid, $pid]);
if ($st->fetch()) {
    db()->prepare('DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?')->execute([$uid, $pid]);
    echo '{"fav":false}';
} else {
    db()->prepare('INSERT INTO favoritos (usuario_id,producto_id) VALUES (?,?)')->execute([$uid, $pid]);
    echo '{"fav":true}';
}
