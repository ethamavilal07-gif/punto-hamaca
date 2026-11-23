<?php
require __DIR__ . '/includes/functions.php';

$pid = (int)($_REQUEST['id'] ?? 0);
$volver = 'producto.php?id=' . $pid . '#resenas';

// Visitante que quiere reseñar: se le lleva a crear su cuenta y después regresa al producto
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!user()) {
        flash('Crea una cuenta para dejar tu reseña.');
        $_SESSION['next'] = root_url($volver);
        redirect('registro.php');
    }
    redirect($volver);
}

if (!csrf_ok()) { flash('Sesión expirada, intenta de nuevo.', 'err'); redirect($volver); }
if (!user()) {
    flash('Crea una cuenta para dejar tu reseña.');
    $_SESSION['next'] = root_url($volver);
    redirect('registro.php');
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'eliminar') {
    $rid = (int)($_POST['resena_id'] ?? 0);
    $st = db()->prepare('SELECT * FROM resenas WHERE id=?');
    $st->execute([$rid]);
    $r = $st->fetch();
    // Solo la persona autora o un administrador pueden borrarla
    if ($r && ((int)$r['usuario_id'] === user()['id'] || is_admin())) {
        delete_review_files('r.id=?', [$rid]);
        db()->prepare('DELETE FROM resenas WHERE id=?')->execute([$rid]);
        flash('Reseña eliminada.');
    }
    redirect('producto.php?id=' . ($r ? (int)$r['producto_id'] : $pid) . '#resenas');
}

if ($accion !== 'publicar') redirect($volver);

$subidas = [];
try {
    $st = db()->prepare('SELECT 1 FROM productos WHERE id=?');
    $st->execute([$pid]);
    if (!$st->fetch()) throw new RuntimeException('Producto no encontrado.');

    $cal = (int)($_POST['calificacion'] ?? 0);
    if ($cal < 1 || $cal > 5) throw new RuntimeException('Elige una calificación de 1 a 5 estrellas.');

    $coment = trim(str_replace("\r", '', (string)($_POST['comentario'] ?? '')));
    if (mb_strlen($coment) > RESENA_MAX_CHARS) throw new RuntimeException('El comentario no puede pasar de ' . RESENA_MAX_CHARS . ' caracteres.');

    $ya = db()->prepare('SELECT 1 FROM resenas WHERE producto_id=? AND usuario_id=?');
    $ya->execute([$pid, user()['id']]);
    if ($ya->fetch()) throw new RuntimeException('Ya dejaste una reseña de este producto. Elimínala si quieres escribir otra.');

    $fotos = files_list($_FILES['fotos'] ?? null);
    if (count($fotos) > RESENA_MAX_FOTOS) throw new RuntimeException('Puedes subir máximo ' . RESENA_MAX_FOTOS . ' fotos.');
    foreach ($fotos as $f) $subidas[] = upload_image($f);

    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO resenas (producto_id, usuario_id, calificacion, comentario) VALUES (?,?,?,?)')
        ->execute([$pid, user()['id'], $cal, $coment]);
    $rid = (int)$pdo->lastInsertId();
    $fi = $pdo->prepare('INSERT INTO resena_fotos (resena_id, archivo) VALUES (?,?)');
    foreach ($subidas as $a) $fi->execute([$rid, $a]);
    $pdo->commit();
    flash('¡Gracias por tu reseña!');
} catch (RuntimeException | PDOException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    foreach ($subidas as $a) @unlink(__DIR__ . '/uploads/' . basename($a)); // no dejar fotos huérfanas
    flash($ex instanceof PDOException ? 'No se pudo guardar tu reseña.' : $ex->getMessage(), 'err');
}
redirect($volver);
