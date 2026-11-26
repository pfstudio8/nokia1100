<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/modules/sales/Models/SalesModel.php';

final class PaymentValidationTest extends TestCase
{
    public function testSinglePaymentFallback(): void
    {
        $res = SalesModel::validate_and_normalize_payments([], 'Efectivo', 1500.50);

        $this->assertFalse($res['es_combinado']);
        $this->assertSame('Efectivo', $res['metodo_resumen']);
        $this->assertSame(1500.50, $res['monto_efectivo']);
        $this->assertCount(1, $res['pagos']);
        $this->assertSame(1500.50, $res['pagos'][0]['monto']);
    }

    public function testCombinedTwoMethodPaymentsSumToTotal(): void
    {
        $pagos = [
            ['metodo' => 'Efectivo', 'monto' => 600.00],
            ['metodo' => 'Transferencia', 'monto' => 400.00],
        ];
        $res = SalesModel::validate_and_normalize_payments($pagos, '', 1000.00);

        $this->assertTrue($res['es_combinado']);
        $this->assertSame('Efectivo + Transferencia', $res['metodo_resumen']);
        $this->assertSame(600.00, $res['monto_efectivo']);
        $this->assertCount(2, $res['pagos']);
    }

    public function testRejectsMoreThanTwoMethods(): void
    {
        $this->expectException(Exception::class);
        SalesModel::validate_and_normalize_payments([
            ['metodo' => 'Efectivo', 'monto' => 300],
            ['metodo' => 'Transferencia', 'monto' => 300],
            ['metodo' => 'Tarjeta', 'monto' => 400],
        ], '', 1000.00);
    }

    public function testRejectsMismatchedPaymentSum(): void
    {
        $this->expectException(Exception::class);
        SalesModel::validate_and_normalize_payments([
            ['metodo' => 'Efectivo', 'monto' => 500],
            ['metodo' => 'Transferencia', 'monto' => 400],
        ], '', 1000.00);
    }

    public function testRejectsZeroOrNegativeCombinedAmount(): void
    {
        $this->expectException(Exception::class);
        SalesModel::validate_and_normalize_payments([
            ['metodo' => 'Efectivo', 'monto' => 1000],
            ['metodo' => 'Transferencia', 'monto' => -50],
        ], '', 950.00);
    }

    public function testRejectsInvalidMethodName(): void
    {
        $this->expectException(Exception::class);
        SalesModel::validate_and_normalize_payments([], 'Criptomoneda', 500.00);
    }

    public function testRejectsDuplicatePaymentMethodInCombined(): void
    {
        $this->expectException(Exception::class);
        SalesModel::validate_and_normalize_payments([
            ['metodo' => 'Efectivo', 'monto' => 500],
            ['metodo' => 'Efectivo', 'monto' => 500],
        ], '', 1000.00);
    }
}
