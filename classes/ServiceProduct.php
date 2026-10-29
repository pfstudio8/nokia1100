<?php
// classes/ServiceProduct.php
// Identificación centralizada del producto contable "Servicio Técnico Taller".
// Reemplaza el antiguo ID mágico 13, que colisionaba con el 13° producto físico real.

final class ServiceProduct
{
    public const NAME = 'Servicio Técnico Taller';

    // Fragmento SQL para excluir el producto contable de servicio de listados, gráficos y métricas de stock físico.
    // $alias debe ser el alias de `producto`; $detailAlias (opcional) el alias de `producto_detalle`.
    public static function excludeSql(string $alias = 'p', ?string $detailAlias = null): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        $clauses = [
            "{$alias}.nombre <> '" . self::NAME . "'"
        ];

        if ($detailAlias !== null && $detailAlias !== '') {
            $d = preg_replace('/[^a-zA-Z0-9_]/', '', $detailAlias);
            $clauses[] = "({$d}.categoria IS NULL OR {$d}.categoria <> 'Servicios')";
            $clauses[] = "NOT (COALESCE({$d}.marca, '') = 'Nokia' AND COALESCE({$d}.modelo, '') = 'Taller')";
        }

        return '(' . implode(' AND ', $clauses) . ')';
    }

    // Devuelve el id del producto de servicio, o null si aún no existe.
    public static function findId(mysqli $conn): ?int
    {
        $stmt = $conn->prepare("SELECT id_producto FROM producto WHERE nombre = ? ORDER BY id_producto ASC LIMIT 1");
        $name = self::NAME;
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['id_producto'] : null;
    }
}
