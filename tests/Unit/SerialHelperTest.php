<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/classes/SerialHelper.php';

final class SerialHelperTest extends TestCase
{
    public function testNormalizeTrimsUppercasesAndRemovesWhitespace(): void
    {
        $this->assertSame('35-123456-789012-3', SerialHelper::normalize('  35-123456-789012-3  '));
        $this->assertSame('SN/ABC12345', SerialHelper::normalize("  sn/abc 123\t45 \n"));
    }

    public function testIsValidAcceptsStandardImeisAndSerials(): void
    {
        $this->assertTrue(SerialHelper::isValid(SerialHelper::normalize('359999000011111')));
        $this->assertTrue(SerialHelper::isValid(SerialHelper::normalize('sn1234abcd')));
        $this->assertTrue(SerialHelper::isValid(SerialHelper::normalize('AB-12/34')));
    }

    public function testIsValidRejectsTooShortTooLongOrInvalidCharacters(): void
    {
        $this->assertFalse(SerialHelper::isValid('ABC'));
        $this->assertFalse(SerialHelper::isValid(str_repeat('A', 31)));
        $this->assertFalse(SerialHelper::isValid('SN_12345'));
        $this->assertFalse(SerialHelper::isValid('SN*12345'));
        $this->assertFalse(SerialHelper::isValid(''));
    }
}
