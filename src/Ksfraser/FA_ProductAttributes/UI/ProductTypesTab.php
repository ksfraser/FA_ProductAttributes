<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Renders a read-only overview of product types used with attributes.
 */
class ProductTypesTab
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Output product-type information panel.
     */
    public function render(): void
    {
        echo '<h3>' . _('Product Types') . '</h3>';

        echo '<p>' . _('Product attributes support the following product types:') . '</p>';

        echo '<table class="tablestyle2">';
        echo '<tr><th>' . _('Type') . '</th><th>' . _('Description') . '</th></tr>';
        echo '<tr>';
        echo '<td>' . _('Simple') . '</td>';
        echo '<td>' . _('A standard product with no variations. Attributes are informational.') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>' . _('Variable') . '</td>';
        echo '<td>' . _('A parent product that has child variations generated from its attribute categories.') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>' . _('Variation') . '</td>';
        echo '<td>' . _('A child product automatically generated from a Variable parent\'s attribute combinations.') . '</td>';
        echo '</tr>';
        echo '</table>';
    }
}
