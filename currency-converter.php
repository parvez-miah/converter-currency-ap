<?php
/*
Plugin Name: Ajker Takar Rate
Description: A simple currency converter that fetches data from Google Finance and caches it.
Version: 11.0.9
Author: Ajker Takar Rate
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once(plugin_dir_path(__FILE__) . 'currency-table.php');
include_once(plugin_dir_path(__FILE__) . 'side-currency-converter.php');
include_once(plugin_dir_path(__FILE__) . 'currency-names.php');
include_once(plugin_dir_path(__FILE__) . 'currency-table-names.php');
include_once(plugin_dir_path(__FILE__) . 'currency-graph.php');

// Enqueue necessary scripts and styles
function cc_enqueue_scripts() {
    wp_enqueue_style('cc-styles', plugins_url('css/style.css', __FILE__));
    if (is_singular()) {
        wp_enqueue_style('cc-print-styles', plugins_url('css/print.css', __FILE__), array(), null, 'print');
    }
    wp_enqueue_script('cc-scripts-main', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
    wp_enqueue_script('cc-currency-table', plugins_url('js/currency-table.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('cc-currency-table', 'ccAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'cc_enqueue_scripts');

function load_amcharts() {
    wp_enqueue_script('amcharts-core', 'https://cdn.amcharts.com/lib/4/core.js', array(), null, true);
    wp_enqueue_script('amcharts-charts', 'https://cdn.amcharts.com/lib/4/charts.js', array(), null, true);
    wp_enqueue_script('amcharts-animated', 'https://cdn.amcharts.com/lib/4/themes/animated.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'load_amcharts');

// Fetch and cache conversion rate
function get_conversion_rate($from_currency, $to_currency) {
    $cache_key = 'cc_' . $from_currency . '_' . $to_currency;
    $rate = get_transient($cache_key);

    if ($rate === false) {
        $response = wp_remote_get("https://www.google.com/finance/quote/{$from_currency}-{$to_currency}");
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            preg_match('/<div class="YMlKec fxKbKc">([0-9.]+)</', $body, $matches);
            if (isset($matches[1])) {
                $rate = floatval($matches[1]);
                set_transient($cache_key, $rate, 12 * HOUR_IN_SECONDS); // Cache for 12 hours
            }
        }
    }

    return $rate;
}

// Background Process to Update Cache
function cc_update_cache() {
    $currencies = array('USD' => 'BDT'); // Add more currency pairs as needed
    foreach ($currencies as $from => $to) {
        get_conversion_rate($from, $to); // This function updates the cache
    }
}
add_action('wp_scheduled_cache_update', 'cc_update_cache');

if (!wp_next_scheduled('wp_scheduled_cache_update')) {
    wp_schedule_event(time(), 'hourly', 'wp_scheduled_cache_update');
}

// Handle the AJAX Request
function cc_convert_currency() {
    $from_currency = sanitize_text_field($_POST['from_currency']);
    $to_currency = sanitize_text_field($_POST['to_currency']);
    $amount = floatval($_POST['amount']);
    $preview_rate = floatval($_POST['preview_rate']);

    if ($from_currency === $to_currency) {
        wp_send_json_error('একই মুদ্রার নাম ব্যবহার করা হয়েছে! পরিবর্তন করুন...');
    }

    $rate = get_conversion_rate($from_currency, $to_currency);

    if ($rate === false) {
        wp_send_json_error('কিছু একটা সমস্যা হয়েছে। একটু অপেক্ষা করুন, আপডেট হয়ে যাবে...');
    }

    $adjusted_rate = $rate * (1 + $preview_rate / 100);
    $converted_amount = $amount * $adjusted_rate;
    $bank_rate = $adjusted_rate;
    $exchange_rate = $bank_rate * 1.02;

    $reverse_rate = 1 / $rate;
    $reverse_converted_amount = $amount * $reverse_rate;

    $historical_rates = [
        '7days' => $rate * 0.98, 
        '15days' => $rate * 0.97,
        '30days' => $rate * 0.95
    ];

    $currency_names = cc_get_currency_names();

    wp_send_json_success(array(
        'rate' => $rate,
        'adjusted_rate' => $adjusted_rate,
        'converted_amount' => $converted_amount,
        'bank_rate' => $bank_rate,
        'exchange_rate' => $exchange_rate,
        'from_currency_name' => $currency_names[$from_currency],
        'to_currency_name' => $currency_names[$to_currency],
        'reverse_rate' => $reverse_rate,
        'reverse_converted_amount' => $reverse_converted_amount,
        'historical_rates' => $historical_rates
    ));
}

add_action('wp_ajax_cc_convert_currency', 'cc_convert_currency');
add_action('wp_ajax_nopriv_cc_convert_currency', 'cc_convert_currency');

// Add available currencies (example: dynamic dropdown)
function cc_get_currency_names() {
    $cache_key = 'cc_currency_names';
    $currency_names = get_transient($cache_key);

    if ($currency_names === false) {
        $currency_names = get_currency_names(); // Assuming this function returns an array of currency names
        set_transient($cache_key, $currency_names, 31536000); // Cache for 1 year (31536000 seconds)
    }

    return $currency_names;
}

function cc_currency_options($selected = '') {
    $currencies = cc_get_currency_names();
    foreach ($currencies as $code => $name) {
        echo '<option value="' . $code . '"' . selected($selected, $code, false) . '>' . $name . '</option>';
    }
}

// Create the Currency Converter Shortcode
function cc_register_shortcodes_menu() {
    add_menu_page(
        'Takar Rate',
        'Takar Rate',
        'manage_options',
        'cc-shortcodes',
        'cc_display_shortcodes_page',
        'dashicons-money-alt', // Change the icon here
        20
    );

    // Add Clear Cache submenu
    add_submenu_page(
        'cc-shortcodes',
        'Clear Cache',
        'Clear Cache',
        'manage_options',
        'cc-clear-cache',
        'cc_clear_cache_page'
    );

}
add_action('admin_menu', 'cc_register_shortcodes_menu');

function cc_display_shortcodes_page() {
    ?>
    <div class="wrap">
        <h1>Shortcodes</h1>
        <p>Use the following shortcodes to embed the currency converter:</p>
        <code>[currency_converter]</code>
        <code>[currency_converter from="BDT" to="USD"]</code>
        <code>[currency_table]</code>
    </div>
    <?php
}

function cc_clear_cache_page() {
    ?>
    <div class="wrap">
        <h1>Clear Cache</h1>
        <p>Click the button below to clear the cache for the Ajker Takar Rate plugin:</p>
        <button id="cc-clear-cache-button" class="button button-primary">Clear Cache</button>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#cc-clear-cache-button').on('click', function() {
            $.post(ajaxurl, { action: 'cc_clear_cache' }, function(response) {
                if (response.success) {
                    alert('Cache cleared successfully!');
                } else {
                    alert('Failed to clear cache.');
                }
            });
        });
    });
    </script>
    <?php
}


function cc_clear_cache() {
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_cc_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_cc_%'");
    wp_send_json_success();
}

add_action('wp_ajax_cc_clear_cache', 'cc_clear_cache');
add_action('wp_ajax_cc_clear_currency_table_cache', 'cc_clear_currency_table_cache');
add_action('wp_ajax_cc_clear_all_table_data', 'cc_clear_all_table_data');
?>
