-- Punto Hamaca: base de datos limpia (estructura + catálogo, sin datos personales).
-- Importar en phpMyAdmin: crear/elegir la base 'punto_hamaca' y usar Importar.
-- Si la base queda sin administrador, el sitio crea uno solo al abrirse por primera vez (admin / hamaca2027): cambia esa contraseña.

CREATE DATABASE IF NOT EXISTS punto_hamaca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE punto_hamaca;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `colgaderas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `colgaderas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(300) NOT NULL DEFAULT '',
  `imagen` varchar(255) DEFAULT NULL,
  `icono` varchar(20) NOT NULL DEFAULT 'hilo',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `direcciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direcciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `recibe` varchar(120) NOT NULL,
  `calle` varchar(150) NOT NULL,
  `numero` varchar(30) NOT NULL DEFAULT '',
  `colonia` varchar(100) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `estado` varchar(60) NOT NULL,
  `cp` char(5) NOT NULL,
  `referencias` varchar(200) NOT NULL DEFAULT '',
  `predeterminada` tinyint(1) NOT NULL DEFAULT 0,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `direcciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `favoritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favoritos` (
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  PRIMARY KEY (`usuario_id`,`producto_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspiraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inspiraciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) NOT NULL DEFAULT '',
  `imagen` varchar(255) NOT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  `descripcion` text DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_insp_prod` (`producto_id`),
  CONSTRAINT `fk_insp_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_intentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_intentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `usuario` varchar(120) NOT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario` (`usuario`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mayoreo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mayoreo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa` varchar(150) NOT NULL,
  `giro` varchar(80) NOT NULL,
  `contacto` varchar(100) NOT NULL,
  `puesto` varchar(80) NOT NULL DEFAULT '',
  `correo` varchar(120) NOT NULL,
  `celular` varchar(10) NOT NULL,
  `rfc` varchar(13) NOT NULL DEFAULT '',
  `ciudad` varchar(100) NOT NULL,
  `estado` varchar(60) NOT NULL,
  `interes` varchar(255) NOT NULL,
  `cantidad` varchar(40) NOT NULL,
  `fecha` date DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `atendida` tinyint(1) NOT NULL DEFAULT 0,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `categoria` varchar(50) NOT NULL DEFAULT 'Hamacas',
  `medidas` varchar(150) DEFAULT '',
  `material` varchar(150) DEFAULT '',
  `capacidad` varchar(60) DEFAULT '',
  `colores` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  `mas_vendido` tinyint(1) NOT NULL DEFAULT 0,
  `disponibilidad` varchar(10) NOT NULL DEFAULT 'pedido',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resena_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resena_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resena_id` int(11) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resena_id` (`resena_id`),
  CONSTRAINT `resena_fotos_ibfk_1` FOREIGN KEY (`resena_id`) REFERENCES `resenas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resenas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `calificacion` tinyint(4) NOT NULL,
  `comentario` varchar(200) NOT NULL DEFAULT '',
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resena` (`producto_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('cliente','admin','superadmin') NOT NULL DEFAULT 'cliente',
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  `logins` int(11) NOT NULL DEFAULT 0,
  `pass_inicial` tinyint(1) NOT NULL DEFAULT 0,
  `nombre` varchar(60) NOT NULL DEFAULT '',
  `apellido` varchar(60) NOT NULL DEFAULT '',
  `celular` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Datos del catálogo

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `categoria`, `medidas`, `material`, `capacidad`, `colores`, `imagen`, `creado`, `mas_vendido`, `disponibilidad`) VALUES (1,'Hamaca Matrimonial Flecos Negros','Hamaca tejida a mano con flecos largos. Ideal para terrazas y habitaciones. Fabricada en Puerto Vallarta.',1450.00,'Hamacas','Largo 2.20 m + 80 cm de colgadera por lado · Ancho matrimonial (muy amplia)','Hilo de algodón de 3 mm','250 kg','[{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(2,'Hamaca Macramé Dorada','Hamaca con macramé artesanal en la orilla y acabado de flecos. Una pieza decorativa y cómoda.',1890.00,'Hamacas','Largo 2.20 m + 80 cm de colgadera por lado · Ancho matrimonial (muy amplia)','Hilo de algodón de 3 mm','250 kg','[{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(3,'Hamaca Tipo Americana','Hamaca con barras de madera para mayor apertura. Perfecta para jardín o patio.',1650.00,'Hamacas','Largo 2.20 m + 80 cm de colgadera por lado · Ancho matrimonial (muy amplia)','Hilo de algodón de 3 mm','200 kg','[{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(4,'Hamaca Arcoíris','Colorida, alegre y resistente. Flecos multicolor sobre base crudo.',1550.00,'Hamacas','Largo 2.20 m + 80 cm de colgadera por lado · Ancho matrimonial (muy amplia)','Hilo de algodón de 3 mm','250 kg','[{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(5,'Silla Columpio Macramé','Silla colgante de macramé con asiento redondo y flecos. Incluye cuerda de suspensión.',1250.00,'Columpios','Ø 80 cm · caída 1.5 m','Cuerda de algodón','120 kg','[{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(6,'Silla Hamaca con Borlas','Silla hamaca estilo yucateco con barra de madera y borlas negras.',1350.00,'Sillas','1.10 m de alto','Algodón y madera','130 kg','[{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(7,'Tapiz Macramé Decorativo','Pieza de pared en macramé con flecos, para dar calidez a sala o recámara.',890.00,'Decoración','1.00 m x 1.20 m','Hilo de algodón','—','[{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-17 18:22:42',0,'pedido'),(8,'Lámpara de Piso de Cuerda Trenzada','Lámpara de piso con base de cuerda gruesa trenzada, flecos en la parte baja y pantalla de fibra natural. Una pieza de diseño que da calidez a recibidores, salas y pasillos.',2400.00,'Lámparas','Aprox. 1.60 m de alto','Cuerda trenzada y fibra natural','','[{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]','lampara-piso-trenzada.webp','2026-11-17 18:22:42',0,'pedido'),(10,'Llavero de Macramé','Llavero tejido a mano en macramé con aro metálico, cuentas turquesa y detalle de estrella de mar. Perfecto para regalo o recuerdo.',120.00,'Accesorios','Aprox. 18 cm de largo','Hilo de algodón, aro metálico y cuentas','','[{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]','llavero-macrame.png','2026-11-19 02:18:55',0,'pedido'),(11,'Lámpara Colgante de Flecos','Ideal para transmitir esa esencia de frescura y elegancia en cada uno de tus espacios.\r\n¿Y tú en qué área la usarías?',2800.00,'Lámparas','Aprox. 60 cm de diámetro','Hilo de algodón en flecos','','[{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]','lampara-colgante-flecos.webp','2026-11-19 02:18:55',0,'pedido'),(12,'Hamaca Coco (telar)','Hamaca de telar modelo Coco. A diferencia del resto, su ancho sí se puede medir.',1200.00,'Hamacas','Largo 2.10 m + 80 cm de colgadera por lado (telar) · Ancho 1.20 m','Telar','','[{\"nombre\":\"Crudo\\/Natural\",\"hex\":\"#ece8de\"},{\"nombre\":\"Beige oscuro\",\"hex\":\"#c9c3b6\"},{\"nombre\":\"Capuchino\",\"hex\":\"#786d6e\"},{\"nombre\":\"Camel\",\"hex\":\"#c39d8c\"},{\"nombre\":\"Mostaza\",\"hex\":\"#e2bd52\"},{\"nombre\":\"Amarillo mango\",\"hex\":\"#efc078\"},{\"nombre\":\"Salmón\",\"hex\":\"#f2a0a8\"},{\"nombre\":\"Rosa pastel\",\"hex\":\"#f4a9cb\"},{\"nombre\":\"Rosa mexicano\",\"hex\":\"#e8589a\"},{\"nombre\":\"Fucsia\",\"hex\":\"#d6208b\"},{\"nombre\":\"Rojo vivo\",\"hex\":\"#d0143c\"},{\"nombre\":\"Vino\",\"hex\":\"#5b1a43\"},{\"nombre\":\"Uva\",\"hex\":\"#4b2f94\"},{\"nombre\":\"Lila\",\"hex\":\"#a89be6\"},{\"nombre\":\"Plumbago\",\"hex\":\"#2c56a7\"},{\"nombre\":\"Azul rey\",\"hex\":\"#1e4fc7\"},{\"nombre\":\"Azul marino\",\"hex\":\"#1c2340\"},{\"nombre\":\"Turquesa\",\"hex\":\"#1099d6\"},{\"nombre\":\"Agua\",\"hex\":\"#62c9de\"},{\"nombre\":\"Menta\",\"hex\":\"#34bfd0\"},{\"nombre\":\"Verde limón\",\"hex\":\"#aee63b\"},{\"nombre\":\"Verde esmeralda\",\"hex\":\"#17a566\"},{\"nombre\":\"Verde olivo\",\"hex\":\"#5e5440\"},{\"nombre\":\"Verde oscuro\",\"hex\":\"#1d4a48\"},{\"nombre\":\"Plata\",\"hex\":\"#aab7c8\"},{\"nombre\":\"Gris claro\",\"hex\":\"#b5ae9f\"},{\"nombre\":\"Gris oscuro\",\"hex\":\"#7b7f86\"},{\"nombre\":\"Negro\",\"hex\":\"#151515\"},{\"nombre\":\"Blanco\",\"hex\":\"#fafafa\"}]',NULL,'2026-11-20 17:44:24',0,'pedido');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `colgaderas` WRITE;
/*!40000 ALTER TABLE `colgaderas` DISABLE KEYS */;
INSERT INTO `colgaderas` (`id`, `nombre`, `descripcion`, `imagen`, `icono`, `activa`, `orden`, `creado`) VALUES (1,'Modelo 1','',NULL,'hilo',1,1,'2026-11-23 16:01:16'),(2,'Modelo 2','',NULL,'telar',1,2,'2026-11-23 16:01:16'),(3,'Modelo 3','',NULL,'macrame',1,3,'2026-11-23 16:01:16'),(4,'Modelo 4','',NULL,'borlas',1,4,'2026-11-23 16:01:16');
/*!40000 ALTER TABLE `colgaderas` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `inspiraciones` WRITE;
/*!40000 ALTER TABLE `inspiraciones` DISABLE KEYS */;
INSERT INTO `inspiraciones` (`id`, `titulo`, `imagen`, `creado`, `descripcion`, `producto_id`) VALUES (1,'Hamaca de macramé como tapiz en sala','assets/imagenes/imagen-portada.jpg','2026-11-19 01:36:58','Una hamaca de macramé con flecos en tonos tierra convierte una pared sencilla en el centro de la sala. Colgada como pieza decorativa, combina con maderas, fibras naturales y mucha luz.',2);
/*!40000 ALTER TABLE `inspiraciones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

