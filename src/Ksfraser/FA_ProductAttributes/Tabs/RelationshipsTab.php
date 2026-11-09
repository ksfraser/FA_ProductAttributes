<?php
declare(strict_types=1);

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\RelationshipsDao;
use ksfraser\FrontAccounting\Common\Repository\EntityRegistry;
use ksfraser\RelatedTo\DTO\RelatedQueryDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use ksfraser\RelatedTo\UI\DefaultRelatedRecordsRenderer;
use ksfraser\RelatedTo\UI\RelateRecordsBar;

/**
 * Relationships tab — shows records related to this item via RelatedTo.
 *
 * Displays a "Relate to" bar (Type DDL -> Record DDL -> Relate button) for
 * creating associations through the RelatedTo module hooks, then lists every
 * record related to the current item (symmetric — item may be lhs or rhs),
 * grouped by related entity type under collapsible twistie sections with
 * resolved labels. Each row offers a Remove control.
 *
 * Listing uses the `RELATED_TO` hook; assignment uses the `RELATE_RECORDS`
 * hook — both dispatched to `ksf_FA_RelatedTo`. Label/URL resolution uses the
 * shared EntityRegistry (ksf_FA_Common) + RelationshipsDao (named params via
 * FrontAccountingDbAdapter -> native FA db_* calls at runtime).
 *
 * The "Relate to" bar and the twistie display both come from
 * `ksfraser\RelatedTo\UI` so every module reuses the same controls instead of
 * re-inventing them.
 *
 * @since 1.2.0
 */
class RelationshipsTab extends AbstractTab
{
    /** @var RelationshipsDao */
    private $dao;

    /** @var EntityRegistry */
    private $registry;

    /** @var RelateRecordsBar */
    private $bar;

    /** @var string|null Type key submitted in the Relate-to bar */
    private $submittedType = '';

    public function __construct(RelationshipsDao $dao, EntityRegistry $registry = null)
    {
        $this->dao = $dao;
        $this->registry = $registry ?? new EntityRegistry();
    }

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
        // Handle mutating requests before rendering.
        if ($stockId !== '') {
            if (isset($_GET['remove_rel_type'])) {
                $this->removeRelationship(
                    $stockId,
                    (string) $_GET['remove_rel_type'],
                    (string) $_GET['remove_rel_id'],
                    isset($_GET['remove_rel_role']) ? (string) $_GET['remove_rel_role'] : null
                );
                display_notification(_('Relationship removed.'));
            } elseif (isset($_POST['relate_record_action'])) {
                $rhsType = (string) ($_POST['relate_type'] ?? '');
                $rhsId   = (string) ($_POST['relate_record'] ?? '');
                $this->doRelate($stockId, $rhsType, $rhsId);
                display_notification(_('Record related.'));
            }
        }

        $this->setupBar($stockId);

        $related = $this->getRelatedRecords($stockId);
        $labels  = $this->resolveLabels($related);

        echo '<div class="relationships-tab">';

        // ---- Relate-to bar (shared component) ----------------------------
        echo $this->bar->render();

        // ---- Twistie related-records display (shared renderer) -----------
        $renderer = new DefaultRelatedRecordsRenderer(array(
            'css_class'    => 'related-records',
            'header_class' => 'related-header',
            'table_class'  => 'related-table',
            'table_border' => true,
            'type_labels'  => $this->registry->typeLabels(),
            'label_resolver' => array($this, 'resolveLabel'),
            'url_resolver'   => array($this, 'resolveUrl'),
            'remove_resolver' => array($this, 'renderRemove'),
            'escape'         => array($this, 'esc'),
            'translate'      => function ($v) { return _( $v ); },
        ));

        echo $renderer->renderRelatedRecords(
            'item',
            $stockId,
            $related,
            array()
        );

        echo '</div>';
    }

    /**
     * Build the shared RelateRecordsBar for this item, wiring FA hooks for
     * type/record options.
     */
    private function setupBar(string $stockId): void
    {
        $bar = new RelateRecordsBar(array(
            'lhs_type'    => 'item',
            'lhs_id'      => $stockId,
            'form_action' => $_SERVER['REQUEST_URI'] ?? '',
            'type_options_provider' => array($this, 'availableTypes'),
            'record_options_provider' => array($this, 'recordOptionsFor'),
            'escape'      => array($this, 'esc'),
            'translate'   => function ($v) { return _( $v ); },
        ));

        if (!empty($_POST['relate_type'])) {
            $bar->setSelectedType((string) $_POST['relate_type']);
            $this->submittedType = (string) $_POST['relate_type'];
        } elseif ($this->submittedType !== '') {
            $bar->setSelectedType($this->submittedType);
        }

        $this->bar = $bar;
    }

    /**
     * Available relate-to types, from RELATED_MODULES hook responses or the
     * EntityRegistry pickable types as fallback.
     *
     * @return array<string,string> type key => label
     */
    public function availableTypes(): array
    {
        if (function_exists('hook_invoke_all')) {
            $data = ['modules' => array()];
            hook_invoke_all('RELATED_MODULES', $data);
            $modules = isset($data['modules']) && is_array($data['modules'])
                ? $data['modules']
                : array();
            $types = array();
            foreach ($modules as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $key  = isset($entry['type']) ? (string) $entry['type'] : '';
                $label = isset($entry['label']) ? (string) $entry['label'] : $key;
                if ($key !== '') {
                    $types[$key] = $label;
                }
            }
            if (!empty($types)) {
                return $types;
            }
        }

        $types = array();
        foreach ($this->registry->pickable() as $key => $def) {
            $types[$key] = isset($def['title']) ? (string) $def['title'] : $key;
        }

        return $types;
    }

    /**
     * Record id => label options for one entity type, via a
     * `GET_<Type>Options` hook responder or a direct table read fallback.
     *
     * @param string $type Entity type key
     * @return array<string,string>
     */
    public function recordOptionsFor(string $type): array
    {
        if (function_exists('hook_invoke_all')) {
            $hookName = 'get' . ucfirst((string) $type) . 'Options';
            $payload  = ['entity_type' => $type, 'options' => array()];
            hook_invoke_all($hookName, $payload);
            if (isset($payload['options']) && is_array($payload['options'])) {
                $options = array();
                foreach ($payload['options'] as $value => $label) {
                    $options[(string) $value] = (string) $label;
                }
                if (!empty($options)) {
                    return $options;
                }
            }
        }

        $def = $this->registry->get($type);
        if ($def === null) {
            return array();
        }

        return $this->dao->listOptions($def['table'], $def['pk'], $def['label']);
    }

    // ------------------------------------------------------------------
    // Mutations via RelatedTo hooks / repository
    // ------------------------------------------------------------------

    /**
     * Create a relationship from this item to the selected record.
     */
    private function doRelate(string $stockId, string $rhsType, string $rhsId): void
    {
        if ($rhsType === '' || $rhsId === '') {
            return;
        }

        $dto = new RelatedRecordDto('item', $stockId, $rhsType, $rhsId, 'reference');

        if (function_exists('hook_invoke')) {
            $result = $dto;
            hook_invoke('ksf_FA_RelatedTo', 'RELATE_RECORDS', $result, null);
            return;
        }

        $repoClass = '\\ksfraser\\FrontAccounting\\RelatedTo\\Repository\\RelatedRecordRepository';
        if (class_exists($repoClass)) {
            (new $repoClass())->relate($dto);
        }
    }

    /**
     * Fetch all records related to this item via the RELATED_TO hook.
     *
     * @return RelatedRecordDto[]
     */
    private function getRelatedRecords(string $stockId): array
    {
        if (!function_exists('hook_invoke')) {
            return array();
        }

        $query = new RelatedQueryDto('item', $stockId);
        $result = $query;
        hook_invoke('ksf_FA_RelatedTo', 'RELATED_TO', $result, null);

        $dtos = array();
        if (is_array($result) && isset($result['related']) && is_array($result['related'])) {
            foreach ($result['related'] as $rel) {
                if ($rel instanceof RelatedRecordDto) {
                    $dtos[] = $rel;
                }
            }
        }

        return $dtos;
    }

    /**
     * Remove a relationship via the UNRELATE_RECORDS hook (dispatched to
     * ksf_FA_RelatedTo). Falls back to direct repository call when hooks are
     * unavailable.
     */
    private function removeRelationship(
        string $stockId,
        string $rhsType,
        string $rhsId,
        ?string $linkRole
    ): void {
        $dto = new RelatedRecordDto('item', $stockId, $rhsType, $rhsId, $linkRole);

        if (function_exists('hook_invoke')) {
            $result = $dto;
            hook_invoke('ksf_FA_RelatedTo', 'UNRELATE_RECORDS', $result, null);
            return;
        }

        $repoClass = '\\ksfraser\\FrontAccounting\\RelatedTo\\Repository\\RelatedRecordRepository';
        if (class_exists($repoClass)) {
            (new $repoClass())->unrelate('item', $stockId, $rhsType, $rhsId, $linkRole);
        }
    }

    // ------------------------------------------------------------------
    // Renderer callbacks
    // ------------------------------------------------------------------

    /**
     * Resolve a record label via EntityRegistry + RelationshipsDao.
     */
    public function resolveLabel(string $type, string $id): string
    {
        $def = $this->registry->get($type);
        if ($def === null) {
            return $id;
        }

        $label = $this->dao->resolveLabel($def['table'], $def['pk'], $def['label'], $id);
        return $label !== null ? $label : $id;
    }

    /**
     * Resolve a record URL via EntityUrlResolver pattern — best effort.
     */
    public function resolveUrl(string $type, string $id): string
    {
        return '#';
    }

    /**
     * Render a Remove link for one related record (GET-based, processed at
     * the top of renderTabContent).
     */
    public function renderRemove(RelatedRecordDto $rel, string $lhsId): string
    {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $sep = strpos($url, '?') === false ? '?' : '&';
        $url .= $sep . 'remove_rel_type=' . urlencode($rel->rhsType)
            . '&remove_rel_id=' . urlencode((string) $rel->rhsId);
        if ($rel->linkRole !== null) {
            $url .= '&remove_rel_role=' . urlencode($rel->linkRole);
        }

        return '<a href="' . $this->esc($url) . '" onclick="return confirm(\''
            . $this->esc(_('Remove this relationship?')) . '\');">'
            . _('Remove') . '</a>';
    }

    /**
     * HTML escape.
     */
    private function esc(string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
