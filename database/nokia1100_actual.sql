-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: nokia1100
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Dumping routines for database 'nokia1100'
--

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL COMMENT 'NULL si el intento fue fallido o sin sesiÃ³n',
  `accion` varchar(100) NOT NULL COMMENT 'LOGIN_OK, LOGIN_FAIL, LOGOUT, USER_CREATE, USER_DELETE, USER_RESTORE, ROLE_CHANGE, SALE_CREATE, PRODUCT_CREATE, PRODUCT_DELETE, PASSWORD_RESET',
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `username_intent` varchar(100) DEFAULT NULL COMMENT 'Usuario que intentÃ³ el login (aunque falle)',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_accion` (`accion`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de auditorÃ­a de seguridad y acciones sensibles';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,8,'INVENTORY_UPDATE','Inventario',17,'Stock de \'Celular  Samsung  A15\' devuelto (1 unidades) por anulación de venta','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-16 16:46:10'),(10,8,'INVENTORY_CREATE','Inventario',27,'Registrado nuevo producto en inventario ID 27: Mouse','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-17 20:27:11'),(11,8,'INVENTORY_UPDATE','Inventario',27,'Actualizado producto ID 27: Mouse','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-17 20:27:31'),(12,8,'INVENTORY_UPDATE','Inventario',1,'Actualizado producto ID 1: Celular','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-18 15:10:24'),(13,8,'INVENTORY_UPDATE','Inventario',21,'Actualizado producto ID 21: Celular','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-18 15:10:29'),(14,8,'INVENTORY_UPDATE','Inventario',4,'Actualizado producto ID 4: Auricular','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-18 15:10:35'),(15,8,'INVENTORY_UPDATE','Inventario',25,'Actualizado producto ID 25: Cargador','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'2026-11-18 15:10:43');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caja`
--

DROP TABLE IF EXISTS `caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caja` (
  `id_caja` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `hora_apertura` varchar(8) NOT NULL,
  `hora_cierre` varchar(8) DEFAULT NULL,
  `monto_apertura` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monto_cierre` decimal(12,2) DEFAULT NULL,
  `monto_esperado` decimal(12,2) DEFAULT NULL,
  `diferencia` decimal(12,2) DEFAULT NULL,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `observaciones` text DEFAULT NULL,
  `id_usuario_apertura` int(11) DEFAULT NULL,
  `id_usuario_cierre` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_caja`),
  KEY `idx_caja_estado` (`estado`),
  KEY `idx_caja_fecha` (`fecha`),
  KEY `fk_caja_usuario_apertura` (`id_usuario_apertura`),
  KEY `fk_caja_usuario_cierre` (`id_usuario_cierre`),
  CONSTRAINT `fk_caja_usuario_apertura` FOREIGN KEY (`id_usuario_apertura`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_caja_usuario_cierre` FOREIGN KEY (`id_usuario_cierre`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja`
--

LOCK TABLES `caja` WRITE;
/*!40000 ALTER TABLE `caja` DISABLE KEYS */;
INSERT INTO `caja` VALUES (1,'2026-11-02','21:10','21:26',30000.00,100000.00,100000.00,0.00,'cerrada','',8,8,'2026-11-02 21:10:22','2026-11-02 21:26:15'),(2,'2026-11-02','21:53','22:21',50000.00,50000.00,50000.00,0.00,'cerrada','',8,8,'2026-11-02 21:53:36','2026-11-02 22:21:43'),(3,'2026-11-02','22:54','22:55',200000.00,330000.00,330000.00,0.00,'cerrada','',8,8,'2026-11-02 22:54:41','2026-11-02 22:55:52'),(4,'2026-11-03','00:07','00:24',330000.00,330000.00,330000.00,0.00,'cerrada','',8,8,'2026-11-03 00:07:32','2026-11-03 00:24:48'),(5,'2026-11-03','00:32','00:33',150000.00,310000.00,310000.00,0.00,'cerrada','',8,8,'2026-11-03 00:32:13','2026-11-03 00:33:10'),(6,'2026-11-03','02:33','02:34',310000.00,360000.00,360000.00,0.00,'cerrada','',8,8,'2026-11-03 02:33:41','2026-11-03 02:34:04'),(7,'2026-11-03','20:01','20:03',360000.00,550000.00,510000.00,40000.00,'cerrada','Monto desconocido.',8,8,'2026-11-03 20:01:26','2026-11-03 20:03:08'),(8,'2026-11-03','20:29','20:33',550000.00,555000.00,555000.00,0.00,'cerrada','',8,8,'2026-11-03 20:29:42','2026-11-03 20:33:39'),(9,'2026-11-04','11:20','11:21',555000.00,480000.00,460000.00,20000.00,'cerrada','Dinero restante de dudosa procedencia',8,8,'2026-11-04 11:20:08','2026-11-04 11:21:34'),(10,'2026-11-04','12:12','12:14',480000.00,300000.00,295000.00,5000.00,'cerrada','hay dinero sobrante y no se reconoce por que',8,8,'2026-11-04 12:12:43','2026-11-04 12:14:16'),(11,'2026-11-16','16:46','16:48',300000.00,310000.00,310000.00,0.00,'cerrada','',8,8,'2026-11-16 16:46:27','2026-11-16 16:48:38'),(12,'2026-11-17','14:15','14:24',310000.00,315000.00,315000.00,0.00,'cerrada','',8,8,'2026-11-17 14:15:29','2026-11-17 14:24:02'),(13,'2026-11-17','14:24','14:52',315000.00,336000.00,336000.00,0.00,'cerrada','',8,8,'2026-11-17 14:24:35','2026-11-17 14:52:54'),(14,'2026-11-17','14:53','17:26',336000.00,416000.00,386000.00,30000.00,'cerrada','Dinero mal contado.',18,8,'2026-11-17 14:53:23','2026-11-17 17:26:03');
/*!40000 ALTER TABLE `caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caja_movimiento`
--

DROP TABLE IF EXISTS `caja_movimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caja_movimiento` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_caja` int(11) NOT NULL,
  `tipo` enum('in','out') NOT NULL DEFAULT 'in',
  `medio_pago` varchar(50) NOT NULL DEFAULT 'Efectivo',
  `concepto` varchar(100) NOT NULL,
  `detalle` varchar(255) DEFAULT '',
  `motivo` varchar(255) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_venta` int(11) DEFAULT NULL,
  `id_reparacion` int(11) DEFAULT NULL,
  `id_movimiento_origen` int(11) DEFAULT NULL,
  `hora` varchar(8) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_mov_caja` (`id_caja`),
  KEY `fk_mov_usuario` (`id_usuario`),
  KEY `idx_mov_tipo` (`tipo`),
  KEY `fk_caja_mov_venta` (`id_venta`),
  KEY `fk_caja_mov_reparacion` (`id_reparacion`),
  KEY `fk_caja_mov_origen` (`id_movimiento_origen`),
  CONSTRAINT `fk_caja_mov_origen` FOREIGN KEY (`id_movimiento_origen`) REFERENCES `caja_movimiento` (`id_movimiento`) ON DELETE SET NULL,
  CONSTRAINT `fk_caja_mov_reparacion` FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion` (`id_reparacion`) ON DELETE SET NULL,
  CONSTRAINT `fk_caja_mov_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_caja` FOREIGN KEY (`id_caja`) REFERENCES `caja` (`id_caja`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caja_movimiento`
--

LOCK TABLES `caja_movimiento` WRITE;
/*!40000 ALTER TABLE `caja_movimiento` DISABLE KEYS */;
INSERT INTO `caja_movimiento` VALUES (1,1,'in','Efectivo','Venta en efectivo','Venta de cargador usb tipo c',NULL,10000.00,NULL,NULL,NULL,'21:24',8,'2026-11-02 21:24:33'),(2,1,'in','Efectivo','Cobro de reparación','Reparacion Samsung A15',NULL,50000.00,NULL,NULL,NULL,'21:24',8,'2026-11-02 21:24:57'),(3,1,'out','Efectivo','Retiro de efectivo','',NULL,10000.00,NULL,NULL,NULL,'21:25',8,'2026-11-02 21:25:22'),(4,1,'in','Efectivo','Cobro de reparación','venta de auricular',NULL,20000.00,NULL,NULL,NULL,'21:25',8,'2026-11-02 21:25:55'),(5,3,'in','Efectivo','Venta en efectivo','',NULL,30000.00,NULL,NULL,NULL,'22:55',8,'2026-11-02 22:55:02'),(6,3,'in','Efectivo','Cobro de reparación','Reparacion de Iphne 11',NULL,100000.00,NULL,NULL,NULL,'22:55',8,'2026-11-02 22:55:29'),(7,5,'out','Efectivo','Pago a proveedor','',NULL,90000.00,NULL,NULL,NULL,'00:32',8,'2026-11-03 00:32:27'),(8,5,'in','Efectivo','Cobro de reparación','Reparacion Samsung S26 ULTRA',NULL,250000.00,NULL,NULL,NULL,'00:32',8,'2026-11-03 00:32:51'),(9,6,'in','Efectivo','Venta en efectivo','',NULL,50000.00,NULL,NULL,NULL,'02:33',8,'2026-11-03 02:33:54'),(10,7,'in','Efectivo','Cobro de reparación','',NULL,50000.00,NULL,NULL,NULL,'20:01',8,'2026-11-03 20:01:43'),(11,7,'in','Efectivo','Venta en efectivo','',NULL,100000.00,NULL,NULL,NULL,'20:01',8,'2026-11-03 20:01:52'),(12,8,'in','Efectivo','Venta en efectivo','',NULL,10000.00,NULL,NULL,NULL,'20:30',8,'2026-11-03 20:30:26'),(13,8,'out','Efectivo','Pago a proveedor','',NULL,5000.00,NULL,NULL,NULL,'20:30',8,'2026-11-03 20:30:35'),(14,9,'in','Efectivo','Seña','',NULL,5000.00,NULL,NULL,NULL,'11:20',8,'2026-11-04 11:20:22'),(15,9,'in','Efectivo','Venta en efectivo','',NULL,50000.00,NULL,NULL,NULL,'11:20',8,'2026-11-04 11:20:30'),(16,9,'out','Efectivo','Pago a proveedor','',NULL,150000.00,NULL,NULL,NULL,'11:20',8,'2026-11-04 11:20:41'),(17,10,'in','Efectivo','Cobro de reparación','',NULL,50000.00,NULL,NULL,NULL,'12:12',8,'2026-11-04 12:12:51'),(18,10,'in','Efectivo','Venta en efectivo','',NULL,10000.00,NULL,NULL,NULL,'12:12',8,'2026-11-04 12:12:59'),(19,10,'in','Efectivo','Venta en efectivo','',NULL,5000.00,NULL,NULL,NULL,'12:13',8,'2026-11-04 12:13:04'),(20,10,'out','Efectivo','Pago a proveedor','',NULL,150000.00,NULL,NULL,NULL,'12:13',8,'2026-11-04 12:13:12'),(21,10,'out','Efectivo','Retiro de efectivo','',NULL,100000.00,NULL,NULL,NULL,'12:13',8,'2026-11-04 12:13:20'),(22,11,'in','Transferencia','Cobro de reparación','',NULL,25000.00,NULL,NULL,NULL,'16:46',8,'2026-11-16 16:46:54'),(23,11,'in','Efectivo','Seña','',NULL,10000.00,NULL,NULL,NULL,'16:47',8,'2026-11-16 16:47:55'),(24,12,'in','Transferencia','Venta en efectivo','',NULL,5000.00,NULL,NULL,NULL,'14:16',8,'2026-11-17 14:16:54'),(25,12,'in','Efectivo','Venta en efectivo','',NULL,5000.00,NULL,NULL,NULL,'14:23',8,'2026-11-17 14:23:48'),(26,13,'in','Efectivo','Venta','',NULL,10000.00,NULL,NULL,NULL,'14:24',8,'2026-11-17 14:24:43'),(27,13,'out','Efectivo','Contra-asiento: Venta','Ajuste inverso de mov. #26 · Motivo: Error de carga del monto','Error de carga del monto',10000.00,NULL,NULL,26,'14:25',8,'2026-11-17 14:25:15'),(28,13,'in','Efectivo','Venta','',NULL,11000.00,NULL,NULL,NULL,'14:25',8,'2026-11-17 14:25:28'),(29,13,'in','Efectivo','Aporte de cambio','',NULL,10000.00,NULL,NULL,NULL,'14:26',8,'2026-11-17 14:26:53'),(30,14,'in','Efectivo','Cobro de reparación','Venta #TX-00037 · Pago combinado (Efectivo: $50.000,00 de $130.000,00)','Pago combinado (#TX-00037)',50000.00,37,18,NULL,'17:24',8,'2026-11-17 17:24:56'),(31,14,'in','Transferencia','Cobro de reparación','Venta #TX-00037 · Pago combinado (Transferencia: $80.000,00 de $130.000,00)','Pago combinado (#TX-00037)',80000.00,37,18,NULL,'17:24',8,'2026-11-17 17:24:56');
/*!40000 ALTER TABLE `caja_movimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'Nicolas Garcia','3704236548','','2026-10-06 03:55:57'),(2,'Santiago Mora','371854652','','2026-10-09 16:43:10'),(3,'Migue Romero','3704526894','','2026-10-19 14:49:36'),(4,'Facundo Esquivel','3704245651','facundoesquivel03@gmail.com','2026-10-21 20:05:08'),(5,'Enzo Villalba','3704568974','villalbaenzo95@gmail.com','2026-10-29 00:35:40'),(6,'Marcos Kayser','3704589687','','2026-11-03 22:57:31'),(7,'Ariel Montenegro','3704236545','montenegroariel@gmail.com','2026-11-03 23:12:57'),(8,'Paula Alvarenga','3704797167','paulaalvarenga26@gmail.com','2026-11-04 15:14:52'),(9,'Gonzalo Gauna','37056987412','','2026-11-11 00:45:41'),(10,'Alan Ramirez','3705698745','','2026-11-17 23:50:49'),(11,'Hector Villalba','3704589674','hectorvillalba2000@gmail.com','2026-11-17 23:55:22');
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra`
--

DROP TABLE IF EXISTS `compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tiempo_entrega` varchar(100) DEFAULT NULL,
  `autorizado_por` varchar(100) DEFAULT NULL,
  `estado` enum('Pendiente','Recibido','Cancelado') NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id_compra`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `compra_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra`
--

LOCK TABLES `compra` WRITE;
/*!40000 ALTER TABLE `compra` DISABLE KEYS */;
INSERT INTO `compra` VALUES (1,3,'2026-10-30 20:20:38',275000.00,'','','','Recibido'),(2,3,'2026-10-30 21:39:30',8000000.00,'','','','Cancelado'),(3,3,'2026-10-30 22:09:29',5150000.00,'Lote mensual','15','Enzo','Recibido'),(4,3,'2026-11-03 17:01:18',60000.00,'Reposición automática de stock crítico','A coordinar','facuE','Cancelado'),(5,7,'2026-11-04 12:18:03',1170000.00,'Reposición automática de stock crítico','A coordinar','facuE','Recibido'),(6,9,'2026-11-04 12:19:35',920000.00,'Orden de compra manual','A coordinar','facuE','Cancelado'),(7,7,'2026-11-18 15:23:55',8650000.00,'Reposición automática de stock crítico','A coordinar','facuE','Pendiente');
/*!40000 ALTER TABLE `compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_compra`
--

DROP TABLE IF EXISTS `detalle_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_compra` (
  `id_detalle_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `nombre_producto` varchar(150) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_compra`),
  KEY `id_compra` (`id_compra`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_compra_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `detalle_compra_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_compra`
--

LOCK TABLES `detalle_compra` WRITE;
/*!40000 ALTER TABLE `detalle_compra` DISABLE KEYS */;
INSERT INTO `detalle_compra` VALUES (1,1,14,1,5000.00,'Cargador','Samsung','USB TIPO C'),(2,1,20,1,10000.00,'Cargador','Samsung','Tipo C'),(3,1,13,1,250000.00,'Display','iphone','13'),(4,1,3,1,10000.00,'Funda Silicona','Iphone','13'),(5,2,21,10,800000.00,'Celular','Huawei','nova 15 Max'),(6,3,22,10,130000.00,'Mouse','Ryzen','SuperDragonGT'),(7,3,4,10,10000.00,'Auricular','Iphone','15 Pro'),(8,3,13,15,250000.00,'Display','iphone','13'),(9,4,20,6,10000.00,'Cargador','Samsung','Tipo C'),(10,5,4,5,10000.00,'Auricular','Iphone','15 Pro'),(11,5,23,15,70000.00,'Auricular','SnapDragon','SN Pro'),(12,5,20,7,10000.00,'Cargador','Samsung','Tipo C'),(13,6,4,3,10000.00,'Auricular','Iphone','15 Pro'),(14,6,22,4,130000.00,'Mouse','Ryzen','SuperDragonGT'),(15,6,6,7,10000.00,'Funda','Samsung','S26 ULTRA'),(16,6,1,5,50000.00,'Celular','Nokia','1100'),(17,6,3,5,10000.00,'Funda Silicona','Iphone','13'),(18,7,4,10,10000.00,'Auricular','Iphone','15 Pro'),(19,7,25,10,5000.00,'Cargador','Iphone','Lightning'),(20,7,1,10,50000.00,'Celular','Nokia','1100'),(21,7,21,10,800000.00,'Celular','Huawei','nova 15 Max');
/*!40000 ALTER TABLE `detalle_compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  PRIMARY KEY (`id_detalle_venta`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE,
  CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
INSERT INTO `detalle_venta` VALUES (1,1,1,10,50000.00,'Nokia 1100 Original Nokia 1100'),(2,2,1,1,40000.00,'Nokia 1100 Original Nokia 1100'),(3,3,1,9,40000.00,'Nokia 1100 Original Nokia 1100'),(4,4,4,1,10000.00,'Auriculares Iphone 15 Pro'),(5,5,3,1,10000.00,'Funda silicona ip 13 Iphone 13'),(6,6,2,1,500000.00,'Smartphone  Iphone 15 Pro'),(9,9,13,1,310000.00,'Servicio Técnico Taller'),(10,10,13,1,30000.00,'Servicio Técnico Taller'),(11,11,13,1,20000.00,'Servicio Técnico Taller'),(12,12,13,1,20000.00,'Servicio Técnico Taller'),(13,13,13,1,20000.00,'Servicio Técnico Taller'),(14,14,13,1,225000.00,'Servicio Técnico Taller'),(15,15,16,1,180000.00,'Servicio Técnico Taller'),(16,16,16,1,180000.00,'Servicio Técnico Taller'),(17,17,15,9,10000.00,'Cargador  Soul Lightning'),(18,18,16,1,310000.00,'Servicio Técnico Taller'),(19,19,16,1,150000.00,'Servicio Técnico Taller'),(20,20,16,1,320000.00,'Servicio Técnico Taller'),(21,21,16,1,430000.00,'Servicio Técnico Taller'),(22,22,16,1,110000.00,'Servicio Técnico Taller'),(23,23,16,1,225000.00,'Servicio Técnico Taller'),(24,24,16,1,375000.00,'Servicio Técnico Taller'),(25,25,16,1,375000.00,'Servicio Técnico Taller'),(26,26,16,1,30000.00,'Servicio Técnico Taller'),(27,27,16,1,0.00,'Servicio Técnico Taller'),(28,28,16,1,150000.00,'Servicio Técnico Taller'),(29,29,16,1,165000.00,'Servicio Técnico Taller'),(30,30,16,1,300000.00,'Servicio Técnico Taller'),(31,31,13,1,250000.00,'Display   iphone  13'),(32,32,17,1,170000.00,'Celular  Samsung  A15'),(42,37,16,1,130000.00,'Servicio Técnico Taller'),(44,39,16,1,130000.00,'Servicio Técnico Taller');
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventario`
--

DROP TABLE IF EXISTS `inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventario` (
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_producto`),
  CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario`
--

LOCK TABLES `inventario` WRITE;
/*!40000 ALTER TABLE `inventario` DISABLE KEYS */;
INSERT INTO `inventario` VALUES (1,2),(2,10),(3,6),(4,5),(5,15),(6,20),(7,14),(8,10),(9,20),(13,27),(14,8),(15,19),(16,9999),(17,10),(18,10),(19,5),(20,10),(21,5),(22,35),(23,13),(24,10),(25,2),(26,10),(27,20);
/*!40000 ALTER TABLE `inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metodos_pago` (
  `id_metodo_pago` int(11) NOT NULL AUTO_INCREMENT,
  `metodo` varchar(50) NOT NULL,
  PRIMARY KEY (`id_metodo_pago`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodos_pago`
--

LOCK TABLES `metodos_pago` WRITE;
/*!40000 ALTER TABLE `metodos_pago` DISABLE KEYS */;
INSERT INTO `metodos_pago` VALUES (1,'Efectivo'),(2,'Tarjeta de CrÚdito'),(3,'Tarjeta de DÚbito'),(4,'Transferencia');
/*!40000 ALTER TABLE `metodos_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `persona`
--

DROP TABLE IF EXISTS `persona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persona` (
  `id_persona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_persona`),
  UNIQUE KEY `dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `persona`
--

LOCK TABLES `persona` WRITE;
/*!40000 ALTER TABLE `persona` DISABLE KEYS */;
INSERT INTO `persona` VALUES (12,'Facundo','Esquivel','44982366','','facundoesquivel03@gmail.com',''),(22,'Santiago','Mora','41325648','','scdm0407@gmail.com',''),(27,'omar','Esquivel','44896745','','facuesquivel338@gmail.com','');
/*!40000 ALTER TABLE `persona` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,'Celular',50000.00,1),(2,'Smartphone ',500000.00,1),(3,'Funda Silicona ',10000.00,1),(4,'Auricular',10000.00,1),(5,'Funda',4200.00,1),(6,'Funda ',10000.00,1),(7,'Funda Silicona',10000.00,1),(8,'Mouse ',122748.00,1),(9,'Parlante',89500.00,1),(13,'Display ',250000.00,1),(14,'Cargador',5000.00,1),(15,'Cargador',10000.00,1),(16,'Servicio Técnico Taller',0.00,1),(17,'Celular ',170000.00,1),(18,'Celular ',180000.00,1),(19,'Pantalla OLED',400000.00,1),(20,'Cargador',10000.00,1),(21,'Celular',800000.00,1),(22,'Mouse',130000.00,1),(23,'Auricular',70000.00,1),(24,'Modulo',150000.00,1),(25,'Cargador',5000.00,1),(26,'Film de vidrio',3000.00,1),(27,'Mouse',42948.00,1);
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_detalle`
--

DROP TABLE IF EXISTS `producto_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_detalle` (
  `id_producto` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `tipo_repuesto` varchar(50) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `stock_minimo` int(11) DEFAULT 0,
  PRIMARY KEY (`id_producto`),
  CONSTRAINT `producto_detalle_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_detalle`
--

LOCK TABLES `producto_detalle` WRITE;
/*!40000 ALTER TABLE `producto_detalle` DISABLE KEYS */;
INSERT INTO `producto_detalle` VALUES (1,'Nokia','1100','',NULL,'Celulares',NULL,NULL,5),(2,'Iphone','15 Pro','MU0Y3QL/A',NULL,'Celulares','',NULL,5),(3,'Iphone','13','SIL-IP13-001',NULL,'Fundas','',NULL,5),(4,'Iphone','15 Pro','AUR-IP2-101',NULL,'Audio','',NULL,5),(5,'Motorola','Moto G20','',NULL,'Fundas','',NULL,5),(6,'Samsung ','S26 ULTRA','',NULL,'Fundas','',NULL,5),(7,'Iphone 13','Pro','',NULL,'Fundas','',NULL,5),(8,'Ryzen','Basilisk V3','',NULL,'Perifericos','',NULL,2),(9,'JBL ','GO 4','',NULL,'Audio','',NULL,5),(13,' iphone',' 13',NULL,NULL,'Repuestos','Servicio Técnico y Mano de Obra Taller',NULL,0),(14,'Samsung','USB TIPO C','',NULL,'Cargadores','',NULL,5),(15,'Soul','Lightning','',NULL,'Cargadores','',NULL,5),(16,'Nokia','Taller',NULL,NULL,'Servicios','Servicio Técnico y Mano de Obra Taller',NULL,0),(17,'Samsung ','A15','',NULL,'Celulares','',NULL,5),(18,'Motorola','Moto G20','',NULL,'Celulares','',NULL,5),(19,'Iphone','17 PRO','',NULL,'Repuestos','',NULL,2),(20,'Samsung','Tipo C','',NULL,'Cargadores','',NULL,5),(21,'Huawei','nova 15 Max','',NULL,'Celulares','',NULL,5),(22,'Ryzen','SuperDragonGT','',NULL,'Perifericos','',NULL,5),(23,'SnapDragon','SN Pro','',NULL,'Audio','',NULL,5),(24,'Samsung','A56','',NULL,'Repuestos','',NULL,5),(25,'Iphone','Lightning','',NULL,'Cargadores','',NULL,2),(26,'Samsung','A15','',NULL,'Repuestos','',NULL,5),(27,'Terralife','Mouse Vertical','',NULL,'Perifericos','',NULL,5);
/*!40000 ALTER TABLE `producto_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `domicilio` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `atencion` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedor`
--

LOCK TABLES `proveedor` WRITE;
/*!40000 ALTER TABLE `proveedor` DISABLE KEYS */;
INSERT INTO `proveedor` VALUES (7,'El gringo cell','Av. Nestor Kirchner 8569','5493704859647','Martin Gomez','elgringocell@gmail.com'),(9,'El negro tech','Av. los negrillos','5693704589456','Enzo Villalba','elnegrotech@gmail.com');
/*!40000 ALTER TABLE `proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparacion`
--

DROP TABLE IF EXISTS `reparacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reparacion` (
  `id_reparacion` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_orden` varchar(20) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `equipo_marca` varchar(50) NOT NULL,
  `equipo_modelo` varchar(50) NOT NULL,
  `equipo_imei` varchar(30) DEFAULT NULL,
  `falla_declarada` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `presupuesto` decimal(10,2) DEFAULT 0.00,
  `costo_total` decimal(10,2) DEFAULT 0.00,
  `estado` varchar(30) NOT NULL DEFAULT 'Recibido',
  `id_usuario_recibe` int(11) DEFAULT NULL,
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_reparacion`),
  UNIQUE KEY `uq_reparacion_codigo` (`codigo_orden`),
  KEY `id_cliente` (`id_cliente`),
  KEY `fk_reparacion_usuario` (`id_usuario_recibe`),
  CONSTRAINT `fk_reparacion_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_reparacion_usuario` FOREIGN KEY (`id_usuario_recibe`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparacion`
--

LOCK TABLES `reparacion` WRITE;
/*!40000 ALTER TABLE `reparacion` DISABLE KEYS */;
INSERT INTO `reparacion` VALUES (1,'26106974',1,'Iphone','14','213516','modulo roto','',300000.00,10000.00,'Entregado',8,'2026-10-06 03:55:57','2026-10-17 17:26:20'),(2,'26104177',2,'Motorola ','G52','','Modulo roto','',20000.00,10000.00,'Entregado',8,'2026-10-09 16:43:10','2026-10-17 21:11:55'),(3,'26103856',1,'Huawei','k20','','se ha mojado\r\n','',20000.00,0.00,'Entregado',8,'2026-10-17 21:18:35','2026-10-19 14:53:21'),(4,'26108982',3,'Iphone','16','','display desconectado y falla del pin de carga','',220000.00,5000.00,'Entregado',8,'2026-10-19 14:49:36','2026-10-20 00:11:06'),(5,'26106226',4,'Iphone','11','','pantalla rota\r\n','',170000.00,10000.00,'Entregado',8,'2026-10-21 20:05:08','2026-10-21 20:29:10'),(6,'26107372',5,'Iphone','16','ip-129-M123','modulo roto','',300000.00,10000.00,'Entregado',8,'2026-10-29 00:35:40','2026-10-29 01:24:16'),(7,'26105933',2,'Samsung','A14','','Pantalla rota','',150000.00,0.00,'Entregado',8,'2026-10-30 17:10:14','2026-10-30 17:13:14'),(8,'26102510',1,'Samsung','S26 ULTRA','','Pin de carga fallido','',300000.00,20000.00,'Entregado',8,'2026-10-30 21:53:06','2026-10-30 21:54:52'),(9,'26104388',4,'Iphone','11','','Modulo roto y pin de carga obsoleto','',350000.00,80000.00,'Entregado',8,'2026-10-31 01:04:55','2026-10-31 01:06:17'),(10,'26115866',6,'Samsung','A56','','No anda el pin de carga y modulo obsoleto.\r\n','',150000.00,75000.00,'Entregado',8,'2026-11-03 22:57:31','2026-11-04 14:19:21'),(11,'26117220',7,'Samsung','A17','','Modulo roto','',100000.00,10000.00,'Entregado',8,'2026-11-03 23:12:57','2026-11-03 23:28:56'),(12,'26119155',1,'Samsung','S25','','No funciona el pin de carga ','',300000.00,75000.00,'Entregado',8,'2026-11-04 15:10:29','2026-11-06 16:56:18'),(13,'26118134',4,'Iphone','11','ip-129-M123','Modulo mojado','',30000.00,0.00,'Entregado',8,'2026-11-10 23:30:10','2026-11-10 23:39:09'),(14,'26114413',8,'Iphone','11 PRO','ip-134-I343','mojado','',150000.00,15000.00,'Entregado',8,'2026-11-10 23:44:07','2026-11-11 05:24:43'),(15,'26117681',6,'Samsung','A14','','se cayo \r\n','',0.00,0.00,'Entregado',8,'2026-11-11 00:05:47','2026-11-11 01:01:14'),(16,'26117593',9,'Iphone','13','ip-789-i893','Problemas con pin de carga.','',150000.00,0.00,'Entregado',18,'2026-11-11 00:45:41','2026-11-11 05:22:58'),(17,'26113575',6,'Samsung','S25','','modulo roto\r\n','',300000.00,0.00,'Entregado',8,'2026-11-11 16:08:28','2026-11-11 16:09:46'),(18,'26119469',6,'Samsung','k20','','.','',50000.00,80000.00,'Entregado',8,'2026-11-17 05:27:56','2026-11-17 20:24:56'),(19,'26118024',10,'Samsung','A56','','Modulo despegado y suciedad en el pin de carga.\r\n','',50000.00,80000.00,'Entregado',8,'2026-11-17 23:50:49','2026-11-17 23:53:29');
/*!40000 ALTER TABLE `reparacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparacion_historial`
--

DROP TABLE IF EXISTS `reparacion_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reparacion_historial` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_reparacion` int(11) NOT NULL,
  `estado_anterior` varchar(30) DEFAULT NULL,
  `estado_nuevo` varchar(30) NOT NULL,
  `nota` text DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `id_reparacion` (`id_reparacion`),
  KEY `fk_rep_hist_usuario` (`id_usuario`),
  CONSTRAINT `fk_rep_hist_reparacion` FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion` (`id_reparacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_rep_hist_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparacion_historial`
--

LOCK TABLES `reparacion_historial` WRITE;
/*!40000 ALTER TABLE `reparacion_historial` DISABLE KEYS */;
INSERT INTO `reparacion_historial` VALUES (1,1,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-06 03:55:57'),(2,1,'Recibido','Listo','',8,'2026-10-06 03:56:17'),(3,2,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-09 16:43:10'),(6,1,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-17 17:26:20'),(7,2,'Recibido','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-17 21:11:25'),(8,2,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-17 21:11:55'),(9,3,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-17 21:18:35'),(10,3,'Recibido','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-17 21:18:51'),(11,3,'Listo','Recibido','',8,'2026-10-17 21:18:58'),(12,3,'Recibido','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-17 21:19:30'),(13,3,'Listo','Recibido','',8,'2026-10-17 21:19:34'),(14,3,'Recibido','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-17 21:19:38'),(15,3,'Listo','Recibido','',8,'2026-10-17 21:26:55'),(16,3,'Recibido','Listo','Tecnico finalizo reparacion',NULL,'2026-10-17 21:52:57'),(17,3,'Listo','Listo','Finalizado por técnico y enviado a Caja: Enviado a caja para cobro',NULL,'2026-10-17 21:52:57'),(18,3,'Recibido','Listo','',8,'2026-10-17 21:59:30'),(19,3,'Listo','En reparación','',8,'2026-10-17 21:59:34'),(20,3,'En reparación','Listo','',8,'2026-10-17 21:59:38'),(21,3,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-17 22:25:16'),(22,3,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-17 22:25:24'),(23,3,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-17 23:07:06'),(24,4,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-19 14:49:36'),(25,4,'Recibido','En diagnóstico','',8,'2026-10-19 14:50:25'),(26,4,'En diagnóstico','En reparación','',8,'2026-10-19 14:50:30'),(27,4,'En reparación','Listo','',8,'2026-10-19 14:50:33'),(28,4,'Listo','En reparación','',8,'2026-10-19 14:50:48'),(29,4,'En reparación','Listo','',8,'2026-10-19 14:50:54'),(30,4,'Listo','En reparación','',8,'2026-10-19 14:51:05'),(31,4,'En reparación','Listo','',8,'2026-10-19 14:51:17'),(32,4,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-19 14:51:31'),(33,3,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-19 14:53:21'),(34,4,'Listo','En reparación','',8,'2026-10-19 15:05:28'),(35,4,'En reparación','Listo','',8,'2026-10-19 15:05:35'),(36,4,'Listo','En reparación','',8,'2026-10-19 15:05:40'),(37,4,'En reparación','Listo','',8,'2026-10-19 15:05:43'),(38,4,'Listo','En reparación','',8,'2026-10-19 15:08:32'),(39,4,'En reparación','Listo','',8,'2026-10-19 15:08:35'),(40,4,'Listo','En reparación','',8,'2026-10-19 15:08:42'),(41,4,'En reparación','Listo','',8,'2026-10-19 15:08:46'),(42,4,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-19 15:08:50'),(43,4,'Listo','En reparación','',8,'2026-10-19 22:50:40'),(44,4,'En reparación','Listo','',8,'2026-10-19 22:50:45'),(45,4,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-19 22:51:02'),(46,4,'Listo','Entregado','Cobrado y entregado desde Ventas',NULL,'2026-10-20 00:11:06'),(47,5,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-21 20:05:08'),(48,5,'Recibido','Listo','',8,'2026-10-21 20:05:24'),(49,5,'Listo','En reparación','',8,'2026-10-21 20:05:29'),(50,5,'En reparación','Listo','',8,'2026-10-21 20:05:33'),(51,5,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-21 20:06:06'),(52,5,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-21 20:08:30'),(53,5,'Entregado','Listo para Retirar','Cobro anulado (venta #15). Pendiente de cobro nuevamente.',8,'2026-10-21 20:08:40'),(54,5,'Listo para Retirar','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-21 20:09:02'),(55,5,'Entregado','Listo para Retirar','Cobro anulado (venta #16). Pendiente de cobro nuevamente.',8,'2026-10-21 20:09:19'),(56,6,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-29 00:35:40'),(57,6,'Recibido','En diagnóstico','',8,'2026-10-29 00:56:00'),(58,6,'En diagnóstico','Listo','',8,'2026-10-29 00:56:13'),(59,6,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-29 00:56:17'),(60,6,'Listo','En reparación','',8,'2026-10-29 01:24:04'),(61,6,'En reparación','Listo','',8,'2026-10-29 01:24:08'),(62,6,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-29 01:24:11'),(63,6,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-10-29 01:24:16'),(64,7,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-30 17:10:14'),(65,7,'Recibido','En diagnóstico','',8,'2026-10-30 17:10:19'),(66,7,'En diagnóstico','Listo','',8,'2026-10-30 17:11:58'),(67,7,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-30 17:12:15'),(68,7,'Listo','Entregado','Cobrado y entregado desde Ventas',NULL,'2026-10-30 17:13:14'),(69,8,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-30 21:53:06'),(70,8,'Recibido','En diagnóstico','',8,'2026-10-30 21:53:16'),(71,8,'En diagnóstico','En reparación','',8,'2026-10-30 21:53:22'),(72,8,'En reparación','Listo','',8,'2026-10-30 21:53:27'),(73,8,'Listo','Cancelado','',8,'2026-10-30 21:53:34'),(74,8,'Cancelado','Listo','',8,'2026-10-30 21:53:48'),(75,8,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-30 21:54:28'),(76,8,'Listo','Entregado','Cobrado y entregado desde Ventas',NULL,'2026-10-30 21:54:52'),(77,8,'Entregado','Entregado','Cobro anulado (venta #20). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-10-30 21:55:40'),(78,9,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-10-31 01:04:55'),(79,9,'Recibido','En diagnóstico','',8,'2026-10-31 01:05:02'),(80,9,'En diagnóstico','En reparación','',8,'2026-10-31 01:05:07'),(81,9,'En reparación','Listo','',8,'2026-10-31 01:05:22'),(82,9,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-10-31 01:05:48'),(83,9,'Listo','Entregado','Cobrado y entregado desde Ventas',NULL,'2026-10-31 01:06:17'),(84,9,'Entregado','Entregado','Cobro anulado (venta #21). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-10-31 01:07:01'),(85,10,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-03 22:57:31'),(86,11,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-03 23:12:57'),(87,11,'Recibido','En diagnóstico','',8,'2026-11-03 23:13:35'),(88,11,'En diagnóstico','En reparación','',8,'2026-11-03 23:15:21'),(89,11,'En reparación','Listo','',8,'2026-11-03 23:28:21'),(90,11,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-03 23:28:31'),(91,11,'Listo','Entregado','Cobrado y entregado desde Ventas',NULL,'2026-11-03 23:28:56'),(92,10,'Recibido','En diagnóstico','',8,'2026-11-04 14:18:22'),(93,10,'En diagnóstico','En reparación','',8,'2026-11-04 14:18:27'),(94,10,'En reparación','Listo','',8,'2026-11-04 14:18:32'),(95,10,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-04 14:19:01'),(96,10,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-04 14:19:21'),(97,10,'Entregado','Entregado','Cobro anulado (venta #23). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-11-04 14:19:45'),(98,12,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-04 15:10:29'),(99,12,'Recibido','En diagnóstico','',8,'2026-11-04 15:10:52'),(100,12,'En diagnóstico','En reparación','',8,'2026-11-04 15:10:57'),(101,12,'En reparación','Listo','',8,'2026-11-04 15:11:08'),(102,12,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-04 15:11:53'),(103,12,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-04 15:12:08'),(104,12,'Entregado','Entregado','Cobro anulado (venta #24). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-11-04 15:12:25'),(105,12,'Entregado','Listo','',8,'2026-11-06 16:56:04'),(106,12,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-06 16:56:14'),(107,12,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-06 16:56:18'),(108,13,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-10 23:30:10'),(109,13,'Recibido','En diagnóstico','',8,'2026-11-10 23:31:06'),(110,13,'En diagnóstico','En reparación','',8,'2026-11-10 23:37:30'),(111,13,'En reparación','En diagnóstico','',8,'2026-11-10 23:37:40'),(112,13,'En diagnóstico','Recibido','',8,'2026-11-10 23:37:58'),(113,13,'Recibido','En diagnóstico','',8,'2026-11-10 23:38:07'),(114,13,'En diagnóstico','En reparación','',8,'2026-11-10 23:38:19'),(115,13,'En reparación','Listo','',8,'2026-11-10 23:38:29'),(116,13,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-10 23:38:52'),(117,13,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-10 23:39:09'),(118,14,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-10 23:44:07'),(119,14,'Recibido','En diagnóstico','',8,'2026-11-10 23:45:49'),(120,14,'En diagnóstico','En reparación','',8,'2026-11-10 23:46:00'),(121,14,'En reparación','Listo','',8,'2026-11-10 23:46:05'),(122,14,'Listo','Recibido','',8,'2026-11-11 00:03:43'),(123,15,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-11 00:05:47'),(124,15,'Recibido','En diagnóstico','',8,'2026-11-11 00:06:18'),(125,15,'En diagnóstico','En reparación','',8,'2026-11-11 00:06:31'),(126,14,'Recibido','En diagnóstico','',8,'2026-11-11 00:07:10'),(127,14,'En diagnóstico','En reparación','',8,'2026-11-11 00:07:18'),(128,15,'En reparación','Listo','',8,'2026-11-11 00:09:09'),(129,14,'En reparación','Recibido','',8,'2026-11-11 00:22:03'),(130,16,NULL,'Recibido','Ingreso inicial del equipo',18,'2026-11-11 00:45:41'),(131,15,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-11 01:01:14'),(132,15,'Entregado','Entregado','Cobro anulado (venta #27). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-11-11 01:01:20'),(133,16,'Recibido','En diagnóstico','',8,'2026-11-11 01:16:33'),(134,16,'En diagnóstico','En reparación','',8,'2026-11-11 05:05:22'),(135,16,'En reparación','Listo','',8,'2026-11-11 05:05:41'),(136,16,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-11 05:22:58'),(137,14,'Recibido','En reparación','',8,'2026-11-11 05:24:25'),(138,14,'En reparación','Listo','',8,'2026-11-11 05:24:34'),(139,14,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-11 05:24:43'),(140,17,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-11 16:08:28'),(141,17,'Recibido','En diagnóstico','',8,'2026-11-11 16:09:10'),(142,17,'En diagnóstico','En reparación','',8,'2026-11-11 16:09:23'),(143,17,'En reparación','Listo','',8,'2026-11-11 16:09:32'),(144,17,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-11 16:09:38'),(145,17,'Listo','Entregado','Cobrado y entregado desde Ventas',8,'2026-11-11 16:09:46'),(146,14,'Entregado','Entregado','Cobro anulado (venta #29). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-11-11 16:13:20'),(147,18,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-17 05:27:56'),(148,18,'Recibido','En diagnóstico','',8,'2026-11-17 20:22:58'),(149,18,'En diagnóstico','En reparación','',8,'2026-11-17 20:23:12'),(150,18,'En reparación','Listo','',8,'2026-11-17 20:23:15'),(151,18,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-17 20:24:07'),(152,18,'Listo','Entregado','Cobrado y entregado desde Ventas (Efectivo + Transferencia)',8,'2026-11-17 20:24:56'),(153,19,NULL,'Recibido','Ingreso inicial del equipo',8,'2026-11-17 23:50:49'),(154,19,'Recibido','En diagnóstico','',8,'2026-11-17 23:52:09'),(155,19,'En diagnóstico','En reparación','',8,'2026-11-17 23:52:34'),(156,19,'En reparación','Listo','',8,'2026-11-17 23:52:58'),(157,19,'Listo','Listo','Finalizado por técnico y enviado a Caja para cobro y entrega al cliente',8,'2026-11-17 23:53:01'),(159,19,'Listo','Entregado','Cobrado y entregado desde Ventas (Transferencia)',8,'2026-11-17 23:53:29'),(160,19,'Entregado','Entregado','Cobro anulado (venta #39). Se emite Nota de Crédito. La reparación permanece finalizada.',8,'2026-11-17 23:54:07');
/*!40000 ALTER TABLE `reparacion_historial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparacion_imagen`
--

DROP TABLE IF EXISTS `reparacion_imagen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reparacion_imagen` (
  `id_imagen` int(11) NOT NULL AUTO_INCREMENT,
  `id_reparacion` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `tipo` varchar(20) DEFAULT 'Ingreso',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_imagen`),
  KEY `fk_rep_img_reparacion` (`id_reparacion`),
  CONSTRAINT `fk_rep_img_reparacion` FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion` (`id_reparacion`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparacion_imagen`
--

LOCK TABLES `reparacion_imagen` WRITE;
/*!40000 ALTER TABLE `reparacion_imagen` DISABLE KEYS */;
INSERT INTO `reparacion_imagen` VALUES (1,8,'rep_8_1793397186_0.webp','Ingreso','2026-10-30 21:53:06'),(2,9,'rep_9_1793408695_0.webp','Ingreso','2026-10-31 01:04:55'),(4,11,'rep_11_1793747577_0.webp','Ingreso','2026-11-03 23:12:57'),(6,12,'rep_12_1793805029_0.webp','Ingreso','2026-11-04 15:10:29');
/*!40000 ALTER TABLE `reparacion_imagen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparacion_repuesto`
--

DROP TABLE IF EXISTS `reparacion_repuesto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reparacion_repuesto` (
  `id_reparacion_repuesto` int(11) NOT NULL AUTO_INCREMENT,
  `id_reparacion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_reparacion_repuesto`),
  KEY `fk_rep_rep_reparacion` (`id_reparacion`),
  KEY `fk_rep_rep_producto` (`id_producto`),
  CONSTRAINT `fk_rep_rep_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `fk_rep_rep_reparacion` FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion` (`id_reparacion`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparacion_repuesto`
--

LOCK TABLES `reparacion_repuesto` WRITE;
/*!40000 ALTER TABLE `reparacion_repuesto` DISABLE KEYS */;
INSERT INTO `reparacion_repuesto` VALUES (1,1,3,1,10000.00),(2,2,4,1,10000.00),(3,4,14,1,5000.00),(4,5,15,1,10000.00),(5,6,4,1,10000.00),(6,8,20,1,10000.00),(7,8,4,1,10000.00),(8,9,3,1,10000.00),(9,9,23,1,70000.00),(10,11,3,1,10000.00),(14,10,14,1,5000.00),(15,10,23,1,70000.00),(16,12,14,1,5000.00),(17,12,23,1,70000.00),(18,14,25,1,5000.00),(19,14,3,1,10000.00),(20,18,20,1,10000.00),(21,18,23,1,70000.00),(22,19,20,1,10000.00),(23,19,23,1,70000.00);
/*!40000 ALTER TABLE `reparacion_repuesto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','empleado') DEFAULT 'empleado',
  `email` varchar(255) DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_baja` datetime DEFAULT NULL,
  `token_verificacion` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `modulos_permitidos` text DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `id_persona` (`id_persona`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (8,12,'facuE','$2y$10$nOgZXCHwxdO9awS9Vyt8bu82ZDUj.trysNKMXhXh4.OCjHmrxe6By','admin','facundoesquivel03@gmail.com',1,1,NULL,'5a005ddc08594ae420773f4ab73d29d168be1b130dcf7ee87ec1d21ed1b30f89','2026-11-17 18:20:52','dashboard,venta,inventario,taller,clientes,caja,proveedores','f9022fca2dc84778ea3da8f70aaffd10de9636facbdd31770403fb50df79db0d'),(18,22,'santiM','$2y$10$L/N6DPgW5Nf/fiqkD1zTO.up/x0j/fT9WfpoPG4ctuqBZCiZW18da','empleado','scdm0407@gmail.com',1,1,NULL,NULL,NULL,'dashboard,venta,caja,inventario,clientes','8cb3dcae7e26d6edc923c44713325bc99730be45b25c458bd51c31f1b823ecff'),(23,27,'omarE','$2y$10$fGxa/20tpF0yOVjHDX6tV.M.qEEsDwv3z7Mzgc56E/2OIBuD5/fcy','empleado','facuesquivel338@gmail.com',1,1,NULL,NULL,NULL,'dashboard,venta,caja,inventario,clientes','1862d5c1e92e3ff6c4abc7641c8ade7bc15fe3d99c934a45be7b09516e25d09c');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_de_pago` varchar(100) DEFAULT 'Efectivo',
  `id_metodo_pago` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('completada','anulada') NOT NULL DEFAULT 'completada',
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_usuario` (`id_usuario`),
  KEY `fk_venta_metodo` (`id_metodo_pago`),
  CONSTRAINT `fk_venta_metodo` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id_metodo_pago`),
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
INSERT INTO `venta` VALUES (1,'2026-03-24 02:19:09',500000.00,'Tarjeta',NULL,NULL,NULL,'completada'),(2,'2026-03-25 18:25:45',40000.00,'Efectivo',1,NULL,NULL,'completada'),(3,'2026-03-25 21:08:22',360000.00,'Transferencia',4,NULL,NULL,'completada'),(4,'2026-10-06 00:47:21',10000.00,'Efectivo',NULL,8,'','completada'),(5,'2026-10-06 00:47:29',10000.00,'Tarjeta',NULL,8,'','completada'),(6,'2026-10-06 00:47:36',500000.00,'Transferencia',NULL,8,'','anulada'),(9,'2026-10-17 14:26:20',310000.00,'Efectivo',NULL,8,'Cobro de Reparación #26106974 - Iphone 14','completada'),(10,'2026-10-17 18:11:55',30000.00,'Transferencia',NULL,8,'Cobro de Reparación #26104177 - Motorola  G52','completada'),(11,'2026-10-17 19:25:24',20000.00,'Efectivo',NULL,8,'Cobro de Reparación #26103856 - Huawei k20','completada'),(12,'2026-10-17 20:07:06',20000.00,'Efectivo',NULL,8,'Cobro de Reparación #26103856 - Huawei k20','anulada'),(13,'2026-10-19 11:53:21',20000.00,'Transferencia',NULL,8,'Cobro de Reparación #26103856 - Huawei k20','anulada'),(14,'2026-10-19 21:11:06',225000.00,'Tarjeta',NULL,11,'Cobro de Reparación #26108982 - Iphone 16','completada'),(15,'2026-10-21 17:08:30',180000.00,'Efectivo',NULL,8,'Cobro de Reparación #26106226 - Iphone 11','anulada'),(16,'2026-10-21 17:09:02',180000.00,'Efectivo',NULL,8,'Cobro de Reparación #26106226 - Iphone 11','anulada'),(17,'2026-10-28 13:00:00',90000.00,'Efectivo',NULL,8,'','anulada'),(18,'2026-10-28 22:24:16',310000.00,'Transferencia',NULL,8,'Cobro de Reparación #26107372 - Iphone 16','completada'),(19,'2026-10-30 14:13:14',150000.00,'Efectivo',NULL,12,'Cobro de Reparación #26105933 - Samsung A14','completada'),(20,'2026-10-30 18:54:52',320000.00,'Transferencia',NULL,12,'Cobro de Reparación #26102510 - Samsung S26 ULTRA','anulada'),(21,'2026-10-30 22:06:17',430000.00,'Efectivo',NULL,12,'Cobro de Reparación #26104388 - Iphone 11','anulada'),(22,'2026-11-03 20:28:56',110000.00,'Efectivo',NULL,15,'Cobro de Reparación #26117220 - Samsung A17','completada'),(23,'2026-11-04 11:19:21',225000.00,'Tarjeta',NULL,8,'Cobro de Reparación #26115866 - Samsung A56','anulada'),(24,'2026-11-04 12:12:08',375000.00,'Transferencia',NULL,8,'Cobro de Reparación #26119155 - Samsung S25','anulada'),(25,'2026-11-06 13:56:18',375000.00,'Tarjeta',NULL,8,'Cobro de Reparación #26119155 - Samsung S25','completada'),(26,'2026-11-10 20:39:09',30000.00,'Tarjeta',NULL,8,'Cobro de Reparación #26118134 - Iphone 11','completada'),(27,'2026-11-10 22:01:14',0.00,'Tarjeta',NULL,8,'Cobro de Reparación #26117681 - Samsung A14','anulada'),(28,'2026-11-11 02:22:58',150000.00,'Transferencia',NULL,8,'Cobro de Reparación #26117593 - Iphone 13','completada'),(29,'2026-11-11 02:24:43',165000.00,'Efectivo',NULL,8,'Cobro de Reparación #26114413 - Iphone 11 PRO','anulada'),(30,'2026-11-11 13:09:46',300000.00,'Transferencia',NULL,8,'Cobro de Reparación #26113575 - Samsung S25','completada'),(31,'2026-11-11 13:13:46',250000.00,'Efectivo',NULL,8,'','completada'),(32,'2026-11-16 16:38:10',170000.00,'Tarjeta',NULL,8,'','anulada'),(37,'2026-11-17 17:24:56',130000.00,'Efectivo + Transferencia',NULL,8,'Cobro de Reparación #26119469 - Samsung k20','completada'),(39,'2026-11-17 20:53:29',130000.00,'Transferencia',NULL,8,'Cobro de Reparación #26118024 - Samsung A56','anulada');
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_pago`
--

DROP TABLE IF EXISTS `venta_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta_pago` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `idx_venta_pago_venta` (`id_venta`),
  KEY `idx_venta_pago_metodo` (`metodo_pago`),
  CONSTRAINT `fk_venta_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_pago`
--

LOCK TABLES `venta_pago` WRITE;
/*!40000 ALTER TABLE `venta_pago` DISABLE KEYS */;
INSERT INTO `venta_pago` VALUES (1,1,'Tarjeta',500000.00,'2026-11-16 16:36:48'),(2,2,'Efectivo',40000.00,'2026-11-16 16:36:48'),(3,3,'Transferencia',360000.00,'2026-11-16 16:36:48'),(4,4,'Efectivo',10000.00,'2026-11-16 16:36:48'),(5,5,'Tarjeta',10000.00,'2026-11-16 16:36:48'),(6,6,'Transferencia',500000.00,'2026-11-16 16:36:48'),(7,9,'Efectivo',310000.00,'2026-11-16 16:36:48'),(8,10,'Transferencia',30000.00,'2026-11-16 16:36:48'),(9,11,'Efectivo',20000.00,'2026-11-16 16:36:48'),(10,12,'Efectivo',20000.00,'2026-11-16 16:36:48'),(11,13,'Transferencia',20000.00,'2026-11-16 16:36:48'),(12,14,'Tarjeta',225000.00,'2026-11-16 16:36:48'),(13,15,'Efectivo',180000.00,'2026-11-16 16:36:48'),(14,16,'Efectivo',180000.00,'2026-11-16 16:36:48'),(15,17,'Efectivo',90000.00,'2026-11-16 16:36:48'),(16,18,'Transferencia',310000.00,'2026-11-16 16:36:48'),(17,19,'Efectivo',150000.00,'2026-11-16 16:36:48'),(18,20,'Transferencia',320000.00,'2026-11-16 16:36:48'),(19,21,'Efectivo',430000.00,'2026-11-16 16:36:48'),(20,22,'Efectivo',110000.00,'2026-11-16 16:36:48'),(21,23,'Tarjeta',225000.00,'2026-11-16 16:36:48'),(22,24,'Transferencia',375000.00,'2026-11-16 16:36:48'),(23,25,'Tarjeta',375000.00,'2026-11-16 16:36:48'),(24,26,'Tarjeta',30000.00,'2026-11-16 16:36:48'),(25,28,'Transferencia',150000.00,'2026-11-16 16:36:48'),(26,29,'Efectivo',165000.00,'2026-11-16 16:36:48'),(27,30,'Transferencia',300000.00,'2026-11-16 16:36:48'),(28,31,'Efectivo',250000.00,'2026-11-16 16:36:48'),(32,32,'Tarjeta',170000.00,'2026-11-16 16:38:10'),(39,37,'Efectivo',50000.00,'2026-11-17 17:24:56'),(40,37,'Transferencia',80000.00,'2026-11-17 17:24:56'),(43,39,'Transferencia',130000.00,'2026-11-17 20:53:29');
/*!40000 ALTER TABLE `venta_pago` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-18 18:44:26
