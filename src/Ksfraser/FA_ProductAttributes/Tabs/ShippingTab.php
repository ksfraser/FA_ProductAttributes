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
        $this->handlePostActions($stockId);

        $tab = new \Ksfraser\FA_ProductAttributes\UI\ShippingAttributesTab($this->dao);
        $tab->render($stockId);
    }

    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }
        if (($_POST['action'] ?? '') !== 'save_shipping_attributes') {
            return;
        }

        $action = new UpsertShippingAttributesAction($this->dao);
        $action->handle($stockId, $_POST);

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $action = new UpsertShippingAttributesAction($this->dao);
        $action->handle($stockId, $postData);
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->delete($stockId);
    }
}
