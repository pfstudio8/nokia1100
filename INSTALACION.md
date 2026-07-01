# Guía de Instalación — Nokia 1100 System

Este documento describe los pasos necesarios para desplegar y configurar el sistema de gestión Nokia 1100 en un entorno local utilizando XAMPP.

---

## Requisitos Previos

1. Servidor local con **PHP 8.x** y **MySQL / MariaDB** (se recomienda usar **XAMPP**).
2. Servidor web configurado para interpretar PHP.
3. Cuenta de correo con soporte SMTP (opcional, requerida para el envío de correos de verificación y recuperación de contraseña).

---

## Pasos para la Instalación

### 1. Ubicar el proyecto
Copia o clona la carpeta del proyecto dentro del directorio raíz de tu servidor web.
* En XAMPP (Windows): `C:\xampp\htdocs\nokia1100`

### 2. Configurar la Base de Datos
1. Inicia los servicios de Apache y MySQL en el Panel de Control de XAMPP.
2. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`) y crea una base de datos llamada `nokia1100`.
3. Importa el archivo SQL completo con la estructura y los datos de prueba iniciales:
   * Archivo a importar: [database/bd_nokia1100.sql](file:///c:/xampp/htdocs/nokia1100/database/bd_nokia1100.sql)
   * Si prefieres importar solo la estructura limpia sin datos de ejemplo, utiliza: [database/nokia1100_estructura.sql](file:///c:/xampp/htdocs/nokia1100/database/nokia1100_estructura.sql)

*Nota: La base de datos ya incluye la estructura de tablas de negocio, seguridad y auditoría requeridas para el funcionamiento del sistema.*

### 3. Configurar la Conexión en PHP
Abre el archivo de configuración de la base de datos en [config/db.php](file:///c:/xampp/htdocs/nokia1100/config/db.php) y verifica que los datos de conexión correspondan a tu entorno local:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Contraseña de tu MySQL
define('DB_NAME', 'nokia1100');
define('BASE_URL', '/nokia1100'); // Ruta relativa en el servidor web
```

### 4. Configurar el Envío de Correos (SMTP)
El sistema envía correos de verificación al registrar cuentas y de recuperación de contraseñas. Para configurar las credenciales:
1. Abre [config/config_mail.php](file:///c:/xampp/htdocs/nokia1100/config/config_mail.php).
2. Configura los parámetros SMTP de tu de correo (ej. Gmail, Outlook) o crea un archivo personalizado `config/mail.local.php` para sobrescribir las constantes de forma local:

```php
<?php
// Ejemplo para config/mail.local.php
define('SMTP_USER', 'tu_correo@gmail.com');
define('SMTP_PASS', 'tu_contraseña_de_aplicacion');
```

---

## Acceso al Sistema

Una vez configurado, abre tu navegador e ingresa a: `http://localhost/nokia1100`

### Credenciales de Prueba por Defecto
El archivo de volcado de base de datos (`bd_nokia1100.sql`) incluye usuarios iniciales con diferentes roles. Puedes ingresar con:

* **Administrador:**
  * Usuario: `admin`
  * Contraseña: `admin123`
* **Empleado:**
  * Usuario: `empleado`
  * Contraseña: `empleado123`
