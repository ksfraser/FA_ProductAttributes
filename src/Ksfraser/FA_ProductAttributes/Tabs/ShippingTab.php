<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\UpsertShippingAttributesAction;

class ShippingTab extends AbstractTab
{
    /** @var ShippingAttributesDao */
    private $dao;

    public function __construct(ShippingAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function getName(): string
    {
        return 'shipping_attributes';
    }

    public function getTabKey(): string
    {
        return 'shipping_attributes';
    }

    public function getTabLabel(): string
    {
        return _('Shipping');
    }

    public function renderTabContent(string $stockId): void
    {
        $tab = new \Ksfraser\FA_ProductAttributes\UI\ShippingAttributesTab($this->dao);
        $tab->render($stockId);
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $action = new UpsertShippingAttributesAction($this->dao);
        $action->handle($postData);
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->delete($stockId);
    }
}
