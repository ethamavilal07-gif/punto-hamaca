<?php
// Conexión MySQL (XAMPP por defecto: root sin contraseña). Crea BD, tablas y datos iniciales.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'punto_hamaca';
const DB_USER = 'root';
const DB_PASS = '';

// Colores oficiales del hilo de Punto Hamaca (nombre => hex aproximado de las fotos de muestra).
// Para ajustar un tono, cambia el hex aquí o edítalo por producto en el panel admin.
function paleta_oficial(): array {
    return [
        'Crudo/Natural' => '#ece8de', 'Beige oscuro' => '#c9c3b6', 'Capuchino' => '#786d6e', 'Camel' => '#c39d8c',
        'Mostaza' => '#e2bd52', 'Amarillo mango' => '#efc078',
        'Salmón' => '#f2a0a8', 'Rosa pastel' => '#f4a9cb', 'Rosa mexicano' => '#e8589a', 'Fucsia' => '#d6208b', 'Rojo vivo' => '#d0143c',
        'Vino' => '#5b1a43', 'Uva' => '#4b2f94', 'Lila' => '#a89be6',
        'Plumbago' => '#2c56a7', 'Azul rey' => '#1e4fc7', 'Azul marino' => '#1c2340',
        'Turquesa' => '#1099d6', 'Agua' => '#62c9de', 'Menta' => '#34bfd0',
        'Verde limón' => '#aee63b', 'Verde esmeralda' => '#17a566', 'Verde olivo' => '#5e5440', 'Verde oscuro' => '#1d4a48',
        'Plata' => '#aab7c8', 'Gris claro' => '#b5ae9f', 'Gris oscuro' => '#7b7f86',
        'Negro' => '#151515', 'Blanco' => '#fafafa',
    ];
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $boot = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    $boot->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    init_schema($pdo);
    return $pdo;
}

function init_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(50) NOT NULL UNIQUE,
        correo VARCHAR(120) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        rol ENUM('cliente','admin','superadmin') NOT NULL DEFAULT 'cliente',
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT,
        precio DECIMAL(10,2) NOT NULL DEFAULT 0,
        categoria VARCHAR(50) NOT NULL DEFAULT 'Hamacas',
        medidas VARCHAR(150) DEFAULT '',
        material VARCHAR(150) DEFAULT '',
        capacidad VARCHAR(60) DEFAULT '',
        colores TEXT,
        imagen VARCHAR(255) DEFAULT NULL,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS favoritos (
        usuario_id INT NOT NULL,
        producto_id INT NOT NULL,
        PRIMARY KEY (usuario_id, producto_id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    )");

    // Contador de inicios de sesión y bandera de contraseña inicial (para recordar cambiarla)
    $col = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'logins'")->fetch();
    if (!$col) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN logins INT NOT NULL DEFAULT 0, ADD COLUMN pass_inicial TINYINT(1) NOT NULL DEFAULT 0');
        $pdo->exec("UPDATE usuarios SET pass_inicial=1 WHERE usuario='admin' AND rol='admin'");
    }
    // 'stock' = entrega en 1-2 días · 'pedido' = se fabrica sobre pedido (10 días hábiles)
    if (!$pdo->query("SHOW COLUMNS FROM productos LIKE 'disponibilidad'")->fetch()) {
        $pdo->exec("ALTER TABLE productos ADD COLUMN disponibilidad VARCHAR(10) NOT NULL DEFAULT 'pedido'");
    }
    if (!$pdo->query("SHOW COLUMNS FROM productos LIKE 'mas_vendido'")->fetch()) {
        $pdo->exec('ALTER TABLE productos ADD COLUMN mas_vendido TINYINT(1) NOT NULL DEFAULT 0');
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS inspiraciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(120) NOT NULL DEFAULT '',
        imagen VARCHAR(255) NOT NULL,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    if (!$pdo->query("SHOW COLUMNS FROM inspiraciones LIKE 'descripcion'")->fetch()) {
        $pdo->exec('ALTER TABLE inspiraciones ADD COLUMN descripcion TEXT NULL, ADD COLUMN producto_id INT NULL,
            ADD CONSTRAINT fk_insp_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL');
    }
    if (!(int)$pdo->query('SELECT COUNT(*) FROM inspiraciones')->fetchColumn()) {
        $pdo->prepare('INSERT INTO inspiraciones (titulo, imagen, descripcion) VALUES (?,?,?)')
            ->execute(['Tapiz de macramé en sala', 'assets/imagenes/imagen-portada.jpg',
                'Un tapiz de macramé con flecos en tonos tierra convierte una pared sencilla en el centro de la sala. Combina con maderas, fibras naturales y mucha luz.']);
    }
    // Reseñas: una por persona y producto, con calificación 1-5, comentario de máx. 200 caracteres y hasta 3 fotos
    $pdo->exec("CREATE TABLE IF NOT EXISTS resenas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        usuario_id INT NOT NULL,
        calificacion TINYINT NOT NULL,
        comentario VARCHAR(200) NOT NULL DEFAULT '',
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_resena (producto_id, usuario_id),
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS resena_fotos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        resena_id INT NOT NULL,
        archivo VARCHAR(255) NOT NULL,
        FOREIGN KEY (resena_id) REFERENCES resenas(id) ON DELETE CASCADE
    )");
    // Rol "superadmin" (gestiona usuarios); en bases creadas antes hay que ampliar la lista de roles
    $rolCol = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch();
    if ($rolCol && strpos($rolCol['Type'], 'superadmin') === false) {
        $pdo->exec("ALTER TABLE usuarios MODIFY rol ENUM('cliente','admin','superadmin') NOT NULL DEFAULT 'cliente'");
    }
    // Nombre y apellido de la persona usuaria
    if (!$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'nombre'")->fetch()) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(60) NOT NULL DEFAULT '', ADD COLUMN apellido VARCHAR(60) NOT NULL DEFAULT ''");
    }
    // Celular (10 dígitos, México) para contactar al hacer un pedido
    if (!$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'celular'")->fetch()) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN celular VARCHAR(10) NOT NULL DEFAULT ''");
    }
    // Direcciones de entrega (una es la predeterminada)
    $pdo->exec("CREATE TABLE IF NOT EXISTS direcciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        recibe VARCHAR(120) NOT NULL,
        calle VARCHAR(150) NOT NULL,
        colonia VARCHAR(100) NOT NULL,
        ciudad VARCHAR(100) NOT NULL,
        estado VARCHAR(60) NOT NULL,
        cp CHAR(5) NOT NULL,
        referencias VARCHAR(200) NOT NULL DEFAULT '',
        predeterminada TINYINT(1) NOT NULL DEFAULT 0,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )");
    // Modelos que se pueden elegir al personalizar una hamaca (internamente 'colgaderas')
    $pdo->exec("CREATE TABLE IF NOT EXISTS colgaderas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(80) NOT NULL,
        descripcion VARCHAR(300) NOT NULL DEFAULT '',
        imagen VARCHAR(255) DEFAULT NULL,
        icono VARCHAR(20) NOT NULL DEFAULT 'hilo',
        activa TINYINT(1) NOT NULL DEFAULT 1,
        orden INT NOT NULL DEFAULT 0,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    if (!(int)$pdo->query('SELECT COUNT(*) FROM colgaderas')->fetchColumn()) {
        // Ejemplos para reemplazar desde el panel (Panel admin → Modelos)
        $st = $pdo->prepare('INSERT INTO colgaderas (nombre,descripcion,icono,orden) VALUES (?,?,?,?)');
        foreach ([
            ['Modelo 1', '', 'hilo', 1],
            ['Modelo 2', '', 'telar', 2],
            ['Modelo 3', '', 'macrame', 3],
            ['Modelo 4', '', 'borlas', 4],
        ] as $r) $st->execute($r);
    }
    // Solicitudes de cotización por mayoreo (empresas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS mayoreo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa VARCHAR(150) NOT NULL,
        giro VARCHAR(80) NOT NULL,
        contacto VARCHAR(100) NOT NULL,
        puesto VARCHAR(80) NOT NULL DEFAULT '',
        correo VARCHAR(120) NOT NULL,
        celular VARCHAR(10) NOT NULL,
        rfc VARCHAR(13) NOT NULL DEFAULT '',
        ciudad VARCHAR(100) NOT NULL,
        estado VARCHAR(60) NOT NULL,
        interes VARCHAR(255) NOT NULL,
        cantidad VARCHAR(40) NOT NULL,
        fecha DATE NULL,
        comentarios TEXT NULL,
        atendida TINYINT(1) NOT NULL DEFAULT 0,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Número exterior/interior separado de la calle (las direcciones anteriores lo tenían junto a la calle)
    if (!$pdo->query("SHOW COLUMNS FROM direcciones LIKE 'numero'")->fetch()) {
        $pdo->exec("ALTER TABLE direcciones ADD COLUMN numero VARCHAR(30) NOT NULL DEFAULT '' AFTER calle");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_intentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        usuario VARCHAR(120) NOT NULL,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (usuario), INDEX (ip)
    )");

    if (!(int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin'")->fetchColumn()) {
        $pdo->prepare("INSERT INTO usuarios (usuario,correo,password,rol,pass_inicial) VALUES (?,?,?,'admin',1)")
            ->execute(['admin', 'admin@punto-hamaca.mx', password_hash('hamaca2027', PASSWORD_DEFAULT)]);
    }
    if (!(int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn()) {
        $neg = ['nombre' => 'Negro', 'hex' => '#1f1f1f'];
        $cru = ['nombre' => 'Crudo', 'hex' => '#e8dcc4'];
        $mos = ['nombre' => 'Mostaza', 'hex' => '#c99a2e'];
        $ver = ['nombre' => 'Verde militar', 'hex' => '#5b6b57'];
        $azu = ['nombre' => 'Azul marino', 'hex' => '#2f4a6b'];
        $gri = ['nombre' => 'Gris', 'hex' => '#7c7f86'];
        $roj = ['nombre' => 'Terracota', 'hex' => '#b5523a'];
        $esm = ['nombre' => 'Esmeralda', 'hex' => '#2f6b57'];
        $seed = [
            ['Hamaca Matrimonial Flecos Negros', 'Hamaca tejida a mano con flecos largos. Ideal para terrazas y habitaciones. Fabricada en Puerto Vallarta.', 1450, 'Hamacas', '2.40 m x 1.60 m', 'Hilo de algodón', '250 kg', [$neg, $cru, $mos, $gri]],
            ['Hamaca Macramé Dorada', 'Hamaca con macramé artesanal en la orilla y acabado de flecos. Una pieza decorativa y cómoda.', 1890, 'Hamacas', '2.60 m x 1.70 m', 'Algodón y macramé', '250 kg', [$mos, $cru, $neg]],
            ['Hamaca Tipo Americana', 'Hamaca con barras de madera para mayor apertura. Perfecta para jardín o patio.', 1650, 'Hamacas', '2.30 m x 1.50 m', 'Nylon y madera', '200 kg', [$ver, $azu, $gri]],
            ['Hamaca Arcoíris', 'Colorida, alegre y resistente. Flecos multicolor sobre base crudo.', 1550, 'Hamacas', '2.40 m x 1.60 m', 'Hilo de algodón', '250 kg', [$cru, $roj, $esm]],
            ['Silla Columpio Macramé', 'Silla colgante de macramé con asiento redondo y flecos. Incluye cuerda de suspensión.', 1250, 'Columpios', 'Ø 80 cm · caída 1.5 m', 'Cuerda de algodón', '120 kg', [$cru, $neg]],
            ['Silla Hamaca con Borlas', 'Silla hamaca estilo yucateco con barra de madera y borlas negras.', 1350, 'Sillas', '1.10 m de alto', 'Algodón y madera', '130 kg', [$cru, $azu, $neg]],
            ['Tapiz Macramé Decorativo', 'Pieza de pared en macramé con flecos, para dar calidez a sala o recámara.', 890, 'Decoración', '1.00 m x 1.20 m', 'Hilo de algodón', '—', [$mos, $neg, $gri]],
            ['Lámpara de Cuerda Trenzada', 'Lámpara de piso con pantalla de fibra natural y base de cuerda trenzada.', 2400, 'Decoración', '1.40 m de alto', 'Fibra natural y cuerda', '—', [$cru, $neg]],
        ];
        $st = $pdo->prepare('INSERT INTO productos (nombre,descripcion,precio,categoria,medidas,material,capacidad,colores) VALUES (?,?,?,?,?,?,?,?)');
        foreach ($seed as $s) {
            $s[7] = json_encode($s[7], JSON_UNESCAPED_UNICODE);
            $st->execute($s);
        }
    }
}
