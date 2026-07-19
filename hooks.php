<?php

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Dao\MediaAttachmentsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler;
use Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration;
use Ksfraser\FA_ProductAttributes\Plugin\PluginLoader;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

if (!class_exists('hooks')) {
    class hooks
    {
    }
}

if (!defined('FA_PRODUCT_ATTRIBUTES_HOOKS_LOADED')) {
    define('FA_PRODUCT_ATTRIBUTES_HOOKS_LOADED', true);
}

if (!defined('SS_PRODUCT_ATTRIBUTES')) {
    define('SS_PRODUCT_ATTRIBUTES', 115 << 8);
}

if (!defined('SS_FA_ProductAttributes')) {
    define('SS_FA_ProductAttributes', SS_PRODUCT_ATTRIBUTES);
}

if (!defined('SA_PRODUCT_ATTRIBUTES')) {
    define('SA_PRODUCT_ATTRIBUTES', SS_PRODUCT_ATTRIBUTES | 1);
}

if (!defined('SA_FA_ProductAttributes')) {
    define('SA_FA_ProductAttributes', SA_PRODUCT_ATTRIBUTES);
}

class hooks_FA_ProductAttributes extends hooks
{
    /** @var string */
    var $module_name = 'FA_ProductAttributes';

    public function __construct()
    {
        $this->load_autoloader();
    }

    public function install()
    {
        return true;
    }

    public function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'stock':
                $app->add_rapp_function(
                    2,
                    _('Product Attributes'),
                    $path_to_root . '/modules/' . $this->module_name . '/public/index.php',
                    'SA_PRODUCT_ATTRIBUTES'
                );
                $app->add_rapp_function(
                    2,
                    _('Lifecycle Flags'),
                    $path_to_root . '/modules/' . $this->module_name . '/public/lifecycle-flags.php',
                    'SA_PRODUCT_ATTRIBUTES'
                );
                break;
        }
    }

    public function install_access()
    {
        $security_areas = array(
            'SA_PRODUCT_ATTRIBUTES' => array(SS_PRODUCT_ATTRIBUTES | 1, _('Product Attributes')),
            'SA_FA_ProductAttributes' => array(SS_PRODUCT_ATTRIBUTES | 1, _('Product Attributes')),
        );
        $security_sections = array(
            SS_PRODUCT_ATTRIBUTES => _('Product Attributes'),
        );

        return array($security_areas, $security_sections);
    }

    public function activate()
    {
        $this->register_hooks();
        return true;
    }

    public function activate_extension($company, $check_only = true)
    {
        if (!$check_only) {
            $this->register_hooks();
        }

        return true;
    }

    public function deactivate()
    {
        unset($GLOBALS['fa_product_attributes_services_cache']);
        return true;
    }

    public function register_hooks()
    {
        // FA hook_invoke_all() calls hook methods directly on this class.
        // Keep this lifecycle method for parity with the historical implementation.
    }

    public function item_display_tab_headers($tabs, $stockId = '')
    {
        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return $tabs;
        }

        if (is_object($tabs) && method_exists($tabs, 'createTab')) {
            return $this->get_items_integration()->addTabHeaders($tabs, (string)$stockId);
        }

        if (!is_array($tabs)) {
            return $tabs;
        }

        $resolvedStockId = $stockId;
        if ($resolvedStockId === '' && isset($_POST['stock_id'])) {
            $resolvedStockId = (string)$_POST['stock_id'];
        }

        $tabs['product_attributes'] = array(
            _('Product Attributes'),
            $resolvedStockId
        );

        $tabs['shipping_attributes'] = array(
            _('Shipping'),
            $resolvedStockId
        );

        $tabs['product_identifiers'] = array(
            _('Identifiers'),
            $resolvedStockId
        );

        $tabs['product_lifecycle'] = array(
            _('Lifecycle'),
            $resolvedStockId
        );

        $tabs['product_media'] = array(
            _('Media'),
            $resolvedStockId
        );

        $tabs['product_urls'] = array(
            _('URLs'),
            $resolvedStockId
        );

        $tabs['product_warranty'] = array(
            _('Warranty'),
            $resolvedStockId
        );

        return $tabs;
    }

    public function item_display_tab_content($stockId = '', $selectedTab = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return false;
        }

        $resolvedStockId = $stockId;
        if ($resolvedStockId === '' && isset($_POST['stock_id'])) {
            $resolvedStockId = (string)$_POST['stock_id'];
        }

        if ($selectedTab === 'product_attributes') {
            $this->get_items_integration()->getTabContent((string)$resolvedStockId, (string)$selectedTab);
            return true;
        }

        if ($selectedTab === 'shipping_attributes') {
            $this->render_shipping_tab((string)$resolvedStockId);
            return true;
        }

        if ($selectedTab === 'product_identifiers') {
            $this->render_identifiers_tab((string)$resolvedStockId);
            return true;
        }

        if ($selectedTab === 'product_lifecycle') {
            $this->render_lifecycle_tab((string)$resolvedStockId);
            return true;
        }

        if ($selectedTab === 'product_media') {
            $this->render_local_media_tab((string)$resolvedStockId);
            return true;
        }

        if ($selectedTab === 'product_urls') {
            $this->render_urls_tab((string)$resolvedStockId);
            return true;
        }

        if ($selectedTab === 'product_warranty') {
            $this->render_warranty_tab((string)$resolvedStockId);
            return true;
        }

        if (preg_match('/^product_attributes/', (string)$selectedTab)) {
            $this->get_items_integration()->getTabContent((string)$resolvedStockId, (string)$selectedTab);
            return true;
        }

        return false;
    }

    public function pre_item_write($itemData, $stockId = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return is_array($itemData) ? $itemData : array();
        }

        if (!is_array($itemData)) {
            $itemData = array();
        }

        if ($stockId === '' && isset($itemData['stock_id'])) {
            $stockId = (string)$itemData['stock_id'];
        }

        $this->get_product_attributes_handler()
            ->handle_product_attributes_save($itemData, (string)$stockId);

        $this->save_shipping_from_post((string)$stockId);
        $this->save_identifiers_from_post((string)$stockId);
        $this->save_lifecycle_from_post((string)$stockId);
        $this->save_warranty_from_post((string)$stockId);

        return $itemData;
    }

    public function pre_item_delete($stockId = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return null;
        }

        $this->get_product_attributes_handler()
            ->handle_product_attributes_delete((string)$stockId);

        $services = $this->get_services();
        $services['shipping_dao']->delete((string)$stockId);
        $services['identifiers_dao']->delete((string)$stockId);
        $services['lifecycle_dao']->delete((string)$stockId);
        $services['warranty_dao']->delete((string)$stockId);
        $services['media_attachments_dao']->deleteByStockId((string)$stockId);
        $this->delete_all_media_files((string)$stockId);
        $services['lifecycle_flag_defs_dao']->deleteAssignments((string)$stockId);

        return null;
    }

    private function load_autoloader()
    {
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoloadPath)) {
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        }
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    private function load_plugins_on_demand()
    {
        if (class_exists(PluginLoader::class)) {
            PluginLoader::getInstance()->loadPluginsOnDemand();
        }
    }

    private function render_shipping_tab(string $stockId): void
    {
        $services = $this->get_services();
        $shippingDao = $services['shipping_dao'];
        $data = ($stockId !== '') ? ($shippingDao->get($stockId) ?? []) : [];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_shipping_attributes">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        $this->render_fieldset(_('Package Dimensions'), [
            'Length'  => $this->input_number('length', $data, 'number', '0.001'),
            'Width'   => $this->input_number('width', $data, 'number', '0.001'),
            'Height'  => $this->input_number('height', $data, 'number', '0.001'),
            'Unit'    => $this->select_field('dim_unit', ['cm' => 'cm', 'in' => 'in'], $data, 'cm'),
        ]);

        $this->render_fieldset(_('Weight / Mass'), [
            'Weight' => $this->input_number('weight', $data, 'number', '0.001'),
            'Unit'   => $this->select_field('weight_unit', ['kg' => 'kg', 'lb' => 'lb', 'g' => 'g', 'oz' => 'oz'], $data, 'kg'),
        ]);

        echo '<fieldset><legend>' . _('Handling Requirements') . '</legend>';
        echo '<p><label><input type="checkbox" name="is_hazardous" value="1"'
            . $this->checked($data, 'is_hazardous') . '> '
            . _('Hazardous / Dangerous Goods') . '</label></p>';
        echo '<p><label><input type="checkbox" name="is_fragile" value="1"'
            . $this->checked($data, 'is_fragile') . '> '
            . _('Fragile') . '</label></p>';
        echo '<p><label><input type="checkbox" name="is_stackable" value="1"'
            . $this->checked($data, 'is_stackable', true) . '> '
            . _('Stackable') . '</label></p>';
        echo '<p><label><input type="checkbox" name="is_oversize" value="1"'
            . $this->checked($data, 'is_oversize') . '> '
            . _('Oversize') . '</label></p>';
        echo '<p><label><input type="checkbox" name="is_perishable" value="1"'
            . $this->checked($data, 'is_perishable') . '> '
            . _('Perishable') . '</label></p>';
        echo '</fieldset>';

        $this->render_fieldset(_('Customs / International Trade'), [
            'HS Code'    => $this->input_text('hs_code', $data),
            'Origin'     => $this->input_text('country_of_origin', $data),
            'Declared $' => $this->input_number('declared_value', $data, 'number', '0.01'),
        ]);

        echo '<p><input type="submit" value="' . _('Save Shipping Attributes') . '"></p>';
        echo '</form>';
    }

    private function render_identifiers_tab(string $stockId): void
    {
        $services = $this->get_services();
        $identifiersDao = $services['identifiers_dao'];
        $data = ($stockId !== '') ? ($identifiersDao->get($stockId) ?? []) : [];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_identifiers">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        $this->render_fieldset(_('Brand & Manufacturer'), [
            'Brand'        => $this->input_text('brand', $data),
            'Manufacturer' => $this->input_text('manufacturer', $data),
            'Model No.'    => $this->input_text('model_no', $data),
        ]);

        $this->render_fieldset(_('Barcodes & Global Trade IDs'), [
            'MPN'             => $this->input_text('mpn', $data),
            'GTIN-14'         => $this->input_text('gtin', $data),
            'EAN-13'          => $this->input_text('ean', $data),
            'UPC-A'           => $this->input_text('upc', $data),
            'ISBN-13'         => $this->input_text('isbn', $data),
            'ASIN (Amazon)'   => $this->input_text('asin', $data),
            'Internal Barcode' => $this->input_text('internal_barcode', $data),
        ]);

        $this->render_fieldset(_('Sourcing References'), [
            'Supplier Part No.' => $this->input_text('supplier_part_no', $data),
        ]);

        echo '<p><input type="submit" value="' . _('Save Identifiers') . '"></p>';
        echo '</form>';
    }

    private function render_lifecycle_tab(string $stockId): void
    {
        $services      = $this->get_services();
        $lifecycleDao  = $services['lifecycle_dao'];
        $flagDefsDao   = $services['lifecycle_flag_defs_dao'];
        $data = ($stockId !== '') ? ($lifecycleDao->get($stockId) ?? []) : [];

        $currentFlags = ($stockId !== '') ? $flagDefsDao->getAssignedFlagIds($stockId) : [];
        $assignedSet  = array_flip(array_map('strval', $currentFlags));

        $flags = $flagDefsDao->listActiveFlags();

        $statusCurrent = (string)($data['status'] ?? 'active');
        $statuses = ['active' => 'Active', 'draft' => 'Draft', 'discontinued' => 'Discontinued', 'archived' => 'Archived'];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_lifecycle">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        echo '<fieldset><legend>' . _('Status') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Product Status') . '</td>';
        echo '<td><select name="status">';
        foreach ($statuses as $val => $label) {
            $sel = ($statusCurrent === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        echo '</select></td></tr></table></fieldset>';

        if (!empty($flags)) {
            echo '<fieldset><legend>' . _('Storefront Flags') . '</legend>';
            echo '<table class="tablestyle_noborder">';
            foreach ($flags as $flag) {
                $flagId  = (int)($flag['id'] ?? 0);
                $code    = htmlspecialchars((string)($flag['code'] ?? ''));
                $label   = htmlspecialchars((string)($flag['label'] ?? ''));
                $checked = isset($assignedSet[(string)$flagId]) ? ' checked' : '';
                echo '<tr>';
                echo '<td><label for="lflag_' . $flagId . '">' . $label . '</label></td>';
                echo '<td><input type="checkbox" id="lflag_' . $flagId . '" '
                    . 'name="lifecycle_flags[]" value="' . $flagId . '"' . $checked . '></td>';
                echo '</tr>';
            }
            echo '</table></fieldset>';
        }

        $from = htmlspecialchars((string)($data['available_from'] ?? ''));
        $on   = htmlspecialchars((string)($data['discontinue_on'] ?? ''));
        $note = htmlspecialchars((string)($data['clearance_note'] ?? ''));

        echo '<fieldset><legend>' . _('Availability Window') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('Available From') . '</td>';
        echo '<td><input type="date" name="available_from" value="' . $from . '"></td></tr>';
        echo '<tr><td>' . _('Discontinue On') . '</td>';
        echo '<td><input type="date" name="discontinue_on" value="' . $on . '"></td></tr>';
        echo '<tr><td>' . _('Clearance Note') . '</td>';
        echo '<td><input type="text" name="clearance_note" maxlength="255" value="' . $note . '" style="width:100%"></td></tr>';
        echo '</table></fieldset>';

        echo '<p><input type="submit" value="' . _('Save Lifecycle') . '"></p>';
        echo '</form>';
    }

    private function render_local_media_tab(string $stockId): void
    {
        $services = $this->get_services();
        $mediaDao = $services['media_dao'];

        // Handle file upload actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $stockId !== '') {
            $action = $_POST['action'] ?? '';

            if ($action === 'upload_media_image' && isset($_FILES['media_file'])) {
                $this->handle_image_upload($stockId, $_FILES['media_file']);
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }

            if ($action === 'delete_media_item') {
                $mediaId = (int)($_POST['media_id'] ?? 0);
                if ($mediaId > 0) {
                    $mediaItem = $mediaDao->getMediaItem($mediaId);
                    if ($mediaItem && $mediaItem['stock_id'] === $stockId) {
                        // Delete the file from disk if it's a local image
                        $this->delete_media_file((string)($mediaItem['url'] ?? ''));
                        $mediaDao->deleteMedia($mediaId);
                    }
                }
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $mediaItems = ($stockId !== '') ? $mediaDao->getProductMedia($stockId) : [];
        $imageDir = $this->get_image_dir();

        // Show primary image from FA filesystem
        echo '<fieldset><legend>' . _('Primary Image') . '</legend>';
        $primaryFile = $this->find_primary_image($stockId);
        if ($primaryFile) {
            $url = $this->get_image_url($primaryFile);
            echo '<p><img src="' . $url . '" style="max-width:200px;max-height:200px;border:1px solid #ccc"></p>';
            echo '<p><small>' . htmlspecialchars(basename($primaryFile)) . '</small></p>';
        } else {
            echo '<p>' . _('No primary image set.') . '</p>';
            echo '<p><small>' . _('Upload via Inventory → Items → edit the item.') . '</small></p>';
        }
        echo '</fieldset>';

        // Show additional images
        echo '<fieldset><legend>' . _('Additional Images & Media') . '</legend>';

        if (empty($mediaItems)) {
            echo '<p>' . _('No additional images or media uploaded yet.') . '</p>';
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Preview') . '</th><th>' . _('Type') . '</th>'
                . '<th>' . _('Alt Text') . '</th><th>' . _('Order') . '</th><th></th></tr>';
            foreach ($mediaItems as $item) {
                $id   = (int)($item['id'] ?? 0);
                $url  = (string)($item['url'] ?? '');
                $type = htmlspecialchars((string)($item['media_type'] ?? 'image'));
                $alt  = htmlspecialchars((string)($item['alt_text'] ?? ''));
                $sort = (int)($item['sort_order'] ?? 0);
                $isPrimary = (int)($item['is_primary'] ?? 0);

                echo '<tr>';
                if ($type === 'image') {
                    $imgUrl = $this->resolve_media_url($url, $imageDir);
                    echo '<td><img src="' . $imgUrl . '" style="max-width:80px;max-height:80px;border:1px solid #ccc"></td>';
                } else {
                    echo '<td><small>' . $url . '</small></td>';
                }
                $typeLabel = $isPrimary ? $type . ' ★' : $type;
                echo '<td>' . $typeLabel . '</td>';
                echo '<td>' . $alt . '</td>';
                echo '<td>' . $sort . '</td>';
                echo '<td><form method="post" action="" style="display:inline">';
                echo '<input type="hidden" name="action" value="delete_media_item">';
                echo '<input type="hidden" name="media_id" value="' . $id . '">';
                echo '<input type="submit" value="' . _('Delete') . '" style="color:red" '
                    . 'onclick="return confirm(\'' . _('Delete this media item and its file?') . '\')">';
                echo '</form></td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        echo '</fieldset>';

        // Upload form
        echo '<fieldset><legend>' . _('Upload Image') . '</legend>';
        echo '<form method="post" action="" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action"   value="upload_media_image">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('File') . '</td>';
        echo '<td><input type="file" name="media_file" accept="image/jpeg,image/png,image/gif"></td></tr>';
        echo '<tr><td>' . _('Alt Text') . '</td>';
        echo '<td><input type="text" name="alt_text" maxlength="255" style="width:100%" '
            . 'placeholder="Describe the image for accessibility"></td></tr>';
        echo '<tr><td>' . _('Sort Order') . '</td>';
        echo '<td><input type="number" name="sort_order" min="0" value="0"></td></tr>';
        echo '</table>';
        echo '<p><small>' . _('Accepted formats: JPEG, PNG, GIF. File will be saved to the company images directory.') . '</small></p>';
        echo '<p><input type="submit" value="' . _('Upload Image') . '"></p>';
        echo '</form></fieldset>';
    }

    /**
     * Handle file upload for product media image.
     */
    private function handle_image_upload(string $stockId, array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
            display_error(_('File upload failed or empty.'));
            return;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            display_error(_('Only JPEG, PNG, and GIF images are accepted.'));
            return;
        }

        global $SysPrefs;
        $maxSize = isset($SysPrefs->max_image_size) ? (int)$SysPrefs->max_image_size : 500000;
        if ($file['size'] > $maxSize) {
            display_error(_('File exceeds maximum image size.'));
            return;
        }

        $imageDir = $this->get_image_dir();
        if (!is_dir($imageDir)) {
            @mkdir($imageDir, 0755, true);
        }

        $ext = $allowedTypes[$mimeType];
        $cleanStockId = item_img_name($stockId);
        $filename = $cleanStockId . '-' . $this->next_image_index($imageDir, $cleanStockId) . '.' . $ext;
        $dest = $imageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            display_error(_('Failed to save uploaded file.'));
            return;
        }

        $services = $this->get_services();
        $altText = trim((string)($_POST['alt_text'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        $services['media_dao']->addMedia(
            $stockId,
            'images/' . $filename,
            'image',
            $altText,
            $sortOrder,
            false,
            ''
        );

        display_notification(_('Image uploaded successfully.'));
    }

    /**
     * Determine the next numeric index for additional images.
     */
    private function next_image_index(string $imageDir, string $cleanStockId): int
    {
        $index = 1;
        while (glob($imageDir . '/' . $cleanStockId . '-' . $index . '.*') !== false
               && count(glob($imageDir . '/' . $cleanStockId . '-' . $index . '.*')) > 0) {
            $index++;
        }
        return $index;
    }

    /**
     * Delete a media file from disk if it's a local path.
     */
    private function delete_media_file(string $url): void
    {
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            return;
        }
        $path = $this->get_image_dir() . '/' . basename($url);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Find the primary image file for an item in the company images directory.
     */
    private function find_primary_image(string $stockId): ?string
    {
        $imageDir = $this->get_image_dir();
        $cleanName = item_img_name($stockId);
        foreach (['jpg', 'png', 'gif'] as $ext) {
            $path = $imageDir . '/' . $cleanName . '.' . $ext;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Get the absolute path to the company images directory.
     */
    private function get_image_dir(): string
    {
        return company_path() . '/images';
    }

    /**
     * Build a URL for an image path.
     */
    private function get_image_url(string $absolutePath): string
    {
        global $path_to_root;
        $companyPath = company_path();
        $relative = str_replace($companyPath, '', $absolutePath);
        return $path_to_root . '/company' . $relative;
    }

    /**
     * Delete all media files and DB records for a stock item.
     */
    private function delete_all_media_files(string $stockId): void
    {
        $services = $this->get_services();
        $mediaDao = $services['media_dao'];
        $mediaItems = $mediaDao->getProductMedia($stockId);
        foreach ($mediaItems as $item) {
            $this->delete_media_file((string)($item['url'] ?? ''));
            $mediaDao->deleteMedia((int)($item['id'] ?? 0));
        }
    }

    /**
     * Resolve a media URL (stored as relative) to a full URL for display.
     */
    private function resolve_media_url(string $url, string $imageDir): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $path = $imageDir . '/' . basename($url);
        if (is_file($path)) {
            return $this->get_image_url($path);
        }
        return $url;
    }

    private function render_urls_tab(string $stockId): void
    {
        $services      = $this->get_services();
        $attachmentsDao = $services['media_attachments_dao'];

        // Handle media POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $stockId !== '') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add_media_attachment') {
                $url = trim((string)($_POST['url'] ?? ''));
                if ($url !== '') {
                    $description = trim((string)($_POST['description'] ?? ''));
                    $attachmentsDao->add($stockId, $url, $description);
                }
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
            if ($action === 'delete_media_attachment') {
                $attachId = (int)($_POST['attachment_id'] ?? 0);
                if ($attachId > 0) {
                    $attachmentsDao->delete($attachId);
                }
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $items = ($stockId !== '') ? $attachmentsDao->listByStockId($stockId) : [];

        echo '<fieldset><legend>' . _('Media Attachments') . '</legend>';

        if (empty($items)) {
            echo '<p>' . _('No attachments added yet.') . '</p>';
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('URL') . '</th><th>' . _('Description') . '</th>'
                . '<th>' . _('Date') . '</th><th></th></tr>';
            foreach ($items as $item) {
                $id   = (int)($item['id'] ?? 0);
                $url  = htmlspecialchars((string)($item['url'] ?? ''));
                $desc = htmlspecialchars((string)($item['description'] ?? ''));
                $date = htmlspecialchars((string)($item['created_date'] ?? ''));
                echo '<tr>';
                echo '<td><a href="' . $url . '" target="_blank">' . $url . '</a></td>';
                echo '<td>' . $desc . '</td>';
                echo '<td>' . $date . '</td>';
                echo '<td><form method="post" action="" style="display:inline">';
                echo '<input type="hidden" name="action" value="delete_media_attachment">';
                echo '<input type="hidden" name="attachment_id" value="' . $id . '">';
                echo '<input type="submit" value="' . _('Delete') . '" style="color:red" '
                    . 'onclick="return confirm(\'' . _('Delete this attachment?') . '\')">';
                echo '</form></td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        echo '</fieldset>';

        echo '<fieldset><legend>' . _('Add Attachment') . '</legend>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="add_media_attachment">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('URL') . ' <span style="color:red">*</span></td>';
        echo '<td><input type="url" name="url" required maxlength="2048" style="width:100%" '
            . 'placeholder="https://youtube.com/watch?v=... or https://..."></td></tr>';
        echo '<tr><td>' . _('Description') . '</td>';
        echo '<td><input type="text" name="description" maxlength="255" style="width:100%" '
            . 'placeholder="Product demo video, installation guide, etc."></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" value="' . _('Add Attachment') . '"></p>';
        echo '</form></fieldset>';
    }

    private function render_warranty_tab(string $stockId): void
    {
        $services    = $this->get_services();
        $warrantyDao = $services['warranty_dao'];
        $data = ($stockId !== '') ? ($warrantyDao->get($stockId) ?? []) : [];

        $currentType = (string)($data['warranty_type'] ?? 'none');
        $types = [
            'none'         => 'None',
            'manufacturer' => 'Manufacturer Warranty',
            'extended'     => 'Extended Warranty',
            'third_party'  => 'Third-Party Warranty',
            'lifetime'     => 'Lifetime Warranty',
        ];

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="upsert_warranty">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        echo '<fieldset><legend>' . _('Warranty Type') . '</legend>';
        echo '<table class="tablestyle_noborder">';
        foreach ($types as $val => $label) {
            $checked = ($currentType === $val) ? ' checked' : '';
            echo '<tr>';
            echo '<td><input type="radio" name="warranty_type" value="' . $val . '"' . $checked
                . ' id="wt_' . $val . '"></td>';
            echo '<td><label for="wt_' . $val . '">' . $label . '</label></td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';

        $mfgDur = $data['manufacturer_duration'] ?? '';
        $mfgUnit = $data['manufacturer_duration_unit'] ?? 'months';
        echo '<fieldset><legend>' . _('Manufacturer Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="manufacturer_duration" min="0" value="'
            . htmlspecialchars((string)$mfgDur) . '"></td>';
        echo '<td>' . $this->duration_unit_select('manufacturer_duration_unit', $mfgUnit) . '</td>';
        echo '</tr></table></fieldset>';

        $extDur = $data['extended_duration'] ?? '';
        $extUnit = $data['extended_duration_unit'] ?? 'months';
        echo '<fieldset><legend>' . _('Extended Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="extended_duration" min="0" value="'
            . htmlspecialchars((string)$extDur) . '"></td>';
        echo '<td>' . $this->duration_unit_select('extended_duration_unit', $extUnit) . '</td>';
        echo '</tr></table></fieldset>';

        $tpDur = $data['third_party_duration'] ?? '';
        $tpUnit = $data['third_party_duration_unit'] ?? 'months';
        echo '<fieldset><legend>' . _('Third-Party Warranty Duration') . '</legend>';
        echo '<table class="tablestyle_noborder"><tr>';
        echo '<td>' . _('Duration') . '</td>';
        echo '<td><input type="number" name="third_party_duration" min="0" value="'
            . htmlspecialchars((string)$tpDur) . '"></td>';
        echo '<td>' . $this->duration_unit_select('third_party_duration_unit', $tpUnit) . '</td>';
        echo '</tr></table></fieldset>';

        $lifeNotes = htmlspecialchars((string)($data['lifetime_notes'] ?? ''));
        echo '<fieldset><legend>' . _('Lifetime Warranty Notes') . '</legend>';
        echo '<input type="text" name="lifetime_notes" maxlength="255" value="' . $lifeNotes . '" style="width:100%">';
        echo '</fieldset>';

        $warrantyNotes = htmlspecialchars((string)($data['warranty_notes'] ?? ''));
        echo '<fieldset><legend>' . _('Warranty Terms / General Notes') . '</legend>';
        echo '<textarea name="warranty_notes" rows="4" style="width:100%">' . $warrantyNotes . '</textarea>';
        echo '</fieldset>';

        echo '<p><input type="submit" value="' . _('Save Warranty') . '"></p>';
        echo '</form>';
    }

    private function duration_unit_select(string $name, string $current): string
    {
        $options = ['days' => 'Days', 'months' => 'Months', 'years' => 'Years'];
        $html    = '<select name="' . $name . '">';
        foreach ($options as $val => $label) {
            $sel   = ($val === $current) ? ' selected' : '';
            $html .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Render a fieldset with label-input rows.
     *
     * @param array<string, string> $fields  label => HTML input element
     */
    private function render_fieldset(string $legend, array $fields): void
    {
        echo '<fieldset><legend>' . $legend . '</legend>';
        echo '<table class="tablestyle_noborder">';
        foreach ($fields as $label => $input) {
            echo '<tr>';
            echo '<td>' . $label . '</td>';
            echo '<td>' . $input . '</td>';
            echo '</tr>';
        }
        echo '</table></fieldset>';
    }

    private function input_text(string $name, array $data): string
    {
        $val = htmlspecialchars((string)($data[$name] ?? ''));
        return '<input type="text" name="' . $name . '" value="' . $val . '" maxlength="128">';
    }

    private function input_number(string $name, array $data, string $type = 'number', string $step = '1'): string
    {
        $val = $data[$name] ?? null;
        $str = ($val !== null && $val !== '') ? (string)((float)$val) : '';
        return '<input type="' . $type . '" step="' . $step . '" min="0" name="' . $name . '" value="'
            . htmlspecialchars($str) . '">';
    }

    private function select_field(string $name, array $options, array $data, string $default): string
    {
        $current = (string)($data[$name] ?? $default);
        $html    = '<select name="' . $name . '">';
        foreach ($options as $val => $label) {
            $sel   = ((string)$val === $current) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars((string)$val) . '"' . $sel . '>'
                . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    private function checked(array $data, string $key, bool $default = false): string
    {
        $val = isset($data[$key]) ? (bool)$data[$key] : $default;
        return $val ? ' checked' : '';
    }

    private function can_check_access()
    {
        return function_exists('user_check_access');
    }

    private function has_product_attributes_access()
    {
        return user_check_access('SA_PRODUCT_ATTRIBUTES')
            || user_check_access('SA_FA_ProductAttributes');
    }

    private function get_items_integration()
    {
        $services = $this->get_services();
        return $services['integration'];
    }

    private function get_product_attributes_handler()
    {
        $services = $this->get_services();
        return $services['handler'];
    }

    private function get_services()
    {
        if (isset($GLOBALS['fa_product_attributes_services_cache'])
            && is_array($GLOBALS['fa_product_attributes_services_cache'])) {
            return $GLOBALS['fa_product_attributes_services_cache'];
        }

        $tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
        $db = new FrontAccountingDbAdapter($tablePrefix);
        $dao = new ProductAttributesDao($db);
        $shippingDao = new ShippingAttributesDao($db);
        $identifiersDao = new ProductIdentifiersDao($db);
        $lifecycleDao = new ProductLifecycleDao($db);
        $mediaDao = new ProductMediaDao($db);
        $warrantyDao = new ProductWarrantyDao($db);
        $lifecycleFlagDefsDao = new LifecycleFlagDefsDao($db);
        $mediaAttachmentsDao = new MediaAttachmentsDao($db);
        $service = new ProductAttributesService($dao, $db);

        $GLOBALS['fa_product_attributes_services_cache'] = array(
            'integration' => new ItemsIntegration($service, $shippingDao, $identifiersDao),
            'handler' => new ProductAttributesHandler($service),
            'shipping_dao' => $shippingDao,
            'identifiers_dao' => $identifiersDao,
            'lifecycle_dao' => $lifecycleDao,
            'media_dao' => $mediaDao,
            'media_attachments_dao' => $mediaAttachmentsDao,
            'warranty_dao' => $warrantyDao,
            'lifecycle_flag_defs_dao' => $lifecycleFlagDefsDao,
        );

        return $GLOBALS['fa_product_attributes_services_cache'];
    }

    private function save_shipping_from_post(string $stockId): void
    {
        if ($stockId === '') {
            return;
        }
        $shippingKeys = [
            'length', 'width', 'height', 'dim_unit',
            'weight', 'weight_unit',
            'is_hazardous', 'hazmat_class', 'un_number', 'proper_shipping_name', 'packing_group',
            'is_fragile', 'is_stackable', 'is_oversize', 'is_perishable',
            'temperature_sensitive', 'temp_min', 'temp_max', 'temp_unit',
            'hs_code', 'country_of_origin', 'customs_description', 'declared_value',
        ];
        $data = [];
        foreach ($shippingKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                $data[$key] = $_POST[$key];
            }
        }
        if (empty($data)) {
            return;
        }
        $services = $this->get_services();
        $services['shipping_dao']->upsert($stockId, $data);
    }

    private function save_identifiers_from_post(string $stockId): void
    {
        if ($stockId === '') {
            return;
        }
        $identifierKeys = [
            'brand', 'manufacturer', 'mpn', 'gtin', 'ean', 'upc',
            'isbn', 'asin', 'internal_barcode', 'supplier_part_no', 'model_no',
        ];
        $data = [];
        foreach ($identifierKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                $data[$key] = $_POST[$key];
            }
        }
        if (empty($data)) {
            return;
        }
        $services = $this->get_services();
        $services['identifiers_dao']->upsert($stockId, $data);
    }

    private function save_lifecycle_from_post(string $stockId): void
    {
        if ($stockId === '') {
            return;
        }
        $services = $this->get_services();

        // Save lifecycle data (status, dates, clearance_note)
        $lifecycleKeys = ['status', 'available_from', 'discontinue_on', 'clearance_note'];
        $data = [];
        foreach ($lifecycleKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                $data[$key] = $_POST[$key];
            }
        }
        if (!empty($data)) {
            $services['lifecycle_dao']->upsert($stockId, $data);
        }

        // Save dynamic flag assignments
        $flagIds = [];
        if (isset($_POST['lifecycle_flags']) && is_array($_POST['lifecycle_flags'])) {
            $flagIds = array_map('intval', $_POST['lifecycle_flags']);
        }
        $services['lifecycle_flag_defs_dao']->setAssignedFlags($stockId, $flagIds);
    }

    private function save_warranty_from_post(string $stockId): void
    {
        if ($stockId === '') {
            return;
        }
        $warrantyKeys = [
            'warranty_type',
            'manufacturer_duration', 'manufacturer_duration_unit',
            'extended_duration', 'extended_duration_unit',
            'third_party_duration', 'third_party_duration_unit',
            'lifetime_notes', 'warranty_notes',
        ];
        $data = [];
        foreach ($warrantyKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                $data[$key] = $_POST[$key];
            }
        }
        if (empty($data)) {
            return;
        }
        $services = $this->get_services();
        $services['warranty_dao']->upsert($stockId, $data);
    }
}
