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
3. Importa el archivo SQL completo con la estructura actualizada (20 tablas, claves foráneas) y los datos iniciales:
   * Archivo a importar: `database/nokia1100_actual.sql`

*Nota: La base de datos ya incluye la estructura de tablas de negocio, seguridad y auditoría requeridas para el funcionamiento del sistema.*

### 3. Configurar la Conexión en PHP y las Variables de Entorno
El sistema utiliza un archivo `.env` para manejar todas las contraseñas y variables sensibles.

1. En la raíz del proyecto, copia el archivo `.env.example` y renómbralo a `.env`.
2. Abre el nuevo archivo `.env` y verifica que los datos de conexión a la base de datos correspondan a tu entorno local:

```env
# Configuración de Base de Datos
DB_HOST=localhost
DB_USER=root
DB_PASS=tu_contraseña_aqui
DB_NAME=nokia1100
BASE_URL=/nokia1100
```

### 4. Configurar el Envío de Correos (SMTP) y Notificaciones
El sistema envía correos de verificación de cuenta, recuperación de contraseñas y avisos automáticos de estado de reparación en taller al cliente.

1. En el mismo archivo `.env` que creaste en el paso anterior, edita la sección de SMTP y banderas opcionales:

```env
# Configuración de Correo (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_correo@gmail.com
SMTP_PASS=tu_contraseña_de_aplicacion
SMTP_FROM_EMAIL=tu_correo@gmail.com
SMTP_FROM_NAME="Nokia 1100 System"

# Notificaciones por correo al cliente en taller
NOTIFY_CLIENT_EMAILS=true
```

### 5. Ejecutar Tests Automatizados (PHPUnit)
El proyecto incluye pruebas unitarias y de integración aisladas sobre la base de pruebas `nokia1100_test`:

```powershell
C:\xampp\php\php.exe vendor\phpunit\phpunit\phpunit --testdox
```

Si tu usuario `DB_USER` del `.env` no tiene permiso `CREATE DATABASE`, indicá credenciales administrativas en la consola antes de correr PHPUnit (`$env:DB_TEST_USER='root'; $env:DB_TEST_PASS=''`).

---

## Acceso al Sistema

Una vez configurado, abre tu navegador e ingresa a: `http://localhost/nokia1100`

### Credenciales de Prueba por Defecto
El archivo de volcado de base de datos (`database/nokia1100_actual.sql`) incluye usuarios iniciales con diferentes roles. Puedes ingresar o registrar un nuevo usuario desde la pantalla inicial.

* **Administrador:**
  * Usuario: `admin`
  * Contraseña: `admin123`
* **Empleado:**
  * Usuario: `empleado`
  * Contraseña: `empleado123`
