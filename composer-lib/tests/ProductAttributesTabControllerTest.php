<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Controller\ProductAttributesTabController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class ProductAttributesTabControllerTest extends TestCase
{
    private $dao;
    private $controller;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->controller = new ProductAttributesTabController($this->dao);
    }

    public function testHandleAjaxWithValidUpdate()
    {
        $_POST['update_product_config'] = '1';
        $_POST['stock_id'] = 'TEST001';
        $_POST['parent_stock_id'] = 'PARENT001';

        $this->dao->expects($this->once())
            ->method('setProductParent')
            ->with('TEST001', 'PARENT001');

        // Capture output
        ob_start();
        $this->controller->handleAjax();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
        $this->assertEquals('Product configuration updated.', $response['message']);
    }

    public function testHandleAjaxWithException()
    {
        $_POST['update_product_config'] = '1';
        $_POST['stock_id'] = 'TEST001';
        $_POST['parent_stock_id'] = 'PARENT001';

        $this->dao->expects($this->once())
            ->method('setProductParent')
            ->willThrowException(new Exception('Test error'));

        ob_start();
        $this->controller->handleAjax();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertStringContains('Test error', $response['message']);
    }

    public function testHandlePost()
    {
        $_POST['update_product_config'] = '1';
        $_POST['stock_id'] = 'TEST001';
        $_POST['parent_stock_id'] = 'PARENT001';

        $this->dao->expects($this->once())
            ->method('setProductParent')
            ->with('TEST001', 'PARENT001');

        $this->controller->handlePost('TEST001');
    }
}