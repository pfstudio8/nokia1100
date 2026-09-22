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

### 4. Configurar el Envío de Correos (SMTP)
El sistema envía correos de verificación al registrar cuentas y de recuperación de contraseñas. 

1. En el mismo archivo `.env` que creaste en el paso anterior, edita la sección de SMTP con las credenciales de tu proveedor de correo (ej. Gmail, Outlook):

```env
# Configuración de Correo (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_correo@gmail.com
SMTP_PASS=tu_contraseña_de_aplicacion
SMTP_FROM_EMAIL=tu_correo@gmail.com
SMTP_FROM_NAME="Nokia 1100 System"
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
