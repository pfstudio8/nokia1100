<?php
/**
 * Generador nativo de Códigos QR en formato SVG (ISO/IEC 18004).
 * 100% autónomo (sin dependencias externas ni llamadas de red).
 * Soporta modo Byte (UTF-8 / URLs) desde Versión 1 hasta Versión 10 (Nivel ECC L/M).
 */
class QRCodeSVG
{
    // Capacidad máxima de bytes de datos en modo Byte con Nivel de Corrección L
    // y estructura de bloques [total_codewords, ec_codewords_per_block, num_blocks_g1, data_per_block_g1, num_blocks_g2, data_per_block_g2]
    private const VERSION_TABLE_L = [
        1  => [17,  [26,   7,  1, 19, 0, 0]],
        2  => [32,  [44,  10,  1, 34, 0, 0]],
        3  => [53,  [70,  15,  1, 55, 0, 0]],
        4  => [78,  [100, 20,  1, 80, 0, 0]],
        5  => [106, [134, 26,  1, 108, 0, 0]],
        6  => [134, [172, 18,  2, 68, 0, 0]],
        7  => [154, [196, 20,  2, 78, 0, 0]],
        8  => [192, [242, 24,  2, 97, 0, 0]],
        9  => [230, [292, 30,  2, 116, 0, 0]],
        10 => [271, [346, 18,  2, 68, 2, 69]],
    ];

    // Posiciones de los patrones de alineación por versión
    private const ALIGNMENT_PATTERNS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    // Información de versión (18 bits) para versiones >= 7
    private const VERSION_INFO = [
        7  => 0x07C94,
        8  => 0x085BC,
        9  => 0x09A99,
        10 => 0x0A4D3,
    ];

    private static array $gfExp = [];
    private static array $gfLog = [];
    private static bool $gfInitialized = false;

    private static function initGaloisField(): void
    {
        if (self::$gfInitialized) {
            return;
        }
        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $x;
            self::$gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }
        self::$gfInitialized = true;
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$gfExp[self::$gfLog[$a] + self::$gfLog[$b]];
    }

    private static function getGeneratorPoly(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $factor = self::$gfExp[$i];
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                $next[$j + 1] ^= self::gfMul($poly[$j], $factor);
            }
            $poly = $next;
        }
        return $poly;
    }

    private static function computeReedSolomon(array $data, int $ecCount): array
    {
        $gen = self::getGeneratorPoly($ecCount);
        $msg = array_merge($data, array_fill(0, $ecCount, 0));
        $dataLen = count($data);

        for ($i = 0; $i < $dataLen; $i++) {
            $coef = $msg[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($gen); $j++) {
                    $msg[$i + $j] ^= self::gfMul($gen[$j], $coef);
                }
            }
        }
        return array_slice($msg, $dataLen);
    }

    /**
     * Genera un string `<svg>...</svg>` con el código QR para el texto o URL indicado.
     */
    public static function render(string $text, int $size = 148, int $margin = 2, string $fgColor = '#15171a', string $bgColor = '#ffffff'): string
    {
        self::initGaloisField();

        $bytes = array_values(unpack('C*', $text));
        $byteLen = count($bytes);

        // Seleccionar versión adecuada (1..10)
        $version = 10;
        foreach (self::VERSION_TABLE_L as $v => $info) {
            if ($byteLen <= $info[0]) {
                $version = $v;
                break;
            }
        }

        [, $blockInfo] = self::VERSION_TABLE_L[$version];
        [$totalCodewords, $ecPerBlock, $numBlocksG1, $dataPerBlockG1, $numBlocksG2, $dataPerBlockG2] = $blockInfo;
        $totalDataCodewords = ($numBlocksG1 * $dataPerBlockG1) + ($numBlocksG2 * $dataPerBlockG2);

        // Construir flujo de bits (Modo Byte = 0100)
        $bits = [];
        self::appendBits($bits, 0b0100, 4);
        $charCountBits = ($version <= 9) ? 8 : 16;
        self::appendBits($bits, $byteLen, $charCountBits);

        foreach ($bytes as $b) {
            self::appendBits($bits, $b, 8);
        }

        // Terminador (hasta 4 ceros)
        $maxDataBits = $totalDataCodewords * 8;
        $terminatorLen = min(4, $maxDataBits - count($bits));
        self::appendBits($bits, 0, $terminatorLen);

        // Alinear a múltiplo de 8 bits
        if (count($bits) % 8 !== 0) {
            self::appendBits($bits, 0, 8 - (count($bits) % 8));
        }

        // Rellenar con bytes alternados 0xEC y 0x11
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($bits) < $maxDataBits) {
            self::appendBits($bits, $padBytes[$padIdx % 2], 8);
            $padIdx++;
        }

        // Convertir bits a bytes de datos
        $dataCodewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bits[$i + $j];
            }
            $dataCodewords[] = $val;
        }

        // Dividir en bloques y calcular corrección de errores Reed-Solomon
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;

        for ($i = 0; $i < $numBlocksG1; $i++) {
            $block = array_slice($dataCodewords, $offset, $dataPerBlockG1);
            $offset += $dataPerBlockG1;
            $dataBlocks[] = $block;
            $ecBlocks[] = self::computeReedSolomon($block, $ecPerBlock);
        }
        for ($i = 0; $i < $numBlocksG2; $i++) {
            $block = array_slice($dataCodewords, $offset, $dataPerBlockG2);
            $offset += $dataPerBlockG2;
            $dataBlocks[] = $block;
            $ecBlocks[] = self::computeReedSolomon($block, $ecPerBlock);
        }

        // Intercalar bloques de datos y bloques EC
        $finalCodewords = [];
        $maxDataLen = max($dataPerBlockG1, $dataPerBlockG2);
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($dataBlocks as $block) {
                if ($i < count($block)) {
                    $finalCodewords[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                $finalCodewords[] = $block[$i];
            }
        }

        // Crear matriz de módulos
        $matrixSize = 17 + 4 * $version;
        $matrix = array_fill(0, $matrixSize, array_fill(0, $matrixSize, 0));
        $isFunction = array_fill(0, $matrixSize, array_fill(0, $matrixSize, false));

        // 1. Patrones de búsqueda (Finder Patterns) y separadores
        self::drawFinderPattern($matrix, $isFunction, 0, 0, $matrixSize);
        self::drawFinderPattern($matrix, $isFunction, $matrixSize - 7, 0, $matrixSize);
        self::drawFinderPattern($matrix, $isFunction, 0, $matrixSize - 7, $matrixSize);

        // 2. Patrones de sincronización (Timing Patterns)
        for ($i = 8; $i < $matrixSize - 8; $i++) {
            $bit = ($i % 2 === 0) ? 1 : 0;
            $matrix[6][$i] = $bit;
            $isFunction[6][$i] = true;
            $matrix[$i][6] = $bit;
            $isFunction[$i][6] = true;
        }

        // 3. Patrones de alineación (Alignment Patterns)
        $alignCoords = self::ALIGNMENT_PATTERNS[$version];
        $numAlign = count($alignCoords);
        for ($i = 0; $i < $numAlign; $i++) {
            for ($j = 0; $j < $numAlign; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $numAlign - 1) || ($i === $numAlign - 1 && $j === 0)) {
                    continue;
                }
                self::drawAlignmentPattern($matrix, $isFunction, $alignCoords[$i], $alignCoords[$j]);
            }
        }

        // 4. Módulo oscuro fijo y reserva de áreas de formato
        $matrix[4 * $version + 9][8] = 1;
        $isFunction[4 * $version + 9][8] = true;

        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $isFunction[8][$i] = true;
                $isFunction[$i][8] = true;
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $isFunction[8][$matrixSize - 1 - $i] = true;
            $isFunction[$matrixSize - 1 - $i][8] = true;
        }

        // 5. Información de versión (para versiones >= 7)
        if ($version >= 7 && isset(self::VERSION_INFO[$version])) {
            $vInfo = self::VERSION_INFO[$version];
            for ($i = 0; $i < 18; $i++) {
                $bit = ($vInfo >> $i) & 1;
                $a = intdiv($i, 3);
                $b = ($i % 3) + $matrixSize - 11;
                $matrix[$a][$b] = $bit;
                $isFunction[$a][$b] = true;
                $matrix[$b][$a] = $bit;
                $isFunction[$b][$a] = true;
            }
        }

        // 6. Colocar bits de datos en zig-zag de derecha a izquierda
        $allBits = [];
        foreach ($finalCodewords as $cw) {
            self::appendBits($allBits, $cw, 8);
        }

        $bitIdx = 0;
        $totalBits = count($allBits);
        $upward = true;

        for ($right = $matrixSize - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $matrixSize; $vert++) {
                $row = $upward ? ($matrixSize - 1 - $vert) : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $col = $right - $j;
                    if (!$isFunction[$row][$col]) {
                        $bit = ($bitIdx < $totalBits) ? $allBits[$bitIdx] : 0;
                        $matrix[$row][$col] = $bit;
                        $bitIdx++;
                    }
                }
            }
            $upward = !$upward;
        }

        // 7. Evaluar las 8 máscaras y elegir la de menor penalización
        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        $bestMatrix = $matrix;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::applyMask($matrix, $isFunction, $mask, $matrixSize);
            self::drawFormatInfo($candidate, $mask, $matrixSize);
            $penalty = self::calculatePenalty($candidate, $matrixSize);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask = $mask;
                $bestMatrix = $candidate;
            }
        }

        // 8. Renderizar SVG limpio y optimizado
        $totalModules = $matrixSize + ($margin * 2);
        $pathParts = [];

        for ($r = 0; $r < $matrixSize; $r++) {
            for ($c = 0; $c < $matrixSize; $c++) {
                if ($bestMatrix[$r][$c] === 1) {
                    $x = $c + $margin;
                    $y = $r + $margin;
                    $pathParts[] = "M{$x},{$y}h1v1h-1z";
                }
            }
        }

        $pathData = implode('', $pathParts);
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges" role="img" aria-label="Código QR de Seguimiento">'
            . '<rect width="100%%" height="100%%" fill="%s"/>'
            . '<path d="%s" fill="%s"/>'
            . '</svg>',
            $totalModules,
            $totalModules,
            $size,
            $size,
            htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8'),
            $pathData,
            htmlspecialchars($fgColor, ENT_QUOTES, 'UTF-8')
        );
    }

    private static function appendBits(array &$bits, int $val, int $len): void
    {
        for ($i = $len - 1; $i >= 0; $i--) {
            $bits[] = ($val >> $i) & 1;
        }
    }

    private static function drawFinderPattern(array &$matrix, array &$isFunction, int $rowOffset, int $colOffset, int $size): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $rr = $rowOffset + $r;
                $cc = $colOffset + $c;
                if ($rr < 0 || $rr >= $size || $cc < 0 || $cc >= $size) {
                    continue;
                }
                if (($r >= 0 && $r <= 6 && ($c === 0 || $c === 6))
                    || ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6))
                    || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)
                ) {
                    $matrix[$rr][$cc] = 1;
                } else {
                    $matrix[$rr][$cc] = 0;
                }
                $isFunction[$rr][$cc] = true;
            }
        }
    }

    private static function drawAlignmentPattern(array &$matrix, array &$isFunction, int $centerRow, int $centerCol): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $rr = $centerRow + $r;
                $cc = $centerCol + $c;
                $dist = max(abs($r), abs($c));
                $matrix[$rr][$cc] = ($dist !== 1) ? 1 : 0;
                $isFunction[$rr][$cc] = true;
            }
        }
    }

    private static function applyMask(array $matrix, array $isFunction, int $mask, int $size): array
    {
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($isFunction[$r][$c]) {
                    continue;
                }
                $invert = match ($mask) {
                    0 => ($r + $c) % 2 === 0,
                    1 => $r % 2 === 0,
                    2 => $c % 3 === 0,
                    3 => ($r + $c) % 3 === 0,
                    4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
                    5 => (($r * $c) % 2 + ($r * $c) % 3) === 0,
                    6 => ((($r * $c) % 2 + ($r * $c) % 3) % 2) === 0,
                    7 => ((($r + $c) % 2 + ($r * $c) % 3) % 2) === 0,
                    default => false,
                };
                if ($invert) {
                    $matrix[$r][$c] ^= 1;
                }
            }
        }
        return $matrix;
    }

    private static function drawFormatInfo(array &$matrix, int $mask, int $size): void
    {
        // Nivel L = 01
        $data = (0b01 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        // Primera copia alrededor de la esquina superior izquierda
        for ($i = 0; $i <= 5; $i++) {
            $matrix[8][$i] = ($bits >> $i) & 1;
        }
        $matrix[8][7] = ($bits >> 6) & 1;
        $matrix[8][8] = ($bits >> 7) & 1;
        $matrix[7][8] = ($bits >> 8) & 1;
        for ($i = 9; $i < 15; $i++) {
            $matrix[14 - $i][8] = ($bits >> $i) & 1;
        }

        // Segunda copia en esquina inferior izquierda y superior derecha
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 1 - $i] = ($bits >> $i) & 1;
        }
        for ($i = 8; $i < 15; $i++) {
            $matrix[$size - 15 + $i][8] = ($bits >> $i) & 1;
        }
    }

    private static function calculatePenalty(array $matrix, int $size): int
    {
        $penalty = 0;
        // Regla 1: 5 o más módulos consecutivos del mismo color en fila o columna
        for ($r = 0; $r < $size; $r++) {
            $runColor = $matrix[$r][0];
            $runLen = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($matrix[$r][$c] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $penalty += 3;
                    } elseif ($runLen > 5) {
                        $penalty += 1;
                    }
                } else {
                    $runColor = $matrix[$r][$c];
                    $runLen = 1;
                }
            }
        }
        for ($c = 0; $c < $size; $c++) {
            $runColor = $matrix[0][$c];
            $runLen = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($matrix[$r][$c] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $penalty += 3;
                    } elseif ($runLen > 5) {
                        $penalty += 1;
                    }
                } else {
                    $runColor = $matrix[$r][$c];
                    $runLen = 1;
                }
            }
        }
        // Regla 2: Bloques 2x2 del mismo color
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $color = $matrix[$r][$c];
                if ($color === $matrix[$r][$c + 1] && $color === $matrix[$r + 1][$c] && $color === $matrix[$r + 1][$c + 1]) {
                    $penalty += 3;
                }
            }
        }
        return $penalty;
    }
}

