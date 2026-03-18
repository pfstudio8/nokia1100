# Verification Walkthrough - Login & Registration System

I have implemented the login and registration system with the requested features and a premium design.

## Prerequisites
Ensure your MySQL database `nokia1100_db` exists and has the `persona` and `usuario` tables.

## Verification Steps

### 1. Test Registration
1.  Open `http://localhost/nokia1100/index.php` in your browser.
2.  Click on **"Regístrate aquí"**.
3.  Fill in the personal details (Nombre, Apellido, etc.).
4.  Fill in the user details (Usuario, Contraseña).
5.  Select a **Role** (e.g., "Empleado").
6.  Click **"Registrarse"**.
7.  You should be redirected to the login page with a success message.

### 2. Test Login (Employee)
1.  On the login page, enter the credentials you just created.
2.  Click **"Iniciar Sesión"**.
3.  Verify you are redirected to the **Employee Dashboard** (`employee_dashboard.php`).
4.  Click **"Cerrar Sesión"**.

### 3. Test Login (Admin)
1.  Register a new user with the **"Administrador"** role.
2.  Login with these new credentials.
3.  Verify you are redirected to the **Admin Dashboard** (`admin_dashboard.php`).
4.  Click on **"Ver Stock"** in the Inventario card.
5.  Verify you see the list of products (ensure you have added some products to the DB manually or via SQL).

### 4. Test New Sale (Employee)
1.  Login as an Employee.
2.  Click **"Nueva Venta"**.
3.  Select a product, enter quantity, and payment method.
4.  Click **"Registrar Venta"**.
5.  Verify success message and that the total is correct.
6.  Go to **"Consultar Stock"** and verify the quantity decreased.
4.  Click on **"Ver Detalle"** in the Ventas card.
5.  Verify you see the list of sales (or "No hay ventas" if empty).
6.  Click **"Descargar Excel"** and verify a CSV file is downloaded.
