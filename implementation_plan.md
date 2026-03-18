# Implementation Plan - Login & Registration System

We will build a PHP/MySQL application for the "Nokia 1100" project. The system will feature a secure login and registration flow with role-based access control (Admin/Employee).

## User Review Required
> [!IMPORTANT]
> I will assume the database name is `nokia1100`. You will need to create this database and import the provided SQL schema (which I will generate) or ensure your existing database matches the connection settings in `db.php`.

## Proposed Changes




### Backend (PHP)
#### [NEW] [db.php](file:///c:/xampp/htdocs/nokia1100/db.php)
- Database connection configuration.

#### [NEW] [auth.php](file:///c:/xampp/htdocs/nokia1100/auth.php)
- Handles login requests.
- Verifies credentials.
- Redirects based on `rol` (admin/empleado).

#### [NEW] [register_process.php](file:///c:/xampp/htdocs/nokia1100/register_process.php)
- Handles registration requests.
- Inserts data into `persona` first, then `usuario`.
- Hashes passwords.

### Frontend (HTML/PHP/CSS)
#### [NEW] [style.css](file:///c:/xampp/htdocs/nokia1100/style.css)
- Modern, premium styling (Glassmorphism/Dark mode).

#### [NEW] [index.php](file:///c:/xampp/htdocs/nokia1100/index.php)
- Login page.
- Fields: Username, Password.
- Link to Registration.

#### [NEW] [register.php](file:///c:/xampp/htdocs/nokia1100/register.php)
- Registration page.
- Fields: Personal info (Name, Surname, DNI, Email, etc.) + User info (Username, Password, Role selection).

#### [NEW] [admin_dashboard.php](file:///c:/xampp/htdocs/nokia1100/admin_dashboard.php)
- Protected route for Admins.

#### [NEW] [employee_dashboard.php](file:///c:/xampp/htdocs/nokia1100/employee_dashboard.php)
- Protected route for Employees.

### Sales Module
#### [NEW] [sales.php](file:///c:/xampp/htdocs/nokia1100/sales.php)
- Lists all sales from the `venta` table.
- Shows details like Date, Total, Payment Method.
- Includes a button to "Export to Excel".

#### [NEW] [export_sales.php](file:///c:/xampp/htdocs/nokia1100/export_sales.php)
- Generates a CSV/Excel file of the sales data.
- Headers: ID, Fecha, Total, Metodo Pago.

### Inventory & Sales Registration
#### [NEW] [inventory.php](file:///c:/xampp/htdocs/nokia1100/inventory.php)
- Lists products and their current stock.
- Joins `inventario` and `producto` tables.

#### [NEW] [new_sale.php](file:///c:/xampp/htdocs/nokia1100/new_sale.php)
- Form to register a new sale.
- Select product (dropdown), Quantity, Payment Method.
- Updates `inventario` (decreases stock).
- Inserts into `venta` and `detalle_venta`.

#### [NEW] [add_stock.php](file:///c:/xampp/htdocs/nokia1100/add_stock.php)
- Form to add new products to inventory.
- Fields: Product Name, Brand, Model, Price, Quantity.
- Logic to insert into `producto`, `producto_detalle`, and `inventario`.


## Verification Plan
### Manual Verification

2.  **Registration**: Go to `register.php`, create a new user.
3.  **Login**: Go to `index.php`, login with the new credentials.
4.  **Redirection**: Verify redirection to the correct dashboard.
5.  **Add Stock**: Go to `add_stock.php` (via dashboard), add a new product.
6.  **Verify Inventory**: Check `inventory.php` to see the new product.

