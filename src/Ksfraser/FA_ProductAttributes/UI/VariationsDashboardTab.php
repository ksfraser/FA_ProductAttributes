<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Service\VariationsDashboardService;

/**
 * Single Responsibility: Renders the Variations Dashboard admin tab.
 *
 * Displays a paginated summary of parent products and their variation counts,
 * with filter controls for category, count range, and stock status.
 *
 * Absorbed from FA_ProductVariations repo (DashboardController + summary views).
 */
class VariationsDashboardTab
{
    /** @var VariationsDashboardService */
    private $service;

    public function __construct(VariationsDashboardService $service)
    {
        $this->service = $service;
    }

    /**
     * Render the full dashboard tab HTML.
     */
    public function render(): void
    {
        $page    = max(1, (int)($_GET['dash_page'] ?? 1));
        $perPage = 20;
        $filter  = $_GET['dash_filter'] ?? 'all';
        $catId   = (int)($_GET['dash_cat'] ?? 0);
        $minCnt  = (int)($_GET['dash_min'] ?? 0);
        $maxCnt  = (int)($_GET['dash_max'] ?? 0);

        switch ($filter) {
            case 'category':
                $rows  = $this->service->filterByCategory($catId);
                $total = count($rows);
                break;
            case 'count':
                $rows  = $this->service->filterByVariationCount($minCnt, $maxCnt);
                $total = count($rows);
                break;
            case 'inactive':
                $rows  = $this->service->filterByStockStatus(true);
                $total = count($rows);
                break;
            default:
                $summary = $this->service->getSummary($page, $perPage);
                $rows    = $summary['rows'];
                $total   = $summary['total'];
                break;
        }

        echo '<h3>' . _('Variations Dashboard') . '</h3>';

        // Filter bar
        $self = $_SERVER['PHP_SELF'] ?? '';
        echo '<form method="get" action="' . htmlspecialchars($self) . '">';
        foreach ($_GET as $k => $v) {
            if (!in_array($k, ['dash_filter', 'dash_cat', 'dash_min', 'dash_max', 'dash_page'], true)) {
                echo '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars((string)$v) . '">';
            }
        }
        echo '<label>' . _('Filter') . ':&nbsp;';
        echo '<select name="dash_filter">';
        foreach (['all' => _('All'), 'category' => _('By Category'), 'count' => _('By Count'), 'inactive' => _('Inactive')] as $val => $lbl) {
            $sel = ($filter === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $lbl . '</option>';
        }
        echo '</select></label>&nbsp;';
        echo '<label>' . _('Category ID') . ':&nbsp;<input type="number" name="dash_cat" value="' . $catId . '" size="4"></label>&nbsp;';
        echo '<label>' . _('Min count') . ':&nbsp;<input type="number" name="dash_min" value="' . $minCnt . '" size="4"></label>&nbsp;';
        echo '<label>' . _('Max count') . ':&nbsp;<input type="number" name="dash_max" value="' . $maxCnt . '" size="4" placeholder="0 = any"></label>&nbsp;';
        echo '<input type="submit" value="' . _('Apply') . '">';
        echo '</form>';

        // Summary stats
        echo '<p>' . sprintf(_('Total parent products with variations: %d'), $total) . '</p>';

        if (empty($rows)) {
            echo '<p>' . _('No products found matching the selected filter.') . '</p>';
            return;
        }

        // Results table
        echo '<table class="tablestyle2">';
        echo '<thead><tr>';
        echo '<th>' . _('Stock ID') . '</th>';
        echo '<th>' . _('Description') . '</th>';
        echo '<th>' . _('Variations') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($rows as $row) {
            $stockId     = htmlspecialchars((string)$row['stock_id']);
            $description = htmlspecialchars((string)$row['description']);
            $count       = (int)$row['variation_count'];

            echo '<tr>';
            echo '<td>' . $stockId . '</td>';
            echo '<td>' . $description . '</td>';
            echo '<td style="text-align:right">' . $count . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Pagination (only for 'all' filter; filtered results return full sets)
        if ($filter === 'all' && isset($summary)) {
            $totalPages = $summary['total_pages'];
            if ($totalPages > 1) {
                echo '<p>';
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = ($i === $page) ? ' <strong>' . $i . '</strong>' : ' <a href="?dash_page=' . $i . '">' . $i . '</a>';
                    echo $active;
                }
                echo '</p>';
            }
        }
    }
}
