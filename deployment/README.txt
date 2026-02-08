FA_ProductAttributes_Variations Module Deployment Package

DEPLOYMENT INSTRUCTIONS:
========================

1. Copy the FA_ProductAttributes_Variations directory to your FrontAccounting modules directory:

   On your server:
   cd /var/www/html/devel/FrontAccounting
   cp -r /path/to/this/deployment/FA_ProductAttributes_Variations modules/

2. Set proper permissions:
   chmod -R 755 modules/FA_ProductAttributes_Variations
   chown -R www-data:www-data modules/FA_ProductAttributes_Variations

3. In FA Admin Panel:
   - Go to Setup → Install/Update Modules
   - Activate 'FA_ProductAttributes_Variations'

FILES INCLUDED:
===============
- _init/config                 - Module configuration
- hooks.php                    - FA integration hooks
- product_variations_admin.php - Admin interface
- check_compatibility.php      - Compatibility checker

DEPENDENCIES:
=============
- Requires FA_ProductAttributes_Core to be installed first
- Requires the shared FA_ProductAttributes library

TROUBLESHOOTING:
================
If you get 'File parse error' or 'key_exists() expects array', the module files are missing from the FA modules directory.