<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * Service class for Product Attributes UI and data operations
 *
 * Handles rendering, saving, and deleting product attributes for items
 */
class ProductAttributesService
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db)
    {
        $this->dao = $dao;
        $this->db = $db;
    }

    /**
     * Render the Product Attributes tab content for an item
     *
     * @param string $stockId The item stock ID
     * @return string HTML content for the tab
     */
    public function renderProductAttributesTab(string $stockId): string
    {
        try {
            // Get assigned categories for this product
            $assignedCategories = $this->dao->getAssignedCategoriesForProduct($stockId);

            // Get individual value assignments for this product
            $individualAssignments = $this->dao->listAssignments($stockId);

            // Group individual assignments by category
            $assignmentsByCategory = [];
            foreach ($individualAssignments as $assignment) {
                $catId = $assignment['category_id'];
                if (!isset($assignmentsByCategory[$catId])) {
                    $assignmentsByCategory[$catId] = [
                        'category' => $assignment['category_label'],
                        'values' => []
                    ];
                }
                $assignmentsByCategory[$catId]['values'][] = $assignment['value_label'];
            }

            // Render the tab content
            ob_start();
            ?>
            <div class="tab-content">
                <h3><?php echo _('Product Attributes'); ?></h3>

                <?php if (empty($assignedCategories) && empty($individualAssignments)): ?>
                    <p><?php echo _('No product attributes assigned to this item.'); ?></p>
                    <p><?php echo _('Attributes can be assigned at the category level in the Product Attributes admin, or individual values can be assigned here.'); ?></p>
                <?php else: ?>
                    <table class="tablestyle">
                        <thead>
                            <tr>
                                <th><?php echo _('Category'); ?></th>
                                <th><?php echo _('Assigned Values'); ?></th>
                                <th><?php echo _('Assignment Type'); ?></th>
                                <th><?php echo _('Variations'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Show individual assignments first
                            foreach ($assignmentsByCategory as $catId => $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['category']); ?></td>
                                    <td><?php echo htmlspecialchars(implode(', ', $data['values'])); ?></td>
                                    <td><?php echo _('Individual Values'); ?></td>
                                    <td><?php echo count($data['values']); ?></td>
                                </tr>
                            <?php endforeach;

                            // Show category assignments (excluding those with individual assignments)
                            foreach ($assignedCategories as $category):
                                if (!isset($assignmentsByCategory[$category['id']])):
                                    $values = $this->dao->getValuesForCategory($category['id']);
                                    $valueLabels = array_column($values, 'value');
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['label']); ?></td>
                                    <td><?php echo htmlspecialchars(implode(', ', $valueLabels)); ?></td>
                                    <td><?php echo _('All Category Values'); ?></td>
                                    <td><?php echo count($values); ?></td>
                                </tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h4><?php echo _('Manage Individual Value Assignments'); ?></h4>
                <p><?php echo _('Select specific attribute values for this product. Individual assignments override category assignments.'); ?></p>

                <form method="post" action="">
                    <?php
                    // Get all available categories
                    $allCategories = $this->dao->listCategories();

                    foreach ($allCategories as $category):
                        $categoryValues = $this->dao->getValuesForCategory($category['id']);
                        if (empty($categoryValues)) continue;

                        // Get currently assigned values for this category
                        $assignedValueIds = array_column(
                            array_filter($individualAssignments, function($a) use ($category) {
                                return $a['category_id'] == $category['id'];
                            }),
                            'value_id'
                        );
                    ?>
                    <fieldset>
                        <legend><?php echo htmlspecialchars($category['label']); ?> (<?php echo htmlspecialchars($category['code']); ?>)</legend>

                        <?php foreach ($categoryValues as $value): ?>
                            <label style="display: inline-block; margin-right: 15px;">
                                <input type="checkbox"
                                       name="attribute_values[<?php echo $category['id']; ?>][]"
                                       value="<?php echo $value['id']; ?>"
                                       <?php echo in_array($value['id'], $assignedValueIds) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($value['value']); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <?php endforeach; ?>

                    <div style="margin-top: 15px;">
                        <button type="submit" name="save_product_attributes" value="1" class="btn btn-primary">
                            <?php echo _('Save Attribute Assignments'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php
            return ob_get_clean();

        } catch (\Exception $e) {
            return '<div class="error">' . _('Error loading product attributes: ') . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Save product attributes data for an item
     *
     * @param string $stockId The item stock ID
     * @param array $postData The POST data
     */
    public function saveProductAttributes(string $stockId, array $postData): void
    {
        try {
            // Handle individual value assignments
            if (isset($postData['attribute_values']) && is_array($postData['attribute_values'])) {
                // First, remove all existing individual assignments for this product
                $existingAssignments = $this->dao->listAssignments($stockId);
                foreach ($existingAssignments as $assignment) {
                    $this->dao->deleteAssignment($assignment['id']);
                }

                // Add new assignments based on form data
                foreach ($postData['attribute_values'] as $categoryId => $valueIds) {
                    if (is_array($valueIds)) {
                        foreach ($valueIds as $valueId) {
                            $this->dao->addAssignment($stockId, (int)$categoryId, (int)$valueId);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            error_log('ProductAttributesService: Error saving product attributes: ' . $e->getMessage());
            throw $e; // Re-throw to let caller handle
        }
    }

    /**
     * Delete product attributes data for an item
     *
     * @param string $stockId The item stock ID
     */
    public function deleteProductAttributes(string $stockId): void
    {
        try {
            // Delete individual value assignments
            $existingAssignments = $this->dao->listAssignments($stockId);
            foreach ($existingAssignments as $assignment) {
                $this->dao->deleteAssignment($assignment['id']);
            }

            // Note: Category assignments are managed separately and should be cleaned up by the admin interface

        } catch (\Exception $e) {
            error_log('ProductAttributesService: Error deleting product attributes: ' . $e->getMessage());
            throw $e; // Re-throw to let caller handle
        }
    }
}