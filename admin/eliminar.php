<?php
require __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT imagen FROM productos WHERE id=?');
    $st->execute([$id]);
    if ($p = $st->fetch()) {
        delete_review_files('r.producto_id=?', [$id]); // fotos de las reseñas del producto
        db()->prepare('DELETE FROM productos WHERE id=?')->execute([$id]);
        if ($p['imagen']) @unlink(__DIR__ . '/../uploads/' . basename($p['imagen']));
        flash('Producto eliminado.');
    }
}
redirect('admin/');
