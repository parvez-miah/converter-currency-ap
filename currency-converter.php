<?php
/*
Plugin Name: My Unique Currency Converter
Description: A simple currency converter that fetches data from Google Finance and caches it.
Version: 1111112.0
Author: Your Unique Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue necessary scripts and styles
function cc_enqueue_scripts() {
    wp_enqueue_style('cc-styles', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('cc-scripts', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('cc-scripts', 'ccAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'cc_enqueue_scripts');

// Create the Currency Converter Shortcode
function cc_currency_converter($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'BDT'
    ), $atts);

    ob_start();
    ?>
    <div class="cc-container">
        <input type="number" id="cc-amount" value="1" min="1" />
        <select id="cc-from-currency">
            <?php cc_currency_options($atts['from']); ?>
        </select>
        <select id="cc-to-currency">
            <?php cc_currency_options($atts['to']); ?>
        </select>
        <button id="cc-convert">Convert</button>
        <div id="cc-result"></div>
        <table id="cc-rate-table">
            <thead>
                <tr>
                    <th>পরিমাণ</th>
                    <th>ব্যাংক রেট</th>
                    <th>এক্সচেঞ্জ রেট</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_converter', 'cc_currency_converter');

// Handle the AJAX Request
function cc_convert_currency() {
    $from_currency = sanitize_text_field($_POST['from_currency']);
    $to_currency = sanitize_text_field($_POST['to_currency']);
    $amount = floatval($_POST['amount']);
    $cache_key = 'cc_' . $from_currency . '_' . $to_currency;
    $rate = get_transient($cache_key);

    if ($rate === false) {
        $response = wp_remote_get("https://www.google.com/finance/quote/{$from_currency}-{$to_currency}");
        if (is_wp_error($response)) {
            wp_send_json_error('Unable to fetch conversion rate');
        }
        $body = wp_remote_retrieve_body($response);
        preg_match('/<div class="YMlKec fxKbKc">([0-9.]+)</', $body, $matches);
        if (isset($matches[1])) {
            $rate = floatval($matches[1]);
            set_transient($cache_key, $rate, 15 * MINUTE_IN_SECONDS);
        } else {
            wp_send_json_error('Conversion rate not found');
        }
    }

    $converted_amount = $amount * $rate;
    $bank_rate = $rate;
    $exchange_rate = $bank_rate * 1.02;

    wp_send_json_success(array(
        'rate' => $rate,
        'converted_amount' => $converted_amount,
        'bank_rate' => $bank_rate,
        'exchange_rate' => $exchange_rate
    ));
}
add_action('wp_ajax_cc_convert_currency', 'cc_convert_currency');
add_action('wp_ajax_nopriv_cc_convert_currency', 'cc_convert_currency');

// Create JavaScript File
function cc_create_js_file() {
    $js = <<<JS
jQuery(document).ready(function($) {
    function updateConversion() {
        var fromCurrency = $('#cc-from-currency').val();
        var toCurrency = $('#cc-to-currency').val();
        var amount = $('#cc-amount').val();

        $.ajax({
            url: ccAjax.ajax_url,
            type: 'post',
            data: {
                action: 'cc_convert_currency',
                from_currency: fromCurrency,
                to_currency: toCurrency,
                amount: amount
            },
            success: function(response) {
                if (response.success) {
                    var bankRates = '';
                    var exchangeRates = '';
                    var quantities = [1, 5, 20, 50, 100];

                    quantities.forEach(function(quantity) {
                        bankRates += '<tr><td>' + quantity + '</td><td>' + (response.data.bank_rate * quantity).toFixed(2) + '</td><td>' + (response.data.exchange_rate * quantity).toFixed(2) + '</td></tr>';
                    });

                    $('#cc-result').html(fromCurrency + ' ১ টাকা ' + toCurrency + ' ' + response.data.converted_amount.toFixed(2) + ' টাকা');
                    $('#cc-rate-table tbody').html(bankRates);
                } else {
                    $('#cc-result').html('Error: ' + response.data);
                }
            },
            error: function() {
                $('#cc-result').html('An error occurred');
            }
        });
    }

    $('#cc-amount, #cc-from-currency, #cc-to-currency').change(updateConversion);
    $('#cc-convert').click(updateConversion);

    // Initial conversion on load
    updateConversion();
});
JS;

    file_put_contents(plugin_dir_path(__FILE__) . 'js/script.js', $js);
}
register_activation_hook(__FILE__, 'cc_create_js_file');

// Create CSS File
function cc_create_css_file() {
    $css = <<<CSS
.cc-container {
    margin: 20px;
}
#cc-result {
    margin-top: 10px;
}
#cc-rate-table {
    margin-top: 20px;
    width: 100%;
    border-collapse: collapse;
}
#cc-rate-table th, #cc-rate-table td {
    border: 1px solid #ddd;
    padding: 8px;
}
#cc-rate-table th {
    background-color: #f2f2f2;
}
CSS;

    file_put_contents(plugin_dir_path(__FILE__) . 'css/style.css', $css);
}
register_activation_hook(__FILE__, 'cc_create_css_file');

// Add available currencies (example: dynamic dropdown)
function cc_get_currencies() {
    return [
        'USD' => 'USD',
        'BDT' => 'BDT',
        // Add other currencies here
    ];
}

function cc_currency_options($selected = '') {
    $currencies = cc_get_currencies();
    foreach ($currencies as $code => $name) {
        echo '<option value="' . $code . '"' . selected($selected, $code, false) . '>' . $name . '</option>';
    }
}
