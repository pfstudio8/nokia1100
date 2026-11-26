<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/DatabaseTestCase.php';
require_once dirname(__DIR__, 2) . '/modules/workshop/Models/WorkshopModel.php';

final class StockTest extends DatabaseTestCase
{
    public function testCreateProductPersistsCodeStockAndMinimum(): void
    {
        $idProd = $this->createProduct('Samsung A15', 250000.0, 4, 2, 'NK998877');
        $inv = new InventoryModel();
        $row = $inv->find_product_by_id($idProd);

        $this->assertIsArray($row);
        $this->assertSame('NK998877', $row['codigo']);
        $this->assertSame(2, (int)$row['stock_minimo']);
        $this->assertSame(4, (int)$row['cantidad']);
    }

    public function testWorkshopSparePartDeductsAndRestoresStock(): void
    {
        $uid = $this->adminUserId();
        $idPart = $this->createProduct('Módulo Pantalla OLED', 18000.0, 5, 2);
        $workshop = new WorkshopModel();

        $idRep = $workshop->create_repair_order(
            0,
            'Ana Repuesto',
            '1144556677',
            'ana@test.local',
            'Samsung',
            'A34',
            '351111222233334',
            'Módulo roto',
            '',
            5000.0,
            $uid
        );

        // Consumir 2 unidades: stock baja a 3 y costo_total sube en 36000
        $this->assertTrue($workshop->add_repuesto_to_repair($idRep, $idPart, 2));
        $this->assertSame(3, $this->stockOf($idPart));

        $rep = $workshop->find_repair_by_id($idRep);
        $this->assertSame(36000.0, (float)$rep['costo_total']);

        // Solicitar más unidades que el stock disponible falla sin alterar inventario
        $insufficient = false;
        try {
            $workshop->add_repuesto_to_repair($idRep, $idPart, 10);
        } catch (Exception $e) {
            $insufficient = true;
        }
        $this->assertTrue($insufficient);
        $this->assertSame(3, $this->stockOf($idPart));

        // Quitar el repuesto devuelve las 2 unidades y resta el costo
        $stmtRr = $this->conn->prepare('SELECT id_reparacion_repuesto FROM reparacion_repuesto WHERE id_reparacion = ? AND id_producto = ? LIMIT 1');
        $stmtRr->bind_param('ii', $idRep, $idPart);
        $stmtRr->execute();
        $rr = $stmtRr->get_result()->fetch_assoc();
        $stmtRr->close();
        $this->assertNotEmpty($rr);

        $this->assertTrue($workshop->remove_repuesto_from_repair((int)$rr['id_reparacion_repuesto']));
        $this->assertSame(5, $this->stockOf($idPart));

        $repAfter = $workshop->find_repair_by_id($idRep);
        $this->assertSame(0.0, (float)$repAfter['costo_total']);
    }
}
