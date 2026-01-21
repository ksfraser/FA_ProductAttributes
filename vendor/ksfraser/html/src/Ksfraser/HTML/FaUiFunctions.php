<?php

namespace Ksfraser\HTML;

if (!defined('FA_ROOT')) {
    define('FA_ROOT', realpath(__DIR__ . '/../../../..'));
}

// Load FA UI functions in global namespace
require_once(FA_ROOT . "/includes/ui/ui_input.inc");
require_once(FA_ROOT . "/includes/ui/ui_lists.inc");
require_once(FA_ROOT . "/includes/ui/ui_controls.inc");

/**
 * Facade for Front Accounting UI functions
 * This allows us to decouple our HTML components from FA's UI functions
 */
class FaUiFunctions {
    const TABLESTYLE2 = 2; // Matching FA's constant

    public static function label_row($label, $content, $params="")
    {
        // Check for function in global namespace
        if (function_exists('\\label_row')) {
            call_user_func('\\label_row', $label, $content, $params);
        } else {
            echo "<tr><td class='label'>$label</td><td $params>$content</td></tr>";
        }
    }

    public static function start_table($type = self::TABLESTYLE2, $params="")
    {
        if (function_exists('\\start_table')) {
            call_user_func('\\start_table', $type, $params);
        } else {
            echo "<table class='tablestyle$type' $params>\n";
        }
    }

    public static function end_table($breaks=0)
    {
        if (function_exists('\\end_table')) {
            call_user_func('\\end_table', $breaks);
        } else {
            echo "</table>\n";
        }
    }

    public static function table_header($labels, $params='')
    {
        if (function_exists('\\table_header')) {
            call_user_func('\\table_header', $labels, $params);
        } else {
            echo "<tr>";
            foreach ($labels as $label) {
                echo "<th $params>$label</th>";
            }
            echo "</tr>\n";
        }
    }

    public static function start_row($param="")
    {
        if (function_exists('\\start_row')) {
            call_user_func('\\start_row', $param);
        } else {
            echo "<tr $param>\n";
        }
    }

    public static function end_row()
    {
        if (function_exists('\\end_row')) {
            call_user_func('\\end_row');
        } else {
            echo "</tr>\n";
        }
    }

    public static function label_cells($label, $value, $params="", $params2="", $id=null)
    {
        if (function_exists('\\label_cells')) {
            call_user_func('\\label_cells', $label, $value, $params, $params2, $id);
        } else {
            // OOP implementation
            if ($label != null) {
                $labelCell = new \Ksfraser\HTML\Elements\FaCell($label, $params);
                $labelCell->toHtml();
            }
            
            // Handle Ajax updates if $id is set (simplified for now)
            $valueParams = $params2;
            if (isset($id)) {
                $valueParams .= " id='$id'";
            }
            
            $valueCell = new \Ksfraser\HTML\Elements\FaCell($value, $valueParams);
            $valueCell->toHtml();
        }
    }

    public static function label_cell($label, $params="", $id=null)
    {
        if (function_exists('\\label_cell')) {
            call_user_func('\\label_cell', $label, $params, $id);
        } else {
            // OOP implementation
            $cellParams = $params;
            if (isset($id)) {
                $cellParams .= " id='$id'";
                // TODO: Add Ajax update functionality
            }
            $cell = new \Ksfraser\HTML\Elements\FaCell($label, $cellParams);
            $cell->toHtml();
        }
    }

    public static function number_list_cells($label, $name, $selected, $from, $to, $no_option=false)
    {
        if (function_exists('\\number_list_cells')) {
            call_user_func('\\number_list_cells', $label, $name, $selected, $from, $to, $no_option);
        } else {
            // OOP implementation
            if ($label != null) {
                self::label_cell($label);
            }
            // Get the number list HTML
            $numberListHtml = '';
            if (function_exists('\\number_list')) {
                $numberListHtml = call_user_func('\\number_list', $name, $selected, $from, $to, $no_option);
            } else {
                // Fallback: simple select
                $numberListHtml = "<select name='$name'>";
                if ($no_option) {
                    $numberListHtml .= "<option value=''>--</option>";
                }
                for ($i = $from; $i <= $to; $i++) {
                    $selectedAttr = ($selected == $i) ? ' selected' : '';
                    $numberListHtml .= "<option value='$i'$selectedAttr>$i</option>";
                }
                $numberListHtml .= "</select>";
            }
            $cell = new \Ksfraser\HTML\Elements\FaCell($numberListHtml);
            $cell->toHtml();
        }
    }

    public static function check_cells($label, $name, $value=null, $submit_on_change=false, $title=false, $params='')
    {
        if (function_exists('\\check_cells')) {
            call_user_func('\\check_cells', $label, $name, $value, $submit_on_change, $title, $params);
        } else {
            // OOP implementation
            if ($label != null) {
                self::label_cell($label);
            }
            // Get the check HTML
            $checkHtml = '';
            if (function_exists('\\check')) {
                $checkHtml = call_user_func('\\check', null, $name, $value, $submit_on_change, $title);
            } else {
                // Fallback: simple checkbox
                $checked = $value ? ' checked' : '';
                $checkHtml = "<input type='checkbox' name='$name'$checked>";
            }
            $cell = new \Ksfraser\HTML\Elements\FaCell($checkHtml, $params);
            $cell->toHtml();
        }
    }

    public static function submit_cells($name, $value, $extra="", $title=false, $async=false)
    {
        if (function_exists('\\submit_cells')) {
            call_user_func('\\submit_cells', $name, $value, $extra, $title, $async);
        } else {
            // OOP implementation
            // Get the submit HTML
            $submitHtml = '';
            if (function_exists('\\submit')) {
                ob_start();
                call_user_func('\\submit', $name, $value, true, $title, $async);
                $submitHtml = ob_get_clean();
            } else {
                // Fallback: simple button
                $submitHtml = "<input type='submit' name='$name' value='$value'>";
            }
            $cell = new \Ksfraser\HTML\Elements\FaCell($submitHtml, $extra);
            $cell->toHtml();
        }
    }

    public static function text_cells($label, $name, $value=null, $size="", $max="", $title=false, $labparams="", $post_label="", $inparams="")
    {
        if (function_exists('\\text_cells')) {
            call_user_func('\\text_cells', $label, $name, $value, $size, $max, $title, $labparams, $post_label, $inparams);
        } else {
            // OOP implementation
            if (function_exists('\\default_focus')) {
                call_user_func('\\default_focus', $name);
            }
            if ($label != null) {
                self::label_cell($label, $labparams);
            }
            // Get the text input HTML
            $textHtml = '';
            if (function_exists('\\text_input')) {
                $textHtml = call_user_func('\\text_input', $name, $value, $size, $max, $title, $inparams);
            } else {
                // Fallback: simple input
                $textHtml = "<input type='text' name='$name' value='$value' size='$size' maxlength='$max'>";
            }
            if ($post_label != "") {
                $textHtml .= " " . $post_label;
            }
            $cell = new \Ksfraser\HTML\Elements\FaCell($textHtml);
            $cell->toHtml();
            
            // Add Ajax update functionality
            if (isset($GLOBALS['Ajax'])) {
                $GLOBALS['Ajax']->addUpdate($name, $name, $value);
            }
        }
    }

    public static function file_cells($label, $name, $id="")
    {
        if (function_exists('\\file_cells')) {
            call_user_func('\\file_cells', $label, $name, $id);
        } else {
            // OOP implementation
            $fileHtml = "<input type='file' name='$name'";
            if ($id != "") {
                $fileHtml .= " id='$id'";
            }
            $fileHtml .= ">";
            self::label_cells($label, $fileHtml);
        }
    }

    public static function amount_cells_ex($label, $name, $size, $max=null, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (function_exists('\\amount_cells_ex')) {
            call_user_func('\\amount_cells_ex', $label, $name, $size, $max, $init, $params, $post_label, $dec);
        } else {
            // OOP implementation
            if (!isset($dec)) {
                if (function_exists('\\user_price_dec')) {
                    $dec = call_user_func('\\user_price_dec');
                } else {
                    $dec = 2; // fallback
                }
            }
            if (!isset($_POST[$name]) || $_POST[$name] == "") {
                if ($init !== null) {
                    $_POST[$name] = $init;
                } else {
                    $_POST[$name] = '';
                }
            }
            if ($label != null) {
                if ($params == null) {
                    $params = "class='label'";
                }
                self::label_cell($label, $params);
            }
            if (!isset($max)) {
                $max = $size;
            }
            $inputHtml = "<input class='amount' type='text' name='$name' size='$size' maxlength='$max' dec='$dec' value='" . $_POST[$name] . "'>";
            if ($post_label) {
                $inputHtml .= "<span id='_{$name}_label'> $post_label</span>";
            }
            $cellParams = ($label != null) ? "" : "align='right'";
            $cell = new \Ksfraser\HTML\Elements\FaCell($inputHtml, $cellParams);
            $cell->toHtml();
            
            // Add Ajax update functionality
            if (isset($GLOBALS['Ajax'])) {
                if ($post_label) {
                    $GLOBALS['Ajax']->addUpdate($name, '_'.$name.'_label', $post_label);
                }
                $GLOBALS['Ajax']->addUpdate($name, $name, $_POST[$name]);
                $GLOBALS['Ajax']->addAssign($name, $name, 'dec', $dec);
            }
        }
    }

    public static function amount_cells($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        self::amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec);
    }

    public static function unit_amount_cells($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (!isset($dec)) {
            if (function_exists('\\user_price_dec')) {
                $dec = call_user_func('\\user_price_dec') + 2;
            } else {
                $dec = 4; // fallback
            }
        }
        self::amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec + 2);
    }

    public static function text_row($label, $name, $value, $size, $max, $title=null, $params="", $post_label="")
    {
        if (function_exists('\\text_row')) {
            call_user_func('\\text_row', $label, $name, $value, $size, $max, $title, $params, $post_label);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            self::text_cells(null, $name, $value, $size, $max, $title, $params, $post_label);
            self::end_row();
        }
    }

    public static function amount_row($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (function_exists('\\amount_row')) {
            call_user_func('\\amount_row', $label, $name, $init, $params, $post_label, $dec);
        } else {
            // OOP implementation
            self::start_row();
            self::amount_cells($label, $name, $init, $params, $post_label, $dec);
            self::end_row();
        }
    }

    public static function date_cells($label, $name, $title = null, $check=null, $inc_days=0, $inc_months=0, $inc_years=0, $params=null, $submit_on_change=false)
    {
        if (function_exists('\\date_cells')) {
            call_user_func('\\date_cells', $label, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
        } else {
            // OOP implementation - simplified fallback
            if (!isset($_POST[$name]) || $_POST[$name] == "") {
                if ($inc_years == 1001) {
                    $_POST[$name] = null;
                } else {
                    // Simplified date calculation
                    $dd = date('Y-m-d');
                    if ($inc_days != 0) {
                        $dd = date('Y-m-d', strtotime("$dd + $inc_days days"));
                    }
                    if ($inc_months != 0) {
                        $dd = date('Y-m-d', strtotime("$dd + $inc_months months"));
                    }
                    if ($inc_years != 0) {
                        $dd = date('Y-m-d', strtotime("$dd + $inc_years years"));
                    }
                    $_POST[$name] = $dd;
                }
            }

            $post_label = ""; // Simplified, no date picker in fallback

            if ($label != null) {
                self::label_cell($label, $params);
            }

            $class = $submit_on_change ? 'date active' : 'date';
            $aspect = $check ? 'aspect="cdate"' : '';
            if ($check && ($_POST[$name] != date('Y-m-d'))) {
                $aspect .= ' style="color:#FF0000"';
            }

            if (function_exists('\\default_focus')) {
                call_user_func('\\default_focus', $name);
            }

            $size = 10; // Simplified
            $inputHtml = "<input type='text' name='$name' class='$class' $aspect size='$size' maxlength='12' value='" . $_POST[$name] . "'" . ($title ? " title='$title'" : '') . "> $post_label";

            $cell = new \Ksfraser\HTML\Elements\FaCell($inputHtml);
            $cell->toHtml();
            
            // Add Ajax update functionality
            if (isset($GLOBALS['Ajax'])) {
                $GLOBALS['Ajax']->addUpdate($name, $name, $_POST[$name]);
            }
        }
    }

    public static function date_row($label, $name, $title=null, $check=null, $inc_days=0, $inc_months=0, $inc_years=0, $params=null, $submit_on_change=false)
    {
        if (function_exists('\\date_row')) {
            call_user_func('\\date_row', $label, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            self::date_cells(null, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
            self::end_row();
        }
    }

    public static function email_row($label, $name, $value, $size, $max, $title=null, $params="", $post_label="")
    {
        if (function_exists('\\email_row')) {
            call_user_func('\\email_row', $label, $name, $value, $size, $max, $title, $params, $post_label);
        } else {
            // OOP implementation
            $display_label = $label;
            if (!empty($_POST[$name])) {
                $display_label = "<a href='Mailto:".$_POST[$name]."'>$label</a>";
            }
            self::text_row($display_label, $name, $value, $size, $max, $title, $params, $post_label);
        }
    }

    public static function link_row($label, $name, $value, $size, $max, $title=null, $params="", $post_label="")
    {
        if (function_exists('\\link_row')) {
            call_user_func('\\link_row', $label, $name, $value, $size, $max, $title, $params, $post_label);
        } else {
            // OOP implementation
            $display_label = $label;
            $val = $_POST[$name] ?? '';
            if ($val) {
                if (strpos($val,'http://')===false) {
                    $val = 'http://'.$val;
                }
                $display_label = "<a href='$val' target='_blank'>$label</a>";
            }
            self::text_row($display_label, $name, $value, $size, $max, $title, $params, $post_label);
        }
    }

    public static function password_row($label, $name, $value)
    {
        if (function_exists('\\password_row')) {
            call_user_func('\\password_row', $label, $name, $value);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            $inputHtml = "<input type='password' name='$name' size='32' maxlength='32' value='$value'>";
            $cell = new \Ksfraser\HTML\Elements\FaCell($inputHtml);
            $cell->toHtml();
            self::end_row();
        }
    }

    public static function file_row($label, $name, $id = "")
    {
        if (function_exists('\\file_row')) {
            call_user_func('\\file_row', $label, $name, $id);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            self::file_cells(null, $name, $id);
            self::end_row();
        }
    }

    public static function ref_cells($label, $name, $title=null, $init=null, $params=null, $submit_on_change=false, $type=null, $context=null)
    {
        if (function_exists('\\ref_cells')) {
            call_user_func('\\ref_cells', $label, $name, $title, $init, $params, $submit_on_change, $type, $context);
        } else {
            // OOP implementation - simplified fallback
            if (!isset($_POST[$name]) || $_POST[$name] == "") {
                $_POST[$name] = $init ?? '';
            }

            if (isset($label)) {
                self::label_cell($label, $params);
            }

            $inputHtml = "<input name='$name' value='" . $_POST[$name] . "' size='16' maxlength='35'>";
            $cell = new \Ksfraser\HTML\Elements\FaCell($inputHtml);
            $cell->toHtml();
            // TODO: Add Ajax updates and reference line handling
        }
    }

    public static function small_amount_cells($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (function_exists('\\small_amount_cells')) {
            call_user_func('\\small_amount_cells', $label, $name, $init, $params, $post_label, $dec);
        } else {
            // OOP implementation
            self::amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec);
        }
    }

    public static function small_amount_row($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (function_exists('\\small_amount_row')) {
            call_user_func('\\small_amount_row', $label, $name, $init, $params, $post_label, $dec);
        } else {
            // OOP implementation
            self::start_row();
            self::small_amount_cells($label, $name, $init, $params, $post_label, $dec);
            self::end_row();
        }
    }

    public static function ref_row($label, $name, $title=null, $init=null, $submit_on_change=false, $type=null, $context = null)
    {
        if (function_exists('\\ref_row')) {
            call_user_func('\\ref_row', $label, $name, $title, $init, $submit_on_change, $type, $context);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            self::ref_cells(null, $name, $title, $init, null, $submit_on_change, $type, $context);
            self::end_row();
        }
    }

    public static function percent_row($label, $name, $init=null)
    {
        if (function_exists('\\percent_row')) {
            call_user_func('\\percent_row', $label, $name, $init);
        } else {
            // OOP implementation
            if (!isset($_POST[$name]) || $_POST[$name] == "") {
                $_POST[$name] = $init == null ? '' : $init;
            }
            // Use small_amount_row with % post_label, simplified dec
            self::small_amount_row($label, $name, $_POST[$name], null, "%", 2); // Simplified dec
        }
    }

    public static function qty_row($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (function_exists('\\qty_row')) {
            call_user_func('\\qty_row', $label, $name, $init, $params, $post_label, $dec);
        } else {
            // OOP implementation
            $dec = $dec ?? 2; // Simplified default
            self::start_row();
            self::amount_cells($label, $name, $init, $params, $post_label, $dec);
            self::end_row();
        }
    }

    public static function small_qty_row($label, $name, $init=null, $params=null, $post_label=null, $dec=null)
    {
        if (function_exists('\\small_qty_row')) {
            call_user_func('\\small_qty_row', $label, $name, $init, $params, $post_label, $dec);
        } else {
            // OOP implementation
            $dec = $dec ?? 2; // Simplified default
            self::start_row();
            self::small_amount_cells($label, $name, $init, $params, $post_label, $dec);
            self::end_row();
        }
    }

    public static function combo_list_cells($sql, $order_by_field, $label, $name, $selected_id = null, $none_option=false, $submit_on_change=false)
    {
        if (function_exists('\\combo_list_cells')) {
            call_user_func('\\combo_list_cells', $sql, $order_by_field, $label, $name, $selected_id, $none_option, $submit_on_change);
        } else {
            // OOP implementation - execute SQL to get options
            if ($label != null) {
                self::label_cell($label);
            }

            // Create select element
            $selectHtml = "<select name='$name'";
            if ($submit_on_change) {
                $selectHtml .= " onchange='this.form.submit()'";
            }
            $selectHtml .= ">";

            if ($none_option) {
                $selectHtml .= "<option value=''>-- " . ($none_option === true ? 'select' : $none_option) . " --</option>";
            }

            // Try to execute SQL and generate options
            if (function_exists('\\db_query') && function_exists('\\db_fetch_row')) {
                $result = call_user_func('\\db_query', $sql);
                if ($result) {
                    while ($row = call_user_func('\\db_fetch_row', $result)) {
                        $value = $row[0];
                        $display = isset($row[1]) ? $row[1] : $row[0];
                        $selected = ($selected_id !== null && $value == $selected_id) ? ' selected' : '';
                        $selectHtml .= "<option value='$value'$selected>$display</option>";
                    }
                } else {
                    // Fallback if query fails
                    $selectHtml .= "<option value=''>-- SQL Error --</option>";
                }
            } else {
                // Fallback when database functions not available
                $selectHtml .= "<option value=''>-- Database not available --</option>";
            }

            $selectHtml .= "</select>";

            $cell = new \Ksfraser\HTML\Elements\FaCell($selectHtml);
            $cell->toHtml();
        }
    }

    public static function textarea_cells($label, $name, $value, $cols, $rows, $max=255, $title = null, $params="")
    {
        if (function_exists('\\textarea_cells')) {
            call_user_func('\\textarea_cells', $label, $name, $value, $cols, $rows, $max, $title, $params);
        } else {
            // OOP implementation
            if (function_exists('\\default_focus')) {
                call_user_func('\\default_focus', $name);
            }

            if ($label != null) {
                $labelCell = new \Ksfraser\HTML\Elements\FaCell($label, $params);
                $labelCell->toHtml();
            }

            $value = $value ?? ($_POST[$name] ?? "");
            $textareaHtml = "<textarea name='$name' cols='$cols' rows='$rows'"
                . ($title ? " title='$title'" : '')
                . ($max ? " maxlength='$max'" : '')
                . ">$value</textarea>";

            $cell = new \Ksfraser\HTML\Elements\FaCell($textareaHtml);
            $cell->toHtml();
            
            // Add Ajax update functionality
            if (isset($GLOBALS['Ajax'])) {
                $GLOBALS['Ajax']->addUpdate($name, $name, $value);
            }
        }
    }

    public static function textarea_row($label, $name, $value, $cols, $rows, $max=255, $title=null, $params="")
    {
        if (function_exists('\\textarea_row')) {
            call_user_func('\\textarea_row', $label, $name, $value, $cols, $rows, $max, $title, $params);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            self::textarea_cells(null, $name, $value, $cols, $rows, $max, $title, $params);
            self::end_row();
        }
    }

    public static function check_row($label, $name, $value=null, $submit_on_change=false, $title=false)
    {
        if (function_exists('\\check_row')) {
            call_user_func('\\check_row', $label, $name, $value, $submit_on_change, $title);
        } else {
            // OOP implementation
            self::start_row();
            self::label_cell($label, "class='label'");
            self::check_cells(null, $name, $value, $submit_on_change, $title);
            self::end_row();
        }
    }

    public static function submit_row($name, $value, $right=true, $extra="", $title=false, $async=false)
    {
        if (function_exists('\\submit_row')) {
            call_user_func('\\submit_row', $name, $value, $right, $extra, $title, $async);
        } else {
            // OOP implementation
            self::start_row();
            if ($right) {
                self::label_cell("&nbsp;");
            }
            self::submit_cells($name, $value, $extra, $title, $async);
            self::end_row();
        }
    }

    public static function start_outer_table($class=false, $extra="", $padding='2', $spacing='0', $br=false)
    {
        if (function_exists('\\start_outer_table')) {
            call_user_func('\\start_outer_table', $class, $extra, $padding, $spacing, $br);
        } else {
            // OOP implementation
            if ($br) {
                echo "<br>";
            }
            self::start_table($class, $extra);
            echo "<tr valign='top'><td>\n"; // outer table
        }
    }

    public static function table_section($number=1, $width=false)
    {
        if (function_exists('\\table_section')) {
            call_user_func('\\table_section', $number, $width);
        } else {
            // OOP implementation
            if ($number > 1) {
                echo "</table>\n";
                // output_hidden() - simplified, not implemented
                $widthAttr = ($width ? "width='$width'" : "");
                echo "</td><td style='border-left:1px solid #cccccc;' $widthAttr>\n"; // outer table
            }
            echo "<table class='tablestyle_inner'>\n";
        }
    }

    public static function end_outer_table($breaks=0, $close_table=true)
    {
        if (function_exists('\\end_outer_table')) {
            call_user_func('\\end_outer_table', $breaks, $close_table);
        } else {
            // OOP implementation
            if ($close_table) {
                echo "</table>\n";
                // output_hidden() - simplified, not implemented
            }
            echo "</td></tr>\n";
            self::end_table($breaks);
        }
    }

    public static function alt_table_row_color(&$k, $extra_class=null)
    {
        if (function_exists('\\alt_table_row_color')) {
            call_user_func('\\alt_table_row_color', $k, $extra_class);
        } else {
            // OOP implementation
            $classes = $extra_class ? array($extra_class) : array();
            if ($k == 1) {
                array_push($classes, 'oddrow');
                $k = 0;
            } else {
                array_push($classes, 'evenrow');
                $k++;
            }
            echo "<tr class='" . implode(' ', $classes) . "'>\n";
        }
    }

    public static function table_section_title($msg, $colspan=2)
    {
        if (function_exists('\\table_section_title')) {
            call_user_func('\\table_section_title', $msg, $colspan);
        } else {
            // OOP implementation
            echo "<tr><td colspan='$colspan' class='tableheader'>$msg</td></tr>\n";
        }
    }
}