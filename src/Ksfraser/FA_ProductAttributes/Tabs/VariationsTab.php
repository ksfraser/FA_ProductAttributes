<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use FrontAccounting\ProductAttributes\Variations\Dao\VariationsDao;
use FrontAccounting\ProductAttributes\Variations\Service\FrontAccountingVariationService;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildAction;
use Ksfraser\FA_ProductAttributes\Actions\GenerateVariationsAction;
use Ksfraser\FA_ProductAttributes\Actions\UpdateProductTypesAction;

class VariationsTab extends AbstractTab
{
    /** @var VariationsDao */
    private $dao;

    /** @var FrontAccountingVariationService|null */
    private $variationService;

    public function __construct(VariationsDao $dao, ?FrontAccountingVariationService $variationService = null)
    {
        $this->dao = $dao;
        $this->variationService = $variationService;
    }

    public function getName(): string
    {
        return 'product_variations';
    }

    public function getTabKey(): string
    {
        return 'product_variations';
    }

    public function getTabLabel(): string
    {
        return _('Variations');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $categories = $this->dao->listCategories();
        $parentData = $this->dao->getProductParent($stockId);
        $variations = ($stockId !== '') ? $this->dao->getProductVariations($stockId) : [];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="variations_save">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        if ($parentData) {
            echo '<fieldset><legend>' . _('Parent Product') . '</legend>';
            echo '<p>' . _('This product is a variation of') . ' <strong>'
                . htmlspecialchars($parentData['stock_id']) . '</strong> — '
                . htmlspecialchars($parentData['description'] ?? '') . '</p>';
            echo '</fieldset>';
        }

        echo '<fieldset><legend>' . _('Variation Categories') . '</legend>';
        if (empty($categories)) {
            echo '<p>' . _('No variation categories defined.') . '</p>';
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Values') . '</th><th>' . _('Actions') . '</th></tr>';
            foreach ($categories as $category) {
                $values = $this->dao->listValues($category['id']);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($category['label']) . '</td>';
                echo '<td>' . count($values) . ' values</td>';
                echo '<td><a href="' . $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/index.php?tab=values&category_id='
                    . $category['id'] . '">' . _('Manage') . '</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</fieldset>';

        if (!empty($variations)) {
            echo '<fieldset><legend>' . _('Existing Variations') . '</legend>';
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Stock ID') . '</th><th>' . _('Description') . '</th></tr>';
            foreach ($variations as $v) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($v['stock_id']) . '</td>';
                echo '<td>' . htmlspecialchars($v['description'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</fieldset>';
        }

        echo '<p><input type="submit" name="generate_variations" value="' . _('Generate Variations') . '"> ';
        echo '<input type="submit" name="create_child" value="' . _('Create Child Product') . '"></p>';
        echo '</form>';
    }

    public function handleSave(string $stockId, array $postData): void
    {
        // Variations don't have a simple "save" — actions are dispatched via POST
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->clearParentRelationship($stockId);
    }

    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }

        if (isset($_POST['generate_variations'])) {
            $productAttributesDao = $this->getCoreDao();
            if ($productAttributesDao) {
                $tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
                $db = new \Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter($tablePrefix);
                $action = new GenerateVariationsAction($productAttributesDao, $db);
                $message = $action->handle($_POST);
                display_notification($message);
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        if (isset($_POST['create_child'])) {
            $action = new CreateChildAction($this->dao);
            try {
                $message = $action->handle($_POST);
                display_notification($message);
            } catch (\InvalidArgumentException $e) {
                display_error($e->getMessage());
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    private function getCoreDao(): ?\Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao
    {
        $tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
        try {
            $db = new \Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter($tablePrefix);
            return new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);
        } catch (\Exception $e) {
            return null;
        }
    }
}
