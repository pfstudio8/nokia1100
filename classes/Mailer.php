<?php
// classes/Mailer.php

class Mailer
{
    public static function appUrl(): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : (string)($_ENV['BASE_URL'] ?? '/nokia1100');
        $appUrl = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
        if ($appUrl !== '' && filter_var($appUrl, FILTER_VALIDATE_URL)) {
            return $appUrl . $baseUrl;
        }

        if (PHP_SAPI === 'cli') {
            return 'http://localhost' . $baseUrl;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        return $scheme . '://' . $host . $baseUrl;
    }

    public static function isEnabled(): bool
    {
        return strtolower(trim((string)($_ENV['MAIL_DISABLED'] ?? 'false'))) !== 'true';
    }

    /**
     * Envía un correo HTML usando PHPMailer y configuración SMTP de .env.
     * Nunca lanza excepciones.
     *
     * @return array{ok:bool, error:?string}
     */
    public static function send(string $to, string $toName, string $subject, string $html, string $alt = ''): array
    {
        try {
            if (!self::isEnabled()) {
                return ['ok' => false, 'error' => 'Correo deshabilitado por configuración (MAIL_DISABLED)'];
            }

            $to = trim($to);
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'error' => 'Email destinatario inválido'];
            }

            require_once __DIR__ . '/../config/config_mail.php';

            $smtpHost = (string)($_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
            $smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
            $smtpUser = trim((string)($_ENV['SMTP_USER'] ?? ''));
            $smtpPass = (string)preg_replace('/\s+/', '', (string)($_ENV['SMTP_PASS'] ?? ''));
            $fromEmail = (string)($_ENV['SMTP_FROM_EMAIL'] ?? $smtpUser);
            $fromName = (string)($_ENV['SMTP_FROM_NAME'] ?? 'Nokia 1100 System');

            if ($smtpUser === '' || $smtpPass === '') {
                error_log('Mailer: SMTP_USER/SMTP_PASS no configurados.');
                return ['ok' => false, 'error' => 'Credenciales SMTP no configuradas'];
            }

            if (!cargarPHPMailer()) {
                error_log('Mailer: PHPMailer no está disponible.');
                return ['ok' => false, 'error' => 'PHPMailer no disponible'];
            }

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $toName !== '' ? $toName : $to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $alt !== '' ? $alt : trim(preg_replace('/\s+/', ' ', strip_tags($html)));

            $mail->send();
            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            error_log('Mailer::send error (' . $to . '): ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function layout(string $badge, string $title, string $innerHtml): string
    {
        $year = date('Y');
        $badgeSafe = htmlspecialchars($badge, ENT_QUOTES, 'UTF-8');
        $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titleSafe} - Nokia 1100</title>
</head>
<body style="margin: 0; padding: 0; background-color: #eeebe3; font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #15171a;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eeebe3; padding: 36px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #f8f6f0; border: 1px solid #b5af9c; border-radius: 4px; overflow: hidden; box-shadow: 0 12px 32px -6px rgba(21, 23, 26, 0.08);">
                    <tr>
                        <td style="background-color: #111315; padding: 16px 24px; border-bottom: 2px solid #c6e24a;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left" style="vertical-align: middle;">
                                        <span style="font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; color: #c6e24a; text-transform: uppercase;">
                                            SISTEMA NOKIA
                                        </span>
                                        <span style="display: inline-block; margin-left: 6px; background-color: #c6e24a; color: #15171a; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 2px; vertical-align: middle;">
                                            1100
                                        </span>
                                    </td>
                                    <td align="right" style="vertical-align: middle;">
                                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #c6e24a;"></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 28px 26px; text-align: left;">
                            <div style="margin-bottom: 14px;">
                                <span style="display: inline-block; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; color: #5c6165; text-transform: uppercase; border: 1px solid #d3cebf; padding: 3px 8px; border-radius: 2px; background-color: #eeebe3;">
                                    {$badgeSafe}
                                </span>
                            </div>
                            <h1 style="margin: 0 0 16px 0; font-family: 'Bricolage Grotesque', 'IBM Plex Sans', Arial, sans-serif; font-size: 22px; font-weight: 800; color: #15171a; line-height: 1.25; letter-spacing: -0.02em;">
                                {$titleSafe}
                            </h1>
                            {$innerHtml}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 28px; background-color: #111315; border-top: 1px solid #222729; text-align: center;">
                            <p style="margin: 0; font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #9ba19f; letter-spacing: 0.05em; text-transform: uppercase;">
                                &copy; {$year} Nokia 1100 &middot; Sistema de Gestión y Taller
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    public static function button(string $url, string $label): string
    {
        $urlSafe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $labelSafe = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 22px 0;">
    <tr>
        <td align="center">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="border-radius: 2px; background-color: #15171a; border: 1px solid #15171a; box-shadow: 3px 3px 0 #c6e24a;">
                        <a href="{$urlSafe}" target="_blank" style="display: inline-block; padding: 12px 30px; font-family: 'IBM Plex Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 2px; letter-spacing: 0.02em;">
                            {$labelSafe} &rarr;
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
HTML;
    }
}
