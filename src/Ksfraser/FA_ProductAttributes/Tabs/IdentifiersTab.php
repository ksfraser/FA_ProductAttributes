<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Actions\UpsertProductIdentifiersAction;

class IdentifiersTab extends AbstractTab
{
    /** @var ProductIdentifiersDao */
    private $dao;

    public function __construct(ProductIdentifiersDao $dao)
    {
        $this->dao = $dao;
    }

    public function getName(): string
    {
        return 'product_identifiers';
    }

    public function getTabKey(): string
    {
        return 'product_identifiers';
    }

    public function getTabLabel(): string
    {
        return _('Identifiers');
    }

    public function renderTabContent(string $stockId): void
    {
        $tab = new \Ksfraser\FA_ProductAttributes\UI\ProductIdentifiersTab($this->dao);
        $tab->render($stockId);
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $action = new UpsertProductIdentifiersAction($this->dao);
        $action->handle($stockId, $postData);
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->delete($stockId);
    }
}
