<?php

namespace Ksfraser\FA_ProductAttributes\Test\Debug;

use Ksfraser\FA_ProductAttributes\Debug\DebugTBPref;
use PHPUnit\Framework\TestCase;

/**
 * Test for DebugTBPref
 */
class DebugTBPrefTest extends TestCase
{
    public function testDebugMethodExists(): void
    {
        $this->assertTrue(method_exists(DebugTBPref::class, 'debug'));
    }

    public function testDebugWithZeroLevelDoesNothing(): void
    {
        DebugTBPref::debug(0);
        $this->assertTrue(true);
    }

    public function testDebugWithPositiveLevel(): void
    {
        DebugTBPref::debug(1);
        $this->assertTrue(true);
    }
}