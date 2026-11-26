<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/DatabaseTestCase.php';
require_once dirname(__DIR__, 2) . '/modules/sales/Models/SalesModel.php';

final class CajaModelTest extends DatabaseTestCase
{
    public function testOpenCajaPreventsDuplicateOpenSession(): void
    {
        $cajaModel = new CajaModel();
        $id1 = $cajaModel->open_caja(5000.0, $this->adminUserId());
        $this->assertIsInt($id1);
        $this->assertGreaterThan(0, $id1);

        $id2 = $cajaModel->open_caja(2000.0, $this->adminUserId());
        $this->assertFalse($id2);
    }

    public function testAddMovementCalculatesCashAndNonCashTotals(): void
    {
        $idCaja = $this->openFreshCaja(10000.0);
        $uid = $this->adminUserId();
        $cajaModel = new CajaModel();

        $m1 = $cajaModel->add_movement($idCaja, 'in', 'Cambio inicial', 'Cambio', 1500.0, $uid, 'Efectivo');
        $m2 = $cajaModel->add_movement($idCaja, 'out', 'Retiro proveedor', 'Insumos', 800.0, $uid, 'Efectivo');
        $m3 = $cajaModel->add_movement($idCaja, 'in', 'Seña cliente', 'Transf banc', 2500.0, $uid, 'Transferencia');

        $this->assertIsInt($m1);
        $this->assertIsInt($m2);
        $this->assertIsInt($m3);

        $totals = $cajaModel->calculate_totals($idCaja, 10000.0);
        $this->assertSame(1500.0, $totals['ingresos']);
        $this->assertSame(800.0, $totals['egresos']);
        $this->assertSame(10700.0, $totals['esperado']);
        $this->assertSame(2500.0, $totals['otros_medios']['transferencia']);
    }

    public function testReverseMovementCreatesContraEntryAndBlocksDoubleOrSaleReversal(): void
    {
        $idCaja = $this->openFreshCaja(5000.0);
        $uid = $this->adminUserId();
        $cajaModel = new CajaModel();

        $idMov = $cajaModel->add_movement($idCaja, 'out', 'Gasto caja', 'Envío', 500.0, $uid, 'Efectivo');
        $this->assertIsInt($idMov);

        $rev = $cajaModel->reverse_movement($idMov, $idCaja, 'Error de carga', $uid);
        $this->assertSame('in', $rev['nuevo_tipo']);
        $this->assertSame(500.0, $rev['monto']);

        $totals = $cajaModel->calculate_totals($idCaja, 5000.0);
        $this->assertSame(5000.0, $totals['esperado']);

        // Bloquear doble reversión del mismo movimiento
        $secondFailed = false;
        try {
            $cajaModel->reverse_movement($idMov, $idCaja, 'Segundo intento', $uid);
        } catch (Exception $e) {
            $secondFailed = true;
        }
        $this->assertTrue($secondFailed);

        // Bloquear reversión directa de un movimiento vinculado a una venta
        $idProd = $this->createProduct('Cargador USB-C', 3000.0, 5);
        $salesModel = new SalesModel();
        $idVenta = $salesModel->create_sale_transaction(
            [['id' => $idProd, 'cantidad' => 1]],
            'Efectivo',
            $uid
        );

        $stmtMov = $this->conn->prepare("SELECT id_movimiento FROM caja_movimiento WHERE id_venta = ? LIMIT 1");
        $stmtMov->bind_param('i', $idVenta);
        $stmtMov->execute();
        $movSale = $stmtMov->get_result()->fetch_assoc();
        $stmtMov->close();
        $this->assertNotEmpty($movSale);

        $this->expectException(Exception::class);
        $cajaModel->reverse_movement((int)$movSale['id_movimiento'], $idCaja, 'Intento inválido', $uid);
    }

    public function testCloseCajaFreezesExpectedAndDifference(): void
    {
        $idCaja = $this->openFreshCaja(4000.0);
        $uid = $this->adminUserId();
        $cajaModel = new CajaModel();

        $cajaModel->add_movement($idCaja, 'in', 'Ingreso extra', 'Detalle', 1000.0, $uid, 'Efectivo');
        $closed = $cajaModel->close_caja($idCaja, 4900.0, 'Faltante de 100', $uid);

        $this->assertIsArray($closed);
        $this->assertSame(5000.0, $closed['monto_esperado']);
        $this->assertSame(4900.0, $closed['monto_cierre']);
        $this->assertSame(-100.0, $closed['diferencia']);

        $stored = $cajaModel->get_caja_by_id($idCaja);
        $this->assertSame('cerrada', $stored['estado']);
        $this->assertSame(-100.0, (float)$stored['diferencia']);
    }
}
