<?php

/**
 * FAMock - FrontAccounting Function Mocks for Testing
 *
 * This file provides mock implementations of FrontAccounting functions
 * that are used in the Product Attributes module but not available
 * during unit testing.
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
if (!function_exists('add_access_extensions')) {
    function add_access_extensions() {
        // Mock - do nothing in tests
    }
}

// Session/Company Functions
if (!function_exists('get_company_pref')) {
    function get_company_pref($name) {
        // Mock - return default values
        return null;
    }
}

// Database Constants
if (!defined('TB_PREF')) {
    define('TB_PREF', 'fa_');
}