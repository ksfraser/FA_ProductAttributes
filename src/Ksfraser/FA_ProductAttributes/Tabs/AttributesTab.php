<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

class AttributesTab extends AbstractTab
{
    /** @var ProductAttributesService */
    private $service;

    /** @var ProductAttributesHandler */
    private $handler;

    public function __construct(ProductAttributesService $service, ProductAttributesHandler $handler)
    {
        $this->service = $service;
        $this->handler = $handler;
    }

    public function getName(): string
    {
        return 'product_attributes';
    }

    public function getTabKey(): string
    {
        return 'product_attributes';
    }

    public function getTabLabel(): string
    {
        return _('Product Attributes');
    }

    public function renderTabContent(string $stockId): void
    {
        echo $this->service->renderProductAttributesTab($stockId);
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $this->handler->handle_product_attributes_save($postData, $stockId);
    }

    public function handleDelete(string $stockId): void
    {
        $this->handler->handle_product_attributes_delete($stockId);
    }
}
