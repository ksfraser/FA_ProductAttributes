<?php

/**
 * FAMock - FrontAccounting Function Mocks for Testing
 *
 * This file provides mock implementations of FrontAccounting functions
 * that are used in the Product Attributes module but not available
 * during unit testing.
 *
 * Following SRP principles with separate stub files for different categories.
 */

// UI Functions
if (!function_exists('start_form')) {
    function start_form($multi = false, $action = null, $method = 'POST') {
        echo '<form method="' . $method . '" action="' . ($action ?: $_SERVER['PHP_SELF']) . '">';
    }
}

if (!function_exists('end_form')) {
    function end_form() {
        echo '</form>';
    }
}

if (!function_exists('start_table')) {
    function start_table($class = '') {
        echo '<table class="' . $class . '">';
    }
}

if (!function_exists('end_table')) {
    function end_table($colspan = 1) {
        echo '</table>';
    }
}

if (!function_exists('table_section_title')) {
    function table_section_title($title) {
        echo '<tr><th colspan="2">' . $title . '</th></tr>';
    }
}

if (!function_exists('table_header')) {
    function table_header($headers) {
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th>' . $header . '</th>';
        }
        echo '</tr>';
    }
}

if (!function_exists('start_row')) {
    function start_row($class = '') {
        echo '<tr' . ($class ? ' class="' . $class . '"' : '') . '>';
    }
}

if (!function_exists('end_row')) {
    function end_row() {
        echo '</tr>';
    }
}

if (!function_exists('label_cell')) {
    function label_cell($value, $class = '', $colspan = 1) {
        echo '<td' . ($class ? ' class="' . $class . '"' : '') .
             ($colspan > 1 ? ' colspan="' . $colspan . '"' : '') . '>' . $value . '</td>';
    }
}

if (!function_exists('text_row')) {
    function text_row($label, $name, $value = '', $size = 20, $max = 64) {
        echo '<tr><td>' . $label . '</td><td><input type="text" name="' . $name .
             '" value="' . htmlspecialchars($value) . '" size="' . $size . '" maxlength="' . $max . '"></td></tr>';
    }
}

if (!function_exists('small_amount_row')) {
    function small_amount_row($label, $name, $value = 0) {
        echo '<tr><td>' . $label . '</td><td><input type="number" step="0.01" name="' . $name .
             '" value="' . $value . '"></td></tr>';
    }
}

if (!function_exists('check_row')) {
    function check_row($label, $name, $checked = false) {
        echo '<tr><td>' . $label . '</td><td><input type="checkbox" name="' . $name .
             '" value="1"' . ($checked ? ' checked' : '') . '></td></tr>';
    }
}

if (!function_exists('hidden')) {
    function hidden($name, $value) {
        echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
    }
}

if (!function_exists('submit_center')) {
    function submit_center($name, $value, $echo_on_click = true) {
        echo '<tr><td colspan="2" align="center"><input type="submit" name="' . $name .
             '" value="' . $value . '"></td></tr>';
    }
}

if (!function_exists('submit')) {
    function submit($name, $value, $echo_on_click = true) {
        echo '<input type="submit" name="' . $name . '" value="' . $value . '">';
    }
}

if (!function_exists('edit_button_cell')) {
    function edit_button_cell($name, $value) {
        echo '<td><input type="submit" name="' . $name . '" value="' . $value . '"></td>';
    }
}

if (!function_exists('delete_button_cell')) {
    function delete_button_cell($name, $value) {
        echo '<td><input type="submit" name="' . $name . '" value="' . $value . '"></td>';
    }
}

// Notification Functions
if (!function_exists('display_notification')) {
    function display_notification($message) {
        // In tests, we'll collect notifications instead of outputting them
        if (!isset($GLOBALS['test_notifications'])) {
            $GLOBALS['test_notifications'] = [];
        }
        $GLOBALS['test_notifications'][] = $message;
    }
}

if (!function_exists('display_error')) {
    function display_error($message) {
        // In tests, we'll collect errors instead of outputting them
        if (!isset($GLOBALS['test_errors'])) {
            $GLOBALS['test_errors'] = [];
        }
        $GLOBALS['test_errors'][] = $message;
    }
}

// Translation Function
if (!function_exists('_')) {
    function _($text) {
        return $text; // Return as-is for testing
    }
}

// Constants
if (!defined('TABLESTYLE2')) {
    define('TABLESTYLE2', 'tablestyle2');
}

// Page Functions
if (!function_exists('page')) {
    function page($title, $no_menu = false, $is_index = false, $onload = "", $js = "") {
        // Mock page start - in tests we don't need full HTML output
        echo '<!DOCTYPE html><html><head><title>' . $title . '</title></head><body>';
        echo '<h1>' . $title . '</h1>';
    }
}

if (!function_exists('end_page')) {
    function end_page($no_menu = false, $is_index = false) {
        // Mock page end
        echo '</body></html>';
    }
}

// Security Functions
if (!function_exists('user_check_access')) {
    function user_check_access($access) {
        // Mock - always return true for testing
        return true;
    }
}

// Session/Company Functions
if (!function_exists('get_company_pref')) {
    function get_company_pref($name) {
        // Mock - return default values
        return null;
    }
}

// Mock session for testing
if (!isset($_SESSION)) {
    $_SESSION = [];
}
if (!isset($_SESSION['wa_current_user'])) {
    $_SESSION['wa_current_user'] = new class {
        public $company = 0;
        public $user = 1;
        public $loginname = 'test_user';
    };
}

// Database Constants
if (!defined('TB_PREF')) {
    define('TB_PREF', 'fa_');
}

// Hook System Functions
if (!function_exists('fa_hooks')) {
    function fa_hooks() {
        // Mock hook manager for testing
        if (!isset($GLOBALS['mock_fa_hooks'])) {
            $GLOBALS['mock_fa_hooks'] = new class {
                public function apply_filters($filter, $value, ...$args) {
                    return $value; // Return unchanged for testing
                }
                public function do_action($action, ...$args) {
                    // Do nothing for testing
                }
                public function add_filter($filter, $callback, $priority = 10) {
                    // Do nothing for testing
                }
                public function add_action($action, $callback, $priority = 10) {
                    // Do nothing for testing
                }
                public function call_hook($hook_name, ...$args) {
                    // Return the first argument (value) unchanged, or null if no args
                    return isset($args[0]) ? $args[0] : null;
                }
            };
        }
        return $GLOBALS['mock_fa_hooks'];
    }
}

// FA Native Hook Functions
if (!function_exists('hook_invoke_all')) {
    function hook_invoke_all($hook_name, $args = []) {
        // Mock FA's hook_invoke_all function
        // For filter hooks, return the first arg (value to filter)
        // For action hooks, return empty array
        // This is a simplified mock - in real FA, this would call registered hook functions

        // For button/content hooks, return empty array (no additional content in tests)
        return [];
    }
}

// Global Variables
if (!isset($GLOBALS['path_to_root'])) {
    $GLOBALS['path_to_root'] = '/mock/fa/root'; // Mock path for testing
}

// Database Functions
if (!function_exists('db_query')) {
    // Mock database result resource
    class MockDbResult {
        private $data;
        private $position = 0;

        public function __construct($data) {
            $this->data = $data;
        }

        public function fetch_assoc() {
            if ($this->position < count($this->data)) {
                return $this->data[$this->position++];
            }
            return false;
        }
    }

    function db_query($sql, $error_msg = 'Database error') {
        // Simple mock - return mock result for SELECT, success for others
        if (stripos(trim($sql), 'SELECT') === 0) {
            // Mock some sample data for SELECT queries
            return new MockDbResult([
                ['id' => 1, 'name' => 'Test Item'],
                ['id' => 2, 'name' => 'Another Item']
            ]);
        }
        // For INSERT/UPDATE/DELETE, just return true (success)
        return true;
    }
}

if (!function_exists('db_fetch_assoc')) {
    function db_fetch_assoc($result) {
        if ($result instanceof MockDbResult) {
            return $result->fetch_assoc();
        }
        return false;
    }
}

if (!function_exists('db_insert_id')) {
    function db_insert_id() {
        // Mock last insert ID
        return 123;
    }
}

if (!function_exists('db_escape')) {
    function db_escape($value) {
        // Mock FA db_escape - adds quotes and escapes
        // In real FA this would properly escape and quote the value
        return "'" . addslashes($value) . "'";
    }
}

// Hook system mocks for testing
class FAMock {
    private static $filters = [];

    public static function add_filter($filter_name, $callback, $priority = 10) {
        if (!isset(self::$filters[$filter_name])) {
            self::$filters[$filter_name] = [];
        }
        if (!isset(self::$filters[$filter_name][$priority])) {
            self::$filters[$filter_name][$priority] = [];
        }
        self::$filters[$filter_name][$priority][] = $callback;
    }

    public static function apply_filters($filter_name, $value) {
        if (!isset(self::$filters[$filter_name])) {
            return $value;
        }

        // Sort by priority
        ksort(self::$filters[$filter_name]);

        foreach (self::$filters[$filter_name] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func($callback, $value);
            }
        }

        return $value;
    }

    public static function resetFilters() {
        self::$filters = [];
    }
}

if (!function_exists('add_filter')) {
    function add_filter($filter_name, $callback, $priority = 10) {
        FAMock::add_filter($filter_name, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($filter_name, $value) {
        return FAMock::apply_filters($filter_name, $value);
    }
}