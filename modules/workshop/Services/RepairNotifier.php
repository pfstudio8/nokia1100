<?php
// modules/workshop/Services/RepairNotifier.php

require_once __DIR__ . '/../../../classes/Mailer.php';
require_once __DIR__ . '/../Models/WorkshopModel.php';

class RepairNotifier
{
    private static function isEnabled(): bool
    {
        return strtolower(trim((string)($_ENV['NOTIFY_CLIENT_EMAILS'] ?? 'true'))) !== 'false';
    }

    private static function dispatch(int $id_reparacion, string $tipo, string $subject, string $badge, string $title, string $innerHtml): void
    {
        if (!self::isEnabled()) {
            return;
        }

        try {
            $model = new WorkshopModel();
            $repair = $model->find_repair_by_id($id_reparacion);
            if (!$repair) {
                return;
            }

            $email = trim((string)($repair['cliente_email'] ?? ''));
            $clienteNombre = trim((string)($repair['cliente_nombre'] ?? 'Cliente'));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $model->log_notification($id_reparacion, $tipo, '', $subject, 'omitido', 'Cliente sin email válido');
                return;
            }

            $html = Mailer::layout($badge, $title, $innerHtml);
            $res = Mailer::send($email, $clienteNombre, $subject, $html);

            if (!empty($res['ok'])) {
                $model->log_notification($id_reparacion, $tipo, $email, $subject, 'enviado', null);
            } else {
                $err = mb_substr((string)($res['error'] ?? 'Error desconocido'), 0, 250);
                $model->log_notification($id_reparacion, $tipo, $email, $subject, 'fallido', $err);
            }
        } catch (Throwable $e) {
            error_log("RepairNotifier::dispatch error ($tipo, #$id_reparacion): " . $e->getMessage());
        }
    }

    public static function notifyCreated(int $id_reparacion): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $model = new WorkshopModel();
        $repair = $model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            return;
        }

        $codigo = (string)$repair['codigo_orden'];
        $cliente = htmlspecialchars((string)($repair['cliente_nombre'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $equipo = htmlspecialchars(trim(($repair['equipo_marca'] ?? '') . ' ' . ($repair['equipo_modelo'] ?? '')), ENT_QUOTES, 'UTF-8');
        $falla = nl2br(htmlspecialchars((string)($repair['falla_declarada'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $presupuesto = (float)($repair['presupuesto'] ?? 0);
        $trackUrl = Mailer::appUrl() . '/track.php?code=' . urlencode($codigo);
        $condiciones = htmlspecialchars(WorkshopModel::GARANTIA_CONDICIONES, ENT_QUOTES, 'UTF-8');

        $presHtml = $presupuesto > 0
            ? "<p style=\"margin: 8px 0 0 0; font-size: 13px; color: #15171a;\"><strong>Presupuesto inicial:</strong> $ " . number_format($presupuesto, 2, ',', '.') . "</p>"
            : "<p style=\"margin: 8px 0 0 0; font-size: 13px; color: #5c6165;\"><strong>Presupuesto:</strong> En evaluación técnica</p>";

        $inner = "
            <p style=\"margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #464b4f;\">
                ¡Hola, <strong style=\"color:#15171a;\">{$cliente}</strong>! Registramos el ingreso de tu equipo al taller técnico.
            </p>
            <div style=\"background-color: #eeebe3; border: 1px solid #d3cebf; border-radius: 4px; padding: 14px 16px; margin: 14px 0;\">
                <p style=\"margin: 0; font-family: 'IBM Plex Mono', monospace; font-size: 12px; font-weight: 700; color: #15171a;\">ORDEN #{$codigo}</p>
                <p style=\"margin: 6px 0 0 0; font-size: 13px; color: #15171a;\"><strong>Equipo:</strong> {$equipo}</p>
                <p style=\"margin: 6px 0 0 0; font-size: 13px; color: #464b4f;\"><strong>Falla declarada:</strong> {$falla}</p>
                {$presHtml}
            </div>
            " . Mailer::button($trackUrl, 'Seguir mi reparación') . "
            <p style=\"margin: 0; font-size: 11px; line-height: 1.5; color: #5c6165;\">
                <strong>Política de garantía:</strong> {$condiciones}
            </p>
        ";

        self::dispatch(
            $id_reparacion,
            'created',
            "Nokia 1100 · Orden #{$codigo} — Equipo ingresado al taller",
            'TALLER · INGRESO',
            "Orden #{$codigo} registrada",
            $inner
        );
    }

    public static function notifyStatusChange(int $id_reparacion, string $old, string $new): void
    {
        if (!self::isEnabled() || $old === $new) {
            return;
        }
        if (!in_array($new, ['En diagnóstico', 'En reparación', 'Cancelado'], true)) {
            return;
        }

        $model = new WorkshopModel();
        $repair = $model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            return;
        }

        $codigo = (string)$repair['codigo_orden'];
        $cliente = htmlspecialchars((string)($repair['cliente_nombre'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $equipo = htmlspecialchars(trim(($repair['equipo_marca'] ?? '') . ' ' . ($repair['equipo_modelo'] ?? '')), ENT_QUOTES, 'UTF-8');
        $estadoSafe = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
        $trackUrl = Mailer::appUrl() . '/track.php?code=' . urlencode($codigo);

        $extraMsg = match ($new) {
            'En diagnóstico' => 'Nuestro equipo técnico comenzó la revisión de tu dispositivo.',
            'En reparación'  => 'Tu equipo ya está siendo reparado en nuestro laboratorio.',
            'Cancelado'      => 'La orden fue marcada como cancelada. Por favor comunicate con el taller para coordinar el retiro.',
            default          => 'El estado de tu orden fue actualizado.'
        };

        $inner = "
            <p style=\"margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #464b4f;\">
                Hola, <strong style=\"color:#15171a;\">{$cliente}</strong>. Hay novedades sobre tu <strong>{$equipo}</strong> (Orden <strong>#{$codigo}</strong>).
            </p>
            <div style=\"background-color: #111315; border: 2px solid #c6e24a; border-radius: 4px; padding: 16px; text-align: center; margin: 16px 0;\">
                <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 10px; letter-spacing: 0.14em; color: #9ba19f; text-transform: uppercase; display: block; margin-bottom: 6px;\">NUEVO ESTADO</span>
                <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 18px; font-weight: 700; color: #c6e24a;\">{$estadoSafe}</span>
            </div>
            <p style=\"margin: 0 0 12px 0; font-size: 13px; line-height: 1.5; color: #464b4f;\">{$extraMsg}</p>
            " . Mailer::button($trackUrl, 'Ver seguimiento en vivo') . "
        ";

        self::dispatch(
            $id_reparacion,
            'status_change',
            "Nokia 1100 · Orden #{$codigo} — Estado: {$new}",
            'TALLER · ACTUALIZACIÓN',
            "Actualización de tu orden #{$codigo}",
            $inner
        );
    }

    public static function notifyReadyForPickup(int $id_reparacion): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $model = new WorkshopModel();
        $repair = $model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            return;
        }

        $codigo = (string)$repair['codigo_orden'];
        $cliente = htmlspecialchars((string)($repair['cliente_nombre'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $equipo = htmlspecialchars(trim(($repair['equipo_marca'] ?? '') . ' ' . ($repair['equipo_modelo'] ?? '')), ENT_QUOTES, 'UTF-8');
        $total = (float)($repair['presupuesto'] ?? 0) + (float)($repair['costo_total'] ?? 0);
        $trackUrl = Mailer::appUrl() . '/track.php?code=' . urlencode($codigo);
        $totalTxt = $total > 0 ? '$ ' . number_format($total, 2, ',', '.') : 'Sin cargo (Garantía)';

        $inner = "
            <p style=\"margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #464b4f;\">
                ¡Buenas noticias, <strong style=\"color:#15171a;\">{$cliente}</strong>! Tu <strong>{$equipo}</strong> ya finalizó su reparación y está listo para retirar.
            </p>
            <div style=\"background-color: #111315; border: 2px solid #c6e24a; border-radius: 4px; padding: 18px; text-align: center; margin: 18px 0;\">
                <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 10px; letter-spacing: 0.14em; color: #9ba19f; text-transform: uppercase; display: block; margin-bottom: 6px;\">TOTAL A ABONAR EN CAJA</span>
                <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 24px; font-weight: 700; color: #c6e24a;\">{$totalTxt}</span>
            </div>
            <p style=\"margin: 0 0 10px 0; font-size: 13px; color: #464b4f;\">Presentá el código <strong>#{$codigo}</strong> o tu comprobante de ingreso al momento de retirar.</p>
            " . Mailer::button($trackUrl, 'Ver detalle de la orden') . "
        ";

        self::dispatch(
            $id_reparacion,
            'ready',
            "Nokia 1100 · Orden #{$codigo} — ¡Tu equipo está listo para retirar!",
            'TALLER · LISTO PARA RETIRAR',
            '¡Tu equipo está listo para retirar!',
            $inner
        );
    }

    public static function notifyDelivered(int $id_reparacion, bool $freeWarranty = false): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $model = new WorkshopModel();
        $repair = $model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            return;
        }

        $codigo = (string)$repair['codigo_orden'];
        $cliente = htmlspecialchars((string)($repair['cliente_nombre'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $equipo = htmlspecialchars(trim(($repair['equipo_marca'] ?? '') . ' ' . ($repair['equipo_modelo'] ?? '')), ENT_QUOTES, 'UTF-8');
        $garantiaHasta = !empty($repair['garantia_hasta']) ? date('d/m/Y', strtotime((string)$repair['garantia_hasta'])) : date('d/m/Y', strtotime('+' . WorkshopModel::GARANTIA_DIAS . ' days'));
        $condiciones = htmlspecialchars(WorkshopModel::GARANTIA_CONDICIONES, ENT_QUOTES, 'UTF-8');
        $trackUrl = Mailer::appUrl() . '/track.php?code=' . urlencode($codigo);
        $sinCargoHtml = $freeWarranty
            ? '<p style="margin: 0 0 12px 0; font-size: 13px; font-weight: 700; color: #15803d;">Entrega sin cargo por cobertura de garantía.</p>'
            : '';

        $inner = "
            <p style=\"margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #464b4f;\">
                Hola, <strong style=\"color:#15171a;\">{$cliente}</strong>. Confirmamos la entrega de tu <strong>{$equipo}</strong> (Orden <strong>#{$codigo}</strong>).
            </p>
            {$sinCargoHtml}
            <div style=\"background-color: #eef8f1; border: 1px solid #8ed0a6; border-left: 4px solid #16a34a; border-radius: 4px; padding: 14px 16px; margin: 14px 0;\">
                <p style=\"margin: 0 0 6px 0; font-family: 'IBM Plex Mono', monospace; font-size: 12px; font-weight: 700; color: #156835;\">GARANTÍA VIGENTE HASTA: {$garantiaHasta}</p>
                <p style=\"margin: 0; font-size: 12px; line-height: 1.5; color: #166534;\">{$condiciones}</p>
            </div>
            " . Mailer::button($trackUrl, 'Consultar garantía online') . "
        ";

        self::dispatch(
            $id_reparacion,
            'delivered',
            "Nokia 1100 · Orden #{$codigo} — Entrega confirmada y garantía",
            'TALLER · ENTREGADO',
            'Equipo entregado · Póliza de garantía',
            $inner
        );
    }
}
