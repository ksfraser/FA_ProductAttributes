<?php

namespace Ksfraser\FA_ProductAttributes\Variations\UI;

/**
 * Single responsibility: render the variation action button row
 * (Generate Combinations / Create Child Product) for a parent item.
 *
 * Exactly two actions are exposed, per the agreed 2-button design:
 *  1. Generate Combinations  -> GenerateCombosAction  : persist the cartesian pool.
 *  2. Create Child Product   -> CreateChildProductAction : instantiate the pool
 *     into stock_master children (native add_item) + FULL PA-attribute clone.
 * Both are emitted with FA's `ajaxsubmit` class so they dispatch through FA's
 * JsHttpRequest handler (`js/inserts.js:333`) instead of native form
 * submission. That bypasses the polluting of the host items.php form's
 * `target`/`onsubmit` attributes that breaks plain submit buttons, and keeps
 * this tab inside the host form with no nested <form> (see
 * APP_TAB_ARCHITECTURE.md §10). The host form still carries `_tabs_sel` and
 * `stock_id`, so `handlePostActions()` sees the posted button name.
 *
 * No <form> is emitted here.
 */
class VariationActionButtons
{
    /**
     * @param bool $render Whether the action row should be shown (parent item).
     */
    public function render(bool $render): void
    {
        if (!$render) {
            return;
        }

        echo '<p>';
        echo $this->button('generate_combos', _('Generate Combinations'));
        echo ' ';
        echo $this->button('create_child_product', _('Create Child Product'));
        echo '</p>';
    }

    /**
     * Build a single ajaxsubmit submit button.
     *
     * @param string $name  Button name (also the POST key gating the action).
     * @param string $label Button label.
     * @return string Button HTML.
     */
    private function button(string $name, string $label): string
    {
        $name  = htmlspecialchars($name, ENT_QUOTES);
        $label = htmlspecialchars($this->localise($label), ENT_QUOTES);

        return '<button class="ajaxsubmit" type="submit" formnovalidate name="' . $name
            . '" id="' . $name . '" value="' . $label . '"><span>' . $label . '</span></button>';
    }

    /**
     * @param string $message Untranslated message.
     * @return string Translated (or original outside FA).
     */
    private function localise(string $message): string
    {
        return function_exists('_') ? _($message) : $message;
    }
}