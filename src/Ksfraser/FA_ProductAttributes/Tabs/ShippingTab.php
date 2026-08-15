<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\UpsertShippingAttributesAction;
use Ksfraser\FA_ProductAttributes\UI\ShippingAttributesTab;
use Ksfraser\Traits\InlinePostActionsTrait;
use Ksfraser\Traits\InlineTabRendererTrait;

class ShippingTab extends AbstractTab
{
    use InlinePostActionsTrait;
    use InlineTabRendererTrait;

    /** @var string FQCN of the upsert action class. */
    protected $upsertClassName = UpsertShippingAttributesAction::class;

    /** @var string Submit-button name that gates the inline save. */
    protected $saveButtonName = 'pa_shipping_save';

    /** @var string Notification shown after a successful save. */
    protected $notificationMessage = 'Shipping attributes saved.';

    /** @var string FQCN of the UI renderer class. */
    protected $tabClassName = ShippingAttributesTab::class;

    public function __construct(ShippingAttributesDao $dao)
    {
        $this->dao = $dao;
        $this->initUpsertClass();
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
}
