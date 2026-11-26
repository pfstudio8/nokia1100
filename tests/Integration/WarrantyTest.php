<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/DatabaseTestCase.php';
require_once dirname(__DIR__, 2) . '/modules/workshop/Models/WorkshopModel.php';

final class WarrantyTest extends DatabaseTestCase
{
    public function testDeliveredRepairGrants20DayWarrantyAndSupportsFreeReentry(): void
    {
        $uid = $this->adminUserId();
        $this->openFreshCaja(5000.0);
        $workshop = new WorkshopModel();
        $imei = '359999000011111';

        $idOrig = $workshop->create_repair_order(
            0,
            'Juan Test',
            '1122334455',
            'juan@test.local',
            'Samsung',
            'A54',
            $imei,
            'Cambio de pin',
            '',
            15000.0,
            $uid
        );
        $this->assertTrue($workshop->update_repair_status_and_budget($idOrig, 'Listo', 15000.0, 'Reparado', $uid));

        $idVenta = $workshop->charge_and_deliver_repair($idOrig, 'Efectivo', $uid);
        $this->assertGreaterThan(0, $idVenta);

        $orig = $workshop->find_repair_by_id($idOrig);
        $this->assertSame('Entregado', $orig['estado']);
        $expectedWarranty = date('Y-m-d', strtotime('+20 days'));
        $this->assertSame($expectedWarranty, $orig['garantia_hasta']);

        // Detección de garantía vigente por IMEI
        $active = $workshop->get_warranty_candidate($imei);
        $this->assertIsArray($active);
        $this->assertSame($idOrig, (int)$active['id_reparacion']);

        // Reingreso por garantía ($0)
        $idReentry = $workshop->create_repair_order(
            (int)$orig['id_cliente'],
            'Juan Test',
            '1122334455',
            'juan@test.local',
            'Samsung',
            'A54',
            $imei,
            'Revisión por garantía',
            '',
            9999.0,
            $uid,
            $idOrig
        );
        $reentry = $workshop->find_repair_by_id($idReentry);
        $this->assertSame(1, (int)$reentry['es_garantia']);
        $this->assertSame(0.0, (float)$reentry['presupuesto']);

        // Entrega sin cargo cuando es garantía $0
        $this->assertTrue($workshop->update_repair_status_and_budget($idReentry, 'Listo', 0.0, 'OK en garantía', $uid));
        $saleFree = $workshop->charge_and_deliver_repair($idReentry, 'Efectivo', $uid);
        $this->assertSame(0, $saleFree);

        $reentryDelivered = $workshop->find_repair_by_id($idReentry);
        $this->assertSame('Entregado', $reentryDelivered['estado']);
    }

    public function testVoidWarrantyMarksOriginalAndDisablesCandidate(): void
    {
        $uid = $this->adminUserId();
        $this->openFreshCaja(5000.0);
        $workshop = new WorkshopModel();
        $imei = '359999000022222';

        $idOrig = $workshop->create_repair_order(
            0,
            'Pedro Test',
            '1133445566',
            'pedro@test.local',
            'Motorola',
            'G84',
            $imei,
            'Cambio de módulo',
            '',
            20000.0,
            $uid
        );
        $this->assertTrue($workshop->update_repair_status_and_budget($idOrig, 'Listo', 20000.0, 'Listo', $uid));
        $this->assertGreaterThan(0, $workshop->charge_and_deliver_repair($idOrig, 'Efectivo', $uid));

        $orig = $workshop->find_repair_by_id($idOrig);
        $idReentryVoid = $workshop->create_repair_order(
            (int)$orig['id_cliente'],
            'Pedro Test',
            '1133445566',
            'pedro@test.local',
            'Motorola',
            'G84',
            $imei,
            'Equipo sulfatado',
            '',
            0.0,
            $uid,
            $idOrig
        );

        // Motivo inválido debe fallar
        $invalidReason = false;
        try {
            $workshop->void_warranty($idReentryVoid, 'Motivo inexistente', $uid);
        } catch (Exception $e) {
            $invalidReason = true;
        }
        $this->assertTrue($invalidReason);

        // Anulación válida marca tanto al reingreso como a la orden origen
        $this->assertTrue($workshop->void_warranty($idReentryVoid, 'Humedad / líquido', $uid));

        $voidedReentry = $workshop->find_repair_by_id($idReentryVoid);
        $voidedOrig = $workshop->find_repair_by_id($idOrig);
        $this->assertSame(0, (int)$voidedReentry['es_garantia']);
        $this->assertSame(1, (int)$voidedReentry['garantia_anulada']);
        $this->assertSame('Humedad / líquido', $voidedReentry['garantia_motivo_anulacion']);
        $this->assertSame(1, (int)$voidedOrig['garantia_anulada']);
        $this->assertSame('Humedad / líquido', $voidedOrig['garantia_motivo_anulacion']);

        // Al estar anulada la orden original, ya no aparece vigente
        $this->assertNull($workshop->get_warranty_candidate($imei));
    }
}
