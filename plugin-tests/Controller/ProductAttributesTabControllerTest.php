<?php

namespace Ksfraser\FA_ProductAttributes\Test\Controller;

use Ksfraser\FA_ProductAttributes\Controller\ProductAttributesTabController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class ProductAttributesTabControllerTest extends TestCase
{
    public function testHandlePostUpdatesParent(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('setProductParent')
            ->with('CHILD001', 'PARENT001');

        $_POST['update_product_config'] = '1';
        $_POST['parent_stock_id'] = 'PARENT001';

        $controller = new ProductAttributesTabController($dao);
        $controller->handlePost('CHILD001');

        unset($_POST['update_product_config'], $_POST['parent_stock_id']);
    }

    public function testHandlePostClearsParentWhenEmpty(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('setProductParent')
            ->with('CHILD001', null);

        $_POST['update_product_config'] = '1';
        $_POST['parent_stock_id'] = '';

        $controller = new ProductAttributesTabController($dao);
        $controller->handlePost('CHILD001');

        unset($_POST['update_product_config'], $_POST['parent_stock_id']);
    }

    public function testHandlePostNoopWhenActionNotSet(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())->method('setProductParent');

        unset($_POST['update_product_config']);

        $controller = new ProductAttributesTabController($dao);
        $controller->handlePost('CHILD001');
    }

    public function testHandlePostCatchesException(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->method('setProductParent')
            ->willThrowException(new \RuntimeException('DB error'));

        $_POST['update_product_config'] = '1';
        $_POST['parent_stock_id'] = 'PARENT001';

        $controller = new ProductAttributesTabController($dao);
        // Should not throw — display_error() is called instead
        $controller->handlePost('CHILD001');

        unset($_POST['update_product_config'], $_POST['parent_stock_id']);

        // If we reach this point the exception was caught successfully
        $this->addToAssertionCount(1);
    }
}
