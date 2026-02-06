<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DebugCompany;
use PHPUnit\Framework\TestCase;

/**
 * Test for DebugCompany
 */
class DebugCompanyTest extends TestCase
{
    public function testDebugMethodExists(): void
    {
        $this->assertTrue(method_exists(DebugCompany::class, 'debug'));
    }

    public function testDebugWithZeroLevelDoesNothing(): void
    {
        // This should not throw any exceptions
        DebugCompany::debug(0);
        $this->assertTrue(true);
    }

    public function testDebugWithPositiveLevel(): void
    {
        // This should not throw any exceptions even without session
        DebugCompany::debug(1);
        $this->assertTrue(true);
    }
}