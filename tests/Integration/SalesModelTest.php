<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/DatabaseTestCase.php';
require_once dirname(__DIR__, 2) . '/modules/sales/Models/SalesModel.php';

final class SalesModelTest extends DatabaseTestCase
{
    public function testCashSaleWithoutOpenCajaThrowsAndPreservesStock(): void
    {
        $idProd = $this->createProduct('Funda Silicona', 2500.0, 10);
        $sales = new SalesModel();

        $threw = false;
        try {
            $sales->create_sale_transaction(
                [['id' => $idProd, 'cantidad' => 2]],
                'Efectivo',
                $this->adminUserId()
            );
        } catch (Exception $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
        $this->assertSame(10, $this->stockOf($idProd));
    }

    public function testCashSaleWithOpenCajaDeductsStockAndRecordsCaja(): void
    {
        $idCaja = $this->openFreshCaja(10000.0);
        $idProd = $this->createProduct('Vidrio Templado', 1500.0, 8);
        $sales = new SalesModel();

        $idVenta = $sales->create_sale_transaction(
            [['id' => $idProd, 'cantidad' => 2]],
            'Efectivo',
            $this->adminUserId()
        );

        $this->assertGreaterThan(0, $idVenta);
        $this->assertSame(6, $this->stockOf($idProd));

        $venta = $sales->find_sale_by_id($idVenta);
        $this->assertSame(3000.0, (float)$venta['total']);
        $this->assertSame('completada', $venta['estado']);

        $pagos = $sales->get_sale_payments($idVenta);
        $this->assertCount(1, $pagos);
        $this->assertSame('Efectivo', $pagos[0]['metodo_pago']);
        $this->assertSame(3000.0, $pagos[0]['monto']);

        $totals = (new CajaModel())->calculate_totals($idCaja, 10000.0);
        $this->assertSame(3000.0, $totals['ingresos']);
        $this->assertSame(13000.0, $totals['esperado']);
    }

    public function testCombinedPaymentUpdatesCajaOnlyForCashPortion(): void
    {
        $idCaja = $this->openFreshCaja(5000.0);
        $idProd = $this->createProduct('Auriculares BT', 10000.0, 5);
        $sales = new SalesModel();

        $idVenta = $sales->create_sale_transaction(
            [['id' => $idProd, 'cantidad' => 1]],
            [
                ['metodo' => 'Efectivo', 'monto' => 4000.0],
                ['metodo' => 'Transferencia', 'monto' => 6000.0],
            ],
            $this->adminUserId()
        );

        $pagos = $sales->get_sale_payments($idVenta);
        $this->assertCount(2, $pagos);

        $totals = (new CajaModel())->calculate_totals($idCaja, 5000.0);
        $this->assertSame(4000.0, $totals['ingresos']);
        $this->assertSame(9000.0, $totals['esperado']);
        $this->assertSame(6000.0, $totals['otros_medios']['transferencia']);
    }

    public function testDiscountLimitsAndCalculation(): void
    {
        $idProd = $this->createProduct('Cable Original', 5000.0, 10);
        $sales = new SalesModel();
        $uid = $this->adminUserId();

        // Con descuento permitido (ej. empleado max 15%): 10% pasa
        $idVenta = $sales->create_sale_transaction(
            [['id' => $idProd, 'cantidad' => 1]],
            'Transferencia',
            $uid,
            '',
            ['tipo' => 'porcentaje', 'valor' => 10],
            SalesModel::DESCUENTO_MAX_PCT_EMPLEADO
        );
        $venta = $sales->find_sale_by_id($idVenta);
        $this->assertSame(500.0, (float)$venta['descuento']);
        $this->assertSame(4500.0, (float)$venta['total']);

        // Exceder tope del 15% falla
        $failedOverLimit = false;
        try {
            $sales->create_sale_transaction(
                [['id' => $idProd, 'cantidad' => 1]],
                'Transferencia',
                $uid,
                '',
                ['tipo' => 'porcentaje', 'valor' => 20],
                SalesModel::DESCUENTO_MAX_PCT_EMPLEADO
            );
        } catch (Exception $e) {
            $failedOverLimit = true;
        }
        $this->assertTrue($failedOverLimit);

        // Sin permiso de descuentos (max = 0) rechaza cualquier descuento
        $this->expectException(Exception::class);
        $sales->create_sale_transaction(
            [['id' => $idProd, 'cantidad' => 1]],
            'Transferencia',
            $uid,
            '',
            ['tipo' => 'monto', 'valor' => 100],
            0.0
        );
    }

    public function testRollbackSaleRestoresStockAndReversesCashInCaja(): void
    {
        $idCaja = $this->openFreshCaja(5000.0);
        $idPhone = $this->createProduct('Motorola G54', 120000.0, 3, 1);
        $sales = new SalesModel();
        $uid = $this->adminUserId();

        $idVenta1 = $sales->create_sale_transaction(
            [['id' => $idPhone, 'cantidad' => 1]],
            'Efectivo',
            $uid
        );
        $this->assertGreaterThan(0, $idVenta1);
        $this->assertSame(2, $this->stockOf($idPhone));

        $totalsBefore = (new CajaModel())->calculate_totals($idCaja, 5000.0);
        $this->assertSame(125000.0, $totalsBefore['esperado']);

        // Anular la venta devuelve stock y revierte caja en efectivo
        $this->assertTrue($sales->rollback_sale($idVenta1, $uid));
        $this->assertSame(3, $this->stockOf($idPhone));

        $venta1After = $sales->find_sale_by_id($idVenta1);
        $this->assertSame('anulada', $venta1After['estado']);

        $totalsAfter = (new CajaModel())->calculate_totals($idCaja, 5000.0);
        $this->assertSame(5000.0, $totalsAfter['esperado']);
    }
}
