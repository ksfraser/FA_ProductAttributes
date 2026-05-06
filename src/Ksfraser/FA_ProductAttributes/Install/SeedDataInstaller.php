<?php

namespace Ksfraser\FA_ProductAttributes\Install;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Seeds the Royal Order predefined attribute categories and values.
 *
 * Based on the Royal Order of Adjectives (English adjective ordering):
 * Opinion → Size → Age → Shape → Color → Origin → Material → Purpose
 *
 * Call seed() on first install or when the admin wants to populate starter data.
 * Safe to call multiple times: existing categories/values are not overwritten.
 */
class SeedDataInstaller
{
    /** @var ProductAttributesDao */
    private $dao;

    /**
     * The 8 Royal Order attribute categories with their sort order (1-8)
     * and a default set of values.
     *
     * @var array<int, array<string, mixed>>
     */
    private static $categories = [
        [
            'code'        => 'opinion',
            'label'       => 'Opinion',
            'description' => 'Subjective quality judgement (e.g. Stylish, Classic)',
            'sort_order'  => 1,
            'values'      => ['Stylish', 'Elegant', 'Trendy', 'Casual', 'Sporty', 'Classic'],
        ],
        [
            'code'        => 'size',
            'label'       => 'Size',
            'description' => 'Physical size (e.g. Small, Large)',
            'sort_order'  => 2,
            'values'      => ['XX-Small', 'X-Small', 'Small', 'Medium', 'Large', 'X-Large', 'XX-Large', 'Oversized'],
        ],
        [
            'code'        => 'age',
            'label'       => 'Age',
            'description' => 'Age or era (e.g. New, Vintage)',
            'sort_order'  => 3,
            'values'      => ['New', 'Vintage', 'Retro', 'Worn', 'Antique'],
        ],
        [
            'code'        => 'shape',
            'label'       => 'Shape',
            'description' => 'Physical shape or cut (e.g. Slim-fit, Tapered)',
            'sort_order'  => 4,
            'values'      => ['Slim-fit', 'Loose', 'Tapered', 'Boxy', 'Flared', 'Straight-cut', 'Skinny', 'Wide-leg'],
        ],
        [
            'code'        => 'color',
            'label'       => 'Color',
            'description' => 'Colour (e.g. Red, Blue)',
            'sort_order'  => 5,
            'values'      => ['Red', 'Blue', 'Black', 'White', 'Green', 'Yellow', 'Pink', 'Gray', 'Purple', 'Beige'],
        ],
        [
            'code'        => 'origin',
            'label'       => 'Origin',
            'description' => 'Country or region of origin (e.g. Italian, Canadian)',
            'sort_order'  => 6,
            'values'      => ['Italian', 'American', 'Japanese', 'French', 'British', 'Spanish', 'German', 'Canadian'],
        ],
        [
            'code'        => 'material',
            'label'       => 'Material',
            'description' => 'Material composition (e.g. Cotton, Wool)',
            'sort_order'  => 7,
            'values'      => ['Cotton', 'Wool', 'Leather', 'Polyester', 'Silk', 'Linen', 'Denim', 'Velvet', 'Suede', 'Synthetic'],
        ],
        [
            'code'        => 'purpose',
            'label'       => 'Purpose',
            'description' => 'Intended use or occasion (e.g. Formal, Casual)',
            'sort_order'  => 8,
            'values'      => ['Formal', 'Casual', 'Athletic', 'Workwear', 'Outdoor', 'Beachwear', 'Loungewear', 'Partywear'],
        ],
    ];

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Whether any attribute categories have already been seeded.
     */
    public function isSeeded(): bool
    {
        $existing = $this->dao->listCategories();
        return count($existing) > 0;
    }

    /**
     * Seed predefined Royal Order categories and values.
     *
     * Skips categories whose code already exists; does not overwrite.
     *
     * @return array{categories_added: int, values_added: int, skipped: int}
     */
    public function seed(): array
    {
        $existingCategories = $this->dao->listCategories();
        $existingCodes      = array_column($existingCategories, 'code');

        $categoriesAdded = 0;
        $valuesAdded     = 0;
        $skipped         = 0;

        foreach (self::$categories as $cat) {
            if (in_array($cat['code'], $existingCodes, true)) {
                $skipped++;
                continue;
            }

            $catId = $this->dao->upsertCategory(
                $cat['code'],
                $cat['label'],
                $cat['description'],
                $cat['sort_order'],
                true
            );
            $categoriesAdded++;

            foreach ($cat['values'] as $sortIndex => $valueName) {
                $slug = strtolower(str_replace([' ', '-'], ['_', '_'], $valueName));
                $this->dao->upsertValue(
                    (string)$catId,
                    $valueName,
                    $slug,
                    ($sortIndex + 1) * 10,
                    true
                );
                $valuesAdded++;
            }
        }

        return [
            'categories_added' => $categoriesAdded,
            'values_added'     => $valuesAdded,
            'skipped'          => $skipped,
        ];
    }

    /**
     * Return the list of predefined categories for inspection / UI display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDefinitions(): array
    {
        return self::$categories;
    }
}
