<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;

/**
 * Dispatches tab rendering based on selected_tab parameter
 * Handles both standalone admin page tabs and items.php embedded tabs
 */
class TabDispatcher
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var VariationsDao */
    private $variationsDao;

    /** @var string */
    private $selectedTab;

    /** @var bool */
    private $isEmbedded;

    public function __construct(ProductAttributesDao $dao, VariationsDao $variationsDao, string $selectedTab = '', bool $isEmbedded = false)
    {
        $this->dao = $dao;
        $this->variationsDao = $variationsDao;
        $this->selectedTab = $selectedTab ?: ($_GET['selected_tab'] ?? $_GET['tab'] ?? $_POST['tab'] ?? 'categories');
        $this->isEmbedded = $isEmbedded || isset($_GET['selected_tab']);
    }

    /**
     * Render the appropriate tab content
     */
    public function render(): void
    {
        // If embedded in items.php, handle plugin tabs differently
        if ($this->isEmbedded) {
            $this->renderEmbeddedTab();
        } else {
            $this->renderStandaloneTab();
        }
    }

    /**
     * Render tab when embedded in items.php
     */
    private function renderEmbeddedTab(): void
    {
        // Handle plugin tabs (e.g., product_attributes_dimensions)
        if ($this->selectedTab !== 'product_attributes') {
            $this->renderPluginTab();
            return;
        }

        // Handle main product_attributes tab - show embedded interface
        $this->renderEmbeddedMainTab();
    }

    /**
     * Render plugin-specific tab content
     */
    private function renderPluginTab(): void
    {
        // Extract plugin name from tab (e.g., 'product_attributes_dimensions' -> 'dimensions')
        $pluginName = str_replace('product_attributes_', '', $this->selectedTab);

        // Allow plugins to handle their own tab content via hooks
        global $path_to_root;
        $hooksFile = $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';
        if (file_exists($hooksFile)) {
            require_once $hooksFile;

            if (function_exists('fa_hooks')) {
                $hooks = fa_hooks();

                // Apply filter for plugin tab content
                $content = $hooks->apply_filters('attributes_tab_content', '', $_GET['stock_id'] ?? '', $this->selectedTab);

                if (!empty($content)) {
                    echo $content;
                    return;
                }
            }
        }

        // Fallback: try to load plugin-specific UI class
        $this->renderPluginUIClass($pluginName);
    }

    /**
     * Render plugin UI class dynamically
     */
    private function renderPluginUIClass(string $pluginName): void
    {
        // Convert plugin name to class name (e.g., 'dimensions' -> 'DimensionsTab')
        $className = ucfirst($pluginName) . 'Tab';
        $fullClassName = "Ksfraser\\FA_ProductAttributes\\UI\\{$className}";

        if (class_exists($fullClassName)) {
            try {
                $tabInstance = new $fullClassName($this->dao);
                if (method_exists($tabInstance, 'render')) {
                    $tabInstance->render();
                    return;
                }
            } catch (\Throwable $e) {
                display_error("Error rendering {$pluginName} tab: " . $e->getMessage());
                return;
            }
        }

        // If no specific class found, show generic plugin interface
        $this->renderGenericPluginTab($pluginName);
    }

    /**
     * Render generic plugin tab when no specific UI class exists
     */
    private function renderGenericPluginTab(string $pluginName): void
    {
        start_table(TABLESTYLE2);
        table_header(array(_("Plugin"), _("Status")));
        start_row();
        label_cell(ucfirst($pluginName));
        label_cell(_("Plugin content not implemented"));
        end_row();
        end_table();
    }

    /**
     * Render main product attributes tab when embedded
     */
    private function renderEmbeddedMainTab(): void
    {
        // For embedded main tab, show a simplified interface
        // Could show assignments for the current stock_id
        $stockId = $_GET['stock_id'] ?? '';

        if (empty($stockId)) {
            display_error(_("No stock ID provided"));
            return;
        }

        // Show current assignments for this item
        $assignments = $this->dao->listAssignments($stockId);

        start_table(TABLESTYLE2);
        table_header(array(_("Category"), _("Value"), _("Actions")));

        if (empty($assignments)) {
            start_row();
            label_cell(_("No attributes assigned"), "colspan=3");
            end_row();
        } else {
            foreach ($assignments as $assignment) {
                start_row();
                label_cell($assignment['category_label'] ?? '');
                label_cell($assignment['value_label'] ?? '');
                // Add edit/remove actions
                label_cell(
                    '<a href="#" onclick="return false;">' . _("Edit") . '</a> | ' .
                    '<a href="#" onclick="return false;">' . _("Remove") . '</a>'
                );
                end_row();
            }
        }

        end_table(1);

        // Add "Manage Attributes" button that could open the full admin interface
        echo '<br>';
        start_table();
        start_row();
        label_cell(
            '<input type="button" value="' . _("Manage Product Attributes") . '" onclick="window.open(\'' .
            ($path_to_root ?? '/fa') . '/modules/FA_ProductAttributes/product_attributes_admin.php\', \'_blank\');">'
        );
        end_row();
        end_table();
    }

    /**
     * Render standalone admin page tabs
     */
    private function renderStandaloneTab(): void
    {
        // Show tab navigation for standalone page
        echo '<div style="margin:8px 0">'
            . '<a href="?tab=categories">' . _("Categories") . '</a> | '
            . '<a href="?tab=values">' . _("Values") . '</a> | '
            . '<a href="?tab=assignments">' . _("Assignments") . '</a>'
            . '</div>';

        // Render the appropriate tab
        switch ($this->selectedTab) {
            case 'categories':
                $tab = new CategoriesTab($this->variationsDao);
                $tab->render();
                break;
            case 'values':
                $tab = new ValuesTab($this->variationsDao);
                $tab->render();
                break;
            case 'assignments':
                $tab = new AssignmentsTab($this->variationsDao);
                $tab->render();
                break;
            default:
                // Handle plugin tabs in standalone mode
                $this->renderPluginTab();
                break;
        }
    }
}