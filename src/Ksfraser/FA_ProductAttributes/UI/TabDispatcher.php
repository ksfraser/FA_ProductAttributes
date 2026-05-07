<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Dispatches tab rendering for the product-attributes admin screens.
 *
 * Two modes:
 * - Standalone  (embedded=false): renders tab nav + delegates to CategoriesTab / ValuesTab / AssignmentsTab
 * - Embedded    (embedded=true):  used inside the FrontAccounting items screen tab; tab names are
 *   'product_attributes' (main) or 'product_attributes_*' (plugin-contributed).
 *
 * Constructor signatures accepted:
 *   new TabDispatcher(ProductAttributesDao $dao, VariationsDao $variationsDao, string $tab, bool $embedded)
 *   new TabDispatcher(ProductAttributesDao $dao, string $tab, bool $embedded)   // no variationsDao
 */
class TabDispatcher
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var string */
    private $tab;

    /** @var bool */
    private $embedded;

    /**
     * @param ProductAttributesDao $dao
     * @param mixed                $variationsDaoOrTab   Either a VariationsDao object OR a tab string
     * @param mixed                $tabOrEmbedded        Tab string (when 4-arg) OR embedded bool (when 3-arg)
     * @param bool                 $embedded             Embedded flag (when 4-arg only)
     */
    public function __construct(
        ProductAttributesDao $dao,
        $variationsDaoOrTab = 'categories',
        $tabOrEmbedded = false,
        bool $embedded = false
    ) {
        $this->dao = $dao;

        // Detect which calling convention was used
        if (is_string($variationsDaoOrTab)) {
            // 3-arg form: (dao, tab, embedded)
            $this->tab      = $variationsDaoOrTab;
            $this->embedded = (bool)$tabOrEmbedded;
        } elseif ($variationsDaoOrTab === null) {
            // 1-arg form: (dao) — read tab from GET/POST or default
            if (isset($_GET['selected_tab'])) {
                $this->tab = (string)$_GET['selected_tab'];
            } elseif (isset($_POST['tab'])) {
                $this->tab = (string)$_POST['tab'];
            } else {
                $this->tab = 'categories';
            }
            $this->embedded = false;
        } else {
            // 4-arg form: (dao, variationsDao, tab, embedded)
            $this->tab      = is_string($tabOrEmbedded) ? $tabOrEmbedded : 'categories';
            $this->embedded = $embedded;
        }
    }

    /**
     * Main render entry-point.
     */
    public function render(): void
    {
        if ($this->embedded) {
            $this->renderEmbedded();
        } else {
            $this->renderStandalone();
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function renderStandalone(): void
    {
        // Tab navigation
        $tabs = [
            'summary'     => _('Summary'),
            'categories'  => _('Categories'),
            'values'      => _('Values'),
            'assignments' => _('Assignments'),
            'identifiers' => _('Identifiers'),
            'lifecycle'   => _('Lifecycle'),
            'tags'        => _('Tags'),
            'media'       => _('Media'),
            'shipping'    => _('Shipping'),
        ];

        echo '<div class="tab-nav">';
        foreach ($tabs as $tabKey => $label) {
            $class = ($tabKey === $this->tab) ? ' class="active"' : '';
            echo '<a href="?selected_tab=' . htmlspecialchars($tabKey) . '"' . $class . '>'
                . htmlspecialchars($label) . '</a> ';
        }
        echo '</div>';

        // Delegate to the appropriate tab class
        switch ($this->tab) {
            case 'values':
                $tabObj = new ValuesTab($this->dao);
                $tabObj->render();
                break;

            case 'assignments':
                $tabObj = new AssignmentsTab($this->dao);
                $tabObj->render();
                break;

            case 'categories':
                $tabObj = new CategoriesTab($this->dao);
                $tabObj->render();
                break;

            case 'shipping':
                $stockId     = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');
                $shippingDao = new \Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao(
                    $this->dao->getDbAdapter()
                );
                $shippingTab = new ShippingAttributesTab($shippingDao);
                $shippingTab->render($stockId);
                // Render the "copy to variations" panel beneath the main form
                $variationsDao = new \Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao(
                    $this->dao->getDbAdapter(),
                    $this->dao
                );
                $clonePanel = new \Ksfraser\FA_ProductAttributes\UI\ShippingClonePanel(
                    $shippingDao,
                    $variationsDao
                );
                $clonePanel->render($stockId);
                break;

            case 'identifiers':
                $stockId        = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');
                $identifiersDao = new \Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao(
                    $this->dao->getDbAdapter()
                );
                $identTab = new \Ksfraser\FA_ProductAttributes\UI\ProductIdentifiersTab($identifiersDao);
                $identTab->render($stockId);
                $variationsDao2 = new \Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao(
                    $this->dao->getDbAdapter(),
                    $this->dao
                );
                $identClone = new \Ksfraser\FA_ProductAttributes\UI\IdentifiersClonePanel(
                    $identifiersDao,
                    $variationsDao2
                );
                $identClone->render($stockId);
                break;

            case 'lifecycle':
                $stockId      = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');
                $lifecycleDao = new \Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao(
                    $this->dao->getDbAdapter()
                );
                $lcTab = new \Ksfraser\FA_ProductAttributes\UI\ProductLifecycleTab($lifecycleDao);
                $lcTab->render($stockId);
                $variationsDao3 = new \Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao(
                    $this->dao->getDbAdapter(),
                    $this->dao
                );
                $lcClone = new \Ksfraser\FA_ProductAttributes\UI\LifecycleClonePanel(
                    $lifecycleDao,
                    $variationsDao3
                );
                $lcClone->render($stockId);
                break;

            case 'tags':
                $stockId  = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');
                $tagsDao  = new \Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao(
                    $this->dao->getDbAdapter()
                );
                $tagsTab = new \Ksfraser\FA_ProductAttributes\UI\ProductTagsTab($tagsDao);
                $tagsTab->render($stockId);
                break;

            case 'media':
                $stockId       = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');
                $mediaDao      = new \Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao(
                    $this->dao->getDbAdapter()
                );
                $variationsDao4 = new \Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao(
                    $this->dao->getDbAdapter(),
                    $this->dao
                );
                $mediaTab = new \Ksfraser\FA_ProductAttributes\UI\ProductMediaTab($mediaDao, $variationsDao4);
                $mediaTab->render($stockId);
                break;

            case 'summary':
                $stockId         = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');
                $shippingDaoS    = new \Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao(
                    $this->dao->getDbAdapter()
                );
                $identifiersDaoS = new \Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao(
                    $this->dao->getDbAdapter()
                );
                $lifecycleDaoS   = new \Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao(
                    $this->dao->getDbAdapter()
                );
                $tagsDaoS        = new \Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao(
                    $this->dao->getDbAdapter()
                );
                $mediaDaoS       = new \Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao(
                    $this->dao->getDbAdapter()
                );
                $summaryTab = new \Ksfraser\FA_ProductAttributes\UI\ProductAttributesSummaryTab(
                    $this->dao,
                    $identifiersDaoS,
                    $lifecycleDaoS,
                    $tagsDaoS,
                    $mediaDaoS,
                    $shippingDaoS
                );
                $summaryTab->render($stockId);
                break;

            default:
                // Unknown / plugin-contributed standalone tab
                $tabLabel = ucfirst($this->tab);
                echo '<h4>' . htmlspecialchars($tabLabel) . '</h4>';
                echo '<p>' . _('Tab not available') . '</p>';
                break;
        }
    }

    private function renderEmbedded(): void
    {
        $tab = $this->tab;

        if ($tab === 'product_attributes') {
            $this->renderMainEmbeddedTab();
            return;
        }

        if (strpos($tab, 'product_attributes_') === 0) {
            $this->renderPluginTab($tab);
            return;
        }

        // Fallback
        echo '<p>' . _('Unknown tab') . '</p>';
    }

    private function renderMainEmbeddedTab(): void
    {
        global $path_to_root;

        $stockId = (string)($_GET['stock_id'] ?? $_POST['stock_id'] ?? '');

        if ($stockId === '') {
            if (function_exists('display_error')) {
                display_error(_('No stock ID provided'));
            } else {
                $GLOBALS['test_errors'][] = 'No stock ID provided';
            }
            return;
        }

        $assignments = $this->dao->listAssignments($stockId);

        if (empty($assignments)) {
            echo '<p>' . _('No attributes assigned') . '</p>';
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Value') . '</th></tr>';
            foreach ($assignments as $a) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)($a['category_label'] ?? '')) . '</td>';
                echo '<td>' . htmlspecialchars((string)($a['value_label'] ?? '')) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        // Link to the full attributes management screen
        $basePath = rtrim((string)($path_to_root ?? ''), '/');
        echo '<a href="' . htmlspecialchars($basePath . '/modules/FA_ProductAttributes/product_attributes_admin.php?stock_id=' . urlencode($stockId)) . '">'
            . _('Manage Product Attributes') . '</a>';
    }

    private function renderPluginTab(string $tab): void
    {
        // Derive a human-readable name from the tab key suffix
        $suffix    = substr($tab, strlen('product_attributes_'));
        $tabLabel  = ucfirst(str_replace('_', ' ', $suffix));

        echo '<h4>' . htmlspecialchars($tabLabel) . '</h4>';
        echo '<p>' . _('Plugin content not implemented') . '</p>';
    }
}
