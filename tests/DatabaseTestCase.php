<?php
// tests/DatabaseTestCase.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/modules/inventory/Models/InventoryModel.php';
require_once dirname(__DIR__) . '/modules/caja/Models/CajaModel.php';

abstract class DatabaseTestCase extends TestCase
{
    protected mysqli $conn;

    /** @var array<int, int> */
    protected array $createdProductIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $db = new Database();
        $this->conn = $db->getConnection();

        $resDb = $this->conn->query('SELECT DATABASE() AS db');
        $activeDb = $resDb ? (string)($resDb->fetch_assoc()['db'] ?? '') : '';
        if ($resDb) {
            $resDb->free();
        }
        $expectedDb = (string)($_ENV['DB_NAME'] ?? '');
        $this->assertSame($expectedDb, $activeDb, 'La conexión de test debe apuntar a la base *_test.');
        $this->assertStringEndsWith('_test', $activeDb);

        $this->truncateTransactionalTables();
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdProductIds) && isset($this->conn)) {
            $this->conn->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->createdProductIds as $pid) {
                $stmtInv = $this->conn->prepare('DELETE FROM inventario WHERE id_producto = ?');
                if ($stmtInv) {
                    $stmtInv->bind_param('i', $pid);
                    $stmtInv->execute();
                    $stmtInv->close();
                }
                $stmtDet = $this->conn->prepare('DELETE FROM producto_detalle WHERE id_producto = ?');
                if ($stmtDet) {
                    $stmtDet->bind_param('i', $pid);
                    $stmtDet->execute();
                    $stmtDet->close();
                }
                $stmtProd = $this->conn->prepare('DELETE FROM producto WHERE id_producto = ?');
                if ($stmtProd) {
                    $stmtProd->bind_param('i', $pid);
                    $stmtProd->execute();
                    $stmtProd->close();
                }
            }
            $this->conn->query('SET FOREIGN_KEY_CHECKS=1');
            $this->createdProductIds = [];
        }
        $_SESSION = [];
        parent::tearDown();
    }

    private function truncateTransactionalTables(): void
    {
        $tables = [
            'venta_pago',
            'detalle_venta',
            'caja_movimiento',
            'caja',
            'venta',
            'notificacion_email',
            'reparacion_imagen',
            'reparacion_historial',
            'reparacion_repuesto',
            'reparacion',
            'cliente',
            'detalle_compra',
            'compra',
            'audit_log',
        ];

        $this->conn->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            $this->conn->query("TRUNCATE TABLE `{$t}`");
        }
        $this->conn->query('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function adminUserId(): int
    {
        $res = $this->conn->query("SELECT id_usuario FROM usuario WHERE rol = 'admin' AND is_active = 1 ORDER BY id_usuario ASC LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            $id = (int)$row['id_usuario'];
            $res->free();
            return $id;
        }
        if ($res) {
            $res->free();
        }

        $hash = password_hash('TestPass123!', PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare(
            "INSERT INTO usuario (nombre, apellido, nombre_usuario, email, password, rol, is_active, email_verified)
             VALUES ('Admin', 'Test', 'admin_phpunit', 'admin_phpunit@test.local', ?, 'admin', 1, 1)"
        );
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    protected function createProduct(
        string $nombre,
        float $precio,
        int $stock,
        int $min = 2,
        ?string $codigo = null
    ): int {
        $inv = new InventoryModel();
        $code = $codigo ?? ('TST' . random_int(100000, 999999));
        $id = (int)$inv->create_product_transaction(
            ['nombre' => $nombre, 'precio' => $precio],
            [
                'marca'        => 'NokiaTest',
                'modelo'       => 'M1',
                'categoria'    => 'Celulares',
                'codigo'       => $code,
                'descripcion'  => 'Producto de prueba automatizada',
                'stock_minimo' => $min,
            ],
            $stock
        );
        $this->createdProductIds[] = $id;
        return $id;
    }

    protected function stockOf(int $idProducto): int
    {
        $stmt = $this->conn->prepare('SELECT cantidad FROM inventario WHERE id_producto = ?');
        $stmt->bind_param('i', $idProducto);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['cantidad'] ?? 0);
    }

    protected function openFreshCaja(float $apertura = 10000.0): int
    {
        $caja = new CajaModel();
        $id = $caja->open_caja($apertura, $this->adminUserId());
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        return $id;
    }
}
