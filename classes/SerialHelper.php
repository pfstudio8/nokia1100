<?php
// classes/SerialHelper.php

class SerialHelper
{
    public static function normalize(string $s): string
    {
        return strtoupper((string)preg_replace('/\s+/', '', trim($s)));
    }

    public static function isValid(string $norm): bool
    {
        return (bool)preg_match('/^[A-Z0-9\-\/]{4,30}$/', $norm);
    }
}
