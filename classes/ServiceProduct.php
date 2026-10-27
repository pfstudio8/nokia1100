<?php
// classes/ServiceProduct.php
// Identificación centralizada del producto contable "Servicio Técnico Taller".
// Reemplaza el antiguo ID mágico 13, que colisionaba con el 13° producto físico real.

final class ServiceProduct
{
    public const NAME = 'Servicio Técnico Taller';

    // Fragmento SQL para excluir el producto de servicio de listados físicos.
    // $alias debe ser el alias de la tabla `producto` en la consulta.
    public static function excludeSql(string $alias = 'p'): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        return "{$alias}.nombre <> '" . self::NAME . "'";
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
