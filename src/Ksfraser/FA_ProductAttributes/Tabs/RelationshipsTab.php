<?php
declare(strict_types=1);

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use ksfraser\FrontAccounting\Common\Repository\EntityRegistry;
use ksfraser\RelatedTo\DTO\RelatedQueryDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;

/**
 * Relationships tab — shows records related to this item via RelatedTo.
 *
 * Displays all records related to the current stock item (the item may be
 * either the lhs or rhs side of the association), grouped by related entity
 * type with resolved labels. Each row offers a Remove control that deletes
 * the association from 0_ksf_RelatedTo.
 *
 * Label resolution uses the shared EntityRegistry (ksf_FA_Common) to find
 * the owning table, primary key and label column for each entity type, then
 * issues a native FA db_query against that table.
 *
 * @since 1.2.0
 */
class RelationshipsTab extends AbstractTab
{
    public function getName(): string
    {
        return 'product_relationships';
    }

    public function getTabKey(): string
    {
        return 'product_relationships';
    }

    public function getTabLabel(): string
    {
        return _('Relationships');
    }

    public function isAvailable(string $stockId): bool
    {
        return $stockId !== '';
    }

    public function renderTabContent(string $stockId): void
    {
        // Handle remove POST before rendering.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $stockId !== '') {
            if (isset($_POST['pa_remove_relationship'])) {
                $rhsType = (string) ($_POST['rhs_type'] ?? '');
                $rhsId   = (string) ($_POST['rhs_id'] ?? '');
                $linkRole = isset($_POST['link_role']) ? (string) $_POST['link_role'] : null;
                $this->removeRelationship($stockId, $rhsType, $rhsId, $linkRole);
                display_notification(_('Relationship removed.'));
            }
        }

        $related = $this->getRelatedRecords($stockId);
        $labels  = $this->resolveLabels($related);

        echo '<div class="relationships-tab">';
        echo '<h2>' . _('Related Records') . '</h2>';

        if (empty($related)) {
            echo '<p>' . _('This item is not related to any records.') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="table" style="width:100%; border-collapse:collapse;">';
        echo '<thead><tr>';
        echo '<th>' . _('Related Record') . '</th>';
        echo '<th>' . _('Role') . '</th>';
        echo '<th></th>';
        echo '</tr></thead>';
        echo '<tbody>';

        $currentType = null;
        foreach ($related as $rel) {
            if (!$rel instanceof RelatedRecordDto) {
                continue;
            }

            // Section header when the related type changes.
            if ($rel->rhsType !== $currentType) {
                $currentType = $rel->rhsType;
                echo '<tr><td colspan="3"><strong>'
                    . htmlspecialchars($this->typeLabel($rel->rhsType))
                    . '</strong></td></tr>';
            }

            $rhsId = (string) $rel->rhsId;
            $label = isset($labels[$rel->rhsType][$rhsId])
                ? $labels[$rel->rhsType][$rhsId]
                : $rhsId;
            $role = $rel->linkRole !== null ? $rel->linkRole : _('reference');

            echo '<tr>';
            echo '<td>' . htmlspecialchars($label) . '</td>';
            echo '<td>' . htmlspecialchars($role) . '</td>';
            echo '<td>';
            echo '<form method="post" style="display:inline;margin:0;">';
            echo '<input type="hidden" name="pa_remove_relationship" value="1">';
            echo '<input type="hidden" name="rhs_type" value="'
                . htmlspecialchars($rel->rhsType) . '">';
            echo '<input type="hidden" name="rhs_id" value="'
                . htmlspecialchars($rhsId) . '">';
            if ($rel->linkRole !== null) {
                echo '<input type="hidden" name="link_role" value="'
                    . htmlspecialchars($rel->linkRole) . '">';
            }
            echo '<button type="submit" class="button" onclick="return confirm(\''
                . _('Remove this relationship?') . '\');">' . _('Remove') . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Fetch all records related to this item (symmetric — item may be lhs or rhs).
     *
     * @return RelatedRecordDto[]
     */
    private function getRelatedRecords(string $stockId): array
    {
        $repoClass = '\\ksfraser\\FrontAccounting\\RelatedTo\\Repository\\RelatedRecordRepository';
        if (!class_exists($repoClass)) {
            return array();
        }

        $repo = new $repoClass();
        $related = $repo->relatedSymmetric('item', $stockId);

        $out = array();
        foreach ($related as $rel) {
            if ($rel instanceof RelatedRecordDto) {
                $out[] = $rel;
            }
        }

        return $out;
    }

    /**
     * Remove a relationship from the master table.
     */
    private function removeRelationship(
        string $stockId,
        string $rhsType,
        string $rhsId,
        ?string $linkRole
    ): void {
        $repoClass = '\\ksfraser\\FrontAccounting\\RelatedTo\\Repository\\RelatedRecordRepository';
        if (!class_exists($repoClass)) {
            return;
        }

        $repo = new $repoClass();
        $repo->unrelate('item', $stockId, $rhsType, $rhsId, $linkRole);
    }

    /**
     * Resolve display labels for all related records via the EntityRegistry.
     *
     * @return array<string, array<string, string>> rhsType => rhsId => label
     */
    private function resolveLabels(array $related): array
    {
        $labels = array();
        $registry = new EntityRegistry();
        $allTypes = $registry->all();

        foreach ($related as $rel) {
            if (!$rel instanceof RelatedRecordDto) {
                continue;
            }

            $rhsId = (string) $rel->rhsId;
            if (isset($labels[$rel->rhsType][$rhsId])) {
                continue;
            }

            $def = isset($allTypes[$rel->rhsType]) ? $allTypes[$rel->rhsType] : null;
            if ($def === null) {
                $labels[$rel->rhsType][$rhsId] = $rhsId;
                continue;
            }

            if (!function_exists('db_query')
                || !function_exists('db_fetch_assoc')
                || !function_exists('db_num_rows')
                || !defined('TB_PREF')) {
                $labels[$rel->rhsType][$rhsId] = $rhsId;
                continue;
            }

            $table = TB_PREF . $def['table'];
            $pk    = $def['pk'];
            $labelCol = $def['label'];
            $id    = $this->escapeSql($rhsId);

            $sql = "SELECT `$labelCol` AS label FROM `$table` WHERE `$pk` = $id LIMIT 1";
            $result = db_query($sql);
            if ($result !== false && db_num_rows($result) > 0) {
                $row = db_fetch_assoc($result);
                $labels[$rel->rhsType][$rhsId] = ($row !== false && !empty($row['label']))
                    ? (string) $row['label']
                    : $rhsId;
            } else {
                $labels[$rel->rhsType][$rhsId] = $rhsId;
            }
        }

        return $labels;
    }

    /**
     * Human label for an entity type, from the registry title with fallbacks.
     */
    private function typeLabel(string $type): string
    {
        $registry = new EntityRegistry();
        $allTypes = $registry->all();
        $def = isset($allTypes[$type]) ? $allTypes[$type] : null;
        if ($def !== null && !empty($def['title'])) {
            return (string) $def['title'];
        }

        $fallbacks = array(
            'customer'    => _('Customers'),
            'supplier'    => _('Suppliers'),
            'item'        => _('Items'),
            'contact'     => _('Contacts'),
            'invoice'     => _('Invoices'),
            'meeting'     => _('Meetings'),
            'opportunity' => _('Opportunities'),
            'project'     => _('Projects'),
            'task'        => _('Tasks'),
            'contract'    => _('Contracts'),
            'calendar'    => _('Calendar Entries'),
            'attachment'  => _('Attachments'),
        );

        return isset($fallbacks[$type]) ? $fallbacks[$type] : ucfirst((string) $type);
    }

    /**
     * Escape a value as a SQL string literal.
     */
    private function escapeSql(string $value): string
    {
        $db = null;
        if (isset($GLOBALS['db'])
            && is_object($GLOBALS['db'])
            && method_exists($GLOBALS['db'], 'real_escape_string')) {
            $db = $GLOBALS['db'];
        }

        if ($db !== null) {
            return "'" . $db->real_escape_string($value) . "'";
        }

        return "'" . addslashes($value) . "'";
    }
}
