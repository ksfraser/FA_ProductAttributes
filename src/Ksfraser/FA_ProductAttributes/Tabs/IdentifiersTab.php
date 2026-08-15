<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;
use Ksfraser\FA_ProductAttributes\Actions\UpsertProductIdentifiersAction;
use Ksfraser\FA_ProductAttributes\UI\ProductIdentifiersTab;
use Ksfraser\Traits\InlinePostActionsTrait;
use Ksfraser\Traits\InlineTabRendererTrait;

class IdentifiersTab extends AbstractTab
{
    use InlinePostActionsTrait;
    use InlineTabRendererTrait;

    /** @var string FQCN of the upsert action class. */
    protected $upsertClassName = UpsertProductIdentifiersAction::class;

    /** @var string Submit-button name that gates the inline save. */
    protected $saveButtonName = 'pa_identifiers_save';

    /** @var string Notification shown after a successful save. */
    protected $notificationMessage = 'Identifiers saved.';

    /** @var string FQCN of the UI renderer class. */
    protected $tabClassName = ProductIdentifiersTab::class;

    /** @var IdentifierLookupsDao|null */
    private $lookupsDao;

    public function __construct(ProductIdentifiersDao $dao, IdentifierLookupsDao $lookupsDao = null)
    {
        $this->dao        = $dao;
        $this->lookupsDao = $lookupsDao;
        $this->initUpsertClass();
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

    protected function createTab()
    {
        return new $this->tabClassName($this->dao, $this->lookupsDao);
    }
}
