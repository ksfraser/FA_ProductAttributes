<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\FA_ProductAttributes\Tabs\AttributesTab;
use PHPUnit\Framework\TestCase;

class AttributesTabTest extends TestCase
{
    /** @var ProductAttributesService|\PHPUnit\Framework\MockObject\MockObject */
    private $service;

    /** @var ProductAttributesHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var AttributesTab */
    private $tab;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ProductAttributesService::class);
        $this->handler = $this->createMock(ProductAttributesHandler::class);
        $this->tab     = new AttributesTab($this->service, $this->handler);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_attributes', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_attributes', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Product Attributes', $this->tab->getTabLabel());
    }

    public function testRenderTabContentDelegatesToService(): void
    {
        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with('SKU001')
            ->willReturn('tab content');

        $saved = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();
        if ($saved !== null) {
            $_SERVER['REQUEST_METHOD'] = $saved;
        } else {
            unset($_SERVER['REQUEST_METHOD']);
        }

        $this->assertSame('tab content', $output);
    }

    public function testHandleSaveDelegatesToHandler(): void
    {
        $postData = ['some' => 'data'];
        $this->handler->expects($this->once())
            ->method('handle_product_attributes_save')
            ->with($postData, 'SKU001');

        $this->tab->handleSave('SKU001', $postData);
    }

    public function testHandleDeleteDelegatesToHandler(): void
    {
        $this->handler->expects($this->once())
            ->method('handle_product_attributes_delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }

    public function testRenderTabContentDeletesRowViaPerRowButtonName(): void
    {
        $this->service->expects($this->once())
            ->method('handleDeleteRow')
            ->with(42)
            ->willReturn('Assignment removed.');
        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with('SKU001')
            ->willReturn('tab content');

        $saved        = $this->saveRequest('POST', ['stock_id' => 'SKU001', 'pa_delete_row_42' => 'Remove']);
        unset($GLOBALS['test_notifications']);
        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output   = ob_get_clean();
        $this->restoreRequest($saved);

        $this->assertSame('tab content', $output);
        $this->assertSame(['Assignment removed.'], $GLOBALS['test_notifications']);
    }

    public function testRenderTabContentAddsAssignmentFromPost(): void
    {
        $post = ['stock_id' => 'SKU001', 'category_id' => 6, 'value_ids' => [14, 15], 'add_pa_assignment' => '1'];
        $this->service->expects($this->once())
            ->method('handleAddAssignment')
            ->with('SKU001', $post)
            ->willReturn('1 assignment(s) added.');
        $this->service->expects($this->once())
            ->method('renderProductAttributesTab')
            ->with('SKU001')
            ->willReturn('tab content');

        $saved        = $this->saveRequest('POST', $post);
        unset($GLOBALS['test_notifications']);
        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output   = ob_get_clean();
        $this->restoreRequest($saved);

        $this->assertSame('tab content', $output);
        $this->assertSame(['1 assignment(s) added.'], $GLOBALS['test_notifications']);
    }

    private function saveRequest(string $method, array $post): array
    {
        $saved = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'post'   => $_POST ?? null,
        ];
        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = $post;
        return $saved;
    }

    private function restoreRequest(array $saved): void
    {
        if ($saved['method'] === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $saved['method'];
        }
        $_POST = $saved['post'] ?? [];
    }
}
