<?php
$page_security = 'SA_PRODUCTATTRIBUTES_CATEGORIES';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";
if ($use_date_picker)
    $js .= get_js_date_picker();
include_once "$path_to_root/includes/ui.inc";

page(_("Product Categories Administration"));

start_table(TABLESTYLE, "width=80%");
table_header(array(_("Feature"), _("Status"), _("Description")));

label_row(_("Core Module"), _("Required"), _("FA_ProductAttributes core module must be installed"));
label_row(_("Categories Service"), _("Available"), _("Category management and assignment"));
label_row(_("Category Hierarchies"), _("Available"), _("Support for nested category structures"));
label_row(_("Product Categorization"), _("Available"), _("Assign products to multiple categories"));

end_table();

end_page();
?>