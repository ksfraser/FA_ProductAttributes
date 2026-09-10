<?php

namespace Ksfraser\FA_ProductAttributes\UI;

/**
 * Single responsibility: render the "Add Assignment" box — category dropdown,
 * value checkboxes, "Add All", sort order and the submit control.
 *
 * The value checkboxes are loaded client-side from the module's
 * ajax_get_values.php endpoint (the same mechanism proven working on the admin
 * page), which keeps this box functional on the item form and on the admin page.
 *
 * Emits no <form> tag — the host form (items.php or the admin page) wraps it.
 *
 * @package Ksfraser\FA_ProductAttributes\UI
 */
class AddAssignmentForm
{
    /** @var array<int, array<string, mixed>> */
    private $categories;

    /** @var string */
    private $stockId;

    /** @var int */
    private $selectedCategoryId;

    /** @var array<int, array<string, mixed>> */
    private $initialValues;

    /** @var string */
    private $idPrefix;

    /** @var array{name: string, value: string, label: string} */
    private $submit;

    /** @var array<string, string> */
    private $extraHidden;

    /**
     * @param array<int, array<string, mixed>> $categories        Category rows (id/code/label).
     * @param string                           $stockId           Stock id ('' hides the hidden field).
     * @param int                              $selectedCategoryId Currently selected category ('' => none preselected).
     * @param array<int, array<string, mixed>> $initialValues     Values to pre-render for the selected category.
     * @param string                           $idPrefix          Element id prefix to avoid collisions.
     * @param array{name?: string, value?: string, label?: string} $submit Submit button config.
     * @param array<string, string>            $extraHidden       Extra hidden inputs (e.g. action).
     */
    public function __construct(
        array $categories,
        string $stockId = '',
        int $selectedCategoryId = 0,
        array $initialValues = [],
        string $idPrefix = 'pa_',
        array $submit = ['name' => 'add_pa_assignment', 'value' => '1', 'label' => 'Add'],
        array $extraHidden = []
    ) {
        $this->categories        = $categories;
        $this->stockId           = $stockId;
        $this->selectedCategoryId = $selectedCategoryId;
        $this->initialValues     = $initialValues;
        $this->idPrefix          = $idPrefix;
        $this->submit            = array_merge(
            ['name' => 'add_pa_assignment', 'value' => '1', 'label' => 'Add'],
            $submit
        );
        $this->extraHidden       = $extraHidden;
    }

    public function render(): string
    {
        $selectId = $this->idPrefix . 'category_select';
        $boxId    = $this->idPrefix . 'values_box';
        $sortId   = $this->idPrefix . 'sort_order';
        $root     = isset($GLOBALS['path_to_root']) ? (string)$GLOBALS['path_to_root'] : '';
        $endpoint = $root . '/modules/FA_ProductAttributes/public/ajax_get_values.php';

        $sortMap = [];
        foreach ($this->categories as $cat) {
            $sortMap[(int)$cat['id']] = (int)($cat['sort_order'] ?? 0);
        }
        $initialSort = $this->selectedCategoryId > 0
            ? (int)($sortMap[$this->selectedCategoryId] ?? 0)
            : 0;

        $html = '<fieldset><legend>' . _('Add Assignment') . '</legend>';

        if ($this->stockId !== '') {
            $html .= '<input type="hidden" name="stock_id" value="' . htmlspecialchars($this->stockId, ENT_QUOTES, 'UTF-8') . '">';
        }

        foreach ($this->extraHidden as $name => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
                . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';
        }

        $html .= '<div><label>' . _('Category') . '</label>';
        $html .= '<select name="category_id" id="' . $selectId . '">';
        $html .= '<option value="">' . _('-- Select Category --') . '</option>';
        foreach ($this->categories as $c) {
            $id = (int)$c['id'];
            $selected = $id === $this->selectedCategoryId ? ' selected' : '';
            $label = (string)($c['label'] ?? $c['code'] ?? '');
            $html .= '<option value="' . $id . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select></div>';

        $html .= '<div><label>' . _('Values') . '</label>';
        $html .= '<div id="' . $boxId . '">';
        if (empty($this->initialValues)) {
            $html .= '<em>' . _('Select a category to load values.') . '</em>';
        } else {
            foreach ($this->initialValues as $v) {
                $html .= '<label class="pa-value-check"><input type="checkbox" name="value_ids[]" value="'
                    . (int)$v['id'] . '"> '
                    . htmlspecialchars((string)($v['value'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . (isset($v['slug']) && $v['slug'] !== '' ? ' (' . htmlspecialchars((string)$v['slug'], ENT_QUOTES, 'UTF-8') . ')' : '')
                    . '</label>';
            }
        }
        $html .= '</div>';
        $html .= '<div style="margin-top:4px">'
            . '<label><input type="checkbox" name="add_all" value="1"> ' . _('Add All') . '</label>'
            . '</div>';
        $html .= '</div>';

        $html .= '<div><label>' . _('Sort order') . '</label>';
        $html .= '<input type="number" name="sort_order" id="' . $sortId . '" value="' . $initialSort . '" min="0"></div>';

        $html .= '<div style="margin-top:8px">';
        $button = '<button type="submit"';
        if ($this->submit['name'] !== '') {
            $button .= ' name="' . htmlspecialchars($this->submit['name'], ENT_QUOTES, 'UTF-8') . '"'
                . ' value="' . htmlspecialchars($this->submit['value'], ENT_QUOTES, 'UTF-8') . '"';
        }
        $button .= '>' . htmlspecialchars($this->submit['label'], ENT_QUOTES, 'UTF-8') . '</button>';
        $html .= $button;
        $html .= '</div>';

        $html .= '</fieldset>';

        $html .= '<script>' . "\n"
            . '(function(){' . "\n"
            . 'var sel=document.getElementById(' . json_encode($selectId) . ');' . "\n"
            . 'var box=document.getElementById(' . json_encode($boxId) . ');' . "\n"
            . 'var sortInput=document.getElementById(' . json_encode($sortId) . ');' . "\n"
            . 'var paSort=' . json_encode($sortMap, JSON_NUMERIC_CHECK) . ';' . "\n"
            . 'if(!sel||!box){return;}' . "\n"
            . 'function esc(s){return String(s).replace(/&/g,\'&amp;\').replace(/</g,\'&lt;\').replace(/>/g,\'&gt;\').replace(/"/g,\'&quot;\');}' . "\n"
            . 'sel.addEventListener(\'change\',function(){' . "\n"
            . 'if(sortInput){sortInput.value=paSort[String(sel.value)]||0;}' . "\n"
            . 'box.innerHTML=\'<em>' . _('Loading...') . '</em>\';' . "\n"
            . 'fetch(' . json_encode($endpoint, JSON_UNESCAPED_SLASHES) . '+\'?category_id=\'+encodeURIComponent(sel.value))' . "\n"
            . '.then(function(r){return r.json();})' . "\n"
            . '.then(function(d){' . "\n"
            . 'var h=\'\';' . "\n"
            . 'for(var i=0;i<d.length;i++){' . "\n"
            . 'h+=\'<label class="pa-value-check"><input type="checkbox" name="value_ids[]" value="\'+d[i].id+\'"> \'+esc(d[i].value)+(d[i].slug?\' (\'+esc(d[i].slug)+\')\':\'\')+\'</label>\';' . "\n"
            . '}' . "\n"
            . 'box.innerHTML=h||\'<em>' . _('No values defined.') . '</em>\';' . "\n"
            . '})' . "\n"
            . '.catch(function(){box.innerHTML=\'<em>' . _('No values defined.') . '</em>\';});' . "\n"
            . '});' . "\n"
            . '})();' . "\n"
            . '</script>';

        return $html;
    }
}