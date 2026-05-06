<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;

/**
 * Single Responsibility: Renders the Product Relationship Table (FR1.1).
 *
 * Displays a filterable table of all products showing:
 *   - Stock ID, Description, Type (Simple/Parent/Variation), Parent Stock ID, Status
 *
 * Filter options: all, parents only, variations only.
 */
class ProductRelationshipTable
{
    /** @var ProductAttributesDao */
    private $coreDao;

    /** @var VariationsDao */
    private $variationsDao;

    public function __construct(ProductAttributesDao $coreDao, VariationsDao $variationsDao)
    {
        $this->coreDao       = $coreDao;
        $this->variationsDao = $variationsDao;
    }

    /**
     * Render the product relationship table.
     *
     * Reads the optional $_GET['relationship_filter'] parameter:
     *   - 'all'        (default) — show all products
     *   - 'parents'    — show only parent products (have variations)
     *   - 'variations' — show only variation products (have a parent)
     */
    public function render(): void
    {
        if (function_exists('display_notification')) {
            display_notification('ProductRelationshipTable render() called');
        }

        $filter   = $_GET['relationship_filter'] ?? 'all';
        $products = $this->coreDao->getAllProducts();

        // Annotate each product with type and parent stock ID
        $rows = [];
        foreach ($products as $product) {
            $stockId    = $product['stock_id'];
            $variations = $this->variationsDao->getProductVariations($stockId);

            if (!empty($variations)) {
                $type           = 'Parent';
                $parentStockId  = '';
            } elseif (($parent = $this->variationsDao->getProductParent($stockId)) !== null) {
                $type           = 'Variation';
                $parentStockId  = $parent['stock_id'] ?? '';
            } else {
                $type           = 'Simple';
                $parentStockId  = '';
            }

            $active     = !empty($product['inactive']) ? _('Inactive') : _('Active');
            $rows[] = [
                'stock_id'        => $stockId,
                'description'     => $product['description'] ?? '',
                'type'            => $type,
                'parent_stock_id' => $parentStockId,
                'status'          => $active,
            ];
        }

        // Apply filter
        if ($filter === 'parents') {
            $rows = array_values(array_filter($rows, function ($r) {
                return $r['type'] === 'Parent';
            }));
        } elseif ($filter === 'variations') {
            $rows = array_values(array_filter($rows, function ($r) {
                return $r['type'] === 'Variation';
            }));
        }

        $this->renderFilterBar($filter);
        $this->renderTable($rows);
    }

    /**
     * Render filter navigation bar.
     *
     * @param string $currentFilter
     */
    private function renderFilterBar(string $currentFilter): void
    {
        echo '<div class="relationship-filter">';
        echo '<strong>' . _('Show:') . '</strong> ';

        $filters = [
            'all'        => _('All Products'),
            'parents'    => _('Parents Only'),
            'variations' => _('Variations Only'),
        ];

        foreach ($filters as $value => $label) {
            $active = ($currentFilter === $value) ? ' class="selected"' : '';
            $url    = htmlspecialchars('?' . http_build_query(array_merge($_GET ?? [], ['relationship_filter' => $value])));
            echo '<a href="' . $url . '"' . $active . '>' . htmlspecialchars($label) . '</a> ';
        }

        echo '</div>';
    }

    /**
     * Render the main HTML table.
     *
     * @param array<int, array<string, string>> $rows
     */
    private function renderTable(array $rows): void
    {
        echo '<h3>' . _('Product Relationships') . '</h3>';

        if (empty($rows)) {
            echo '<p>' . _('No products found.') . '</p>';
            return;
        }

        echo '<table class="tablestyle2">';
        echo '<tr>';
        echo '<th>' . _('Stock ID') . '</th>';
        echo '<th>' . _('Description') . '</th>';
        echo '<th>' . _('Type') . '</th>';
        echo '<th>' . _('Parent Stock ID') . '</th>';
        echo '<th>' . _('Status') . '</th>';
        echo '<th>' . _('Actions') . '</th>';
        echo '</tr>';

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['stock_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['description']) . '</td>';
            echo '<td>' . htmlspecialchars($row['type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['parent_stock_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>';
            $this->renderRowActions($row);
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Render action links for a single row.
     *
     * @param array<string, string> $row
     */
    private function renderRowActions(array $row): void
    {
        if ($row['type'] === 'Variation' && !empty($row['parent_stock_id'])) {
            // Navigate to parent
            $parentUrl = htmlspecialchars('?stock_id=' . urlencode($row['parent_stock_id']));
            echo '<a href="' . $parentUrl . '">' . _('View Parent') . '</a>';
        } elseif ($row['type'] === 'Parent') {
            // View all variations
            $varsUrl = htmlspecialchars('?' . http_build_query(['relationship_filter' => 'variations', 'parent' => $row['stock_id']]));
            echo '<a href="' . $varsUrl . '">' . _('View Variations') . '</a>';
        }
    }
}
