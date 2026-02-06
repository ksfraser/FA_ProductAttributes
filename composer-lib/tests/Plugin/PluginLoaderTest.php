<?php

namespace Ksfraser\FA_ProductAttributes\Test\Plugin;

use Ksfraser\FA_ProductAttributes\Plugin\PluginLoader;
use PHPUnit\Framework\TestCase;

/**
 * Test for PluginLoader
 */
class PluginLoaderTest extends TestCase
{
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = PluginLoader::getInstance();
        $instance2 = PluginLoader::getInstance();
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(PluginLoader::class, $instance1);
    }

    public function testLoadPluginsOnDemandMethodExists(): void
    {
        $loader = PluginLoader::getInstance();
        $this->assertTrue(method_exists($loader, 'loadPluginsOnDemand'));
    }

    public function testLoadPluginsOnDemandDoesNotThrow(): void
    {
        $loader = PluginLoader::getInstance();
        $loader->loadPluginsOnDemand();
        $this->assertTrue(true);
    }
}