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
    wp_enqueue_style('cc-print-styles', plugins_url('css/print.css', __FILE__), array(), null, 'print');
    wp_enqueue_script('cc-scripts', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('cc-scripts', 'ccAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'cc_enqueue_scripts');

// Fetch and cache conversion rate
function get_conversion_rate($from_currency, $to_currency) {
    $cache_key = 'cc_' . $from_currency . '_' . $to_currency;
    $rate = get_transient($cache_key);

    if ($rate === false) {
        // If cache is not found, fetch the rate
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
        $rate = get_conversion_rate($from, $to);
        // The get_conversion_rate function already handles updating the cache
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
        wp_send_json_error('টাকার রেট আপডেট হয়েছে নিচে দেখুন।');
    }

    $rate = get_conversion_rate($from_currency, $to_currency);

    if ($rate === false) {
        wp_send_json_error('Conversion rate not found in cache');
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

    $currency_names = [
        'USD' => 'আমেরিকান ডলার',
'BDT' => 'বাংলাদেশি টাকা',
'EUR' => 'ইউরো',
'JPY' => 'জাপানি ইয়েন',
'GBP' => 'ব্রিটিশ পাউন্ড',
'AUD' => 'অস্ট্রেলিয়ান ডলার',
'CAD' => 'কানাডিয়ান ডলার',
'CHF' => 'সুইস ফ্রাঁ',
'CNY' => 'চীনা ইউয়ান',
'SEK' => 'সুইডিশ ক্রোনা',
'NZD' => 'নিউজিল্যান্ড ডলার',
'MXN' => 'মেক্সিকান পেসো',
'SGD' => 'সিঙ্গাপুর ডলার',
'HKD' => 'হংকং ডলার',
'NOK' => 'নরওয়েজিয়ান ক্রোন',
'KRW' => 'দক্ষিণ কোরিয়ান ওন',
'TRY' => 'তুর্কি লিরা',
'RUB' => 'রাশিয়ান রুবল',
'INR' => 'ভারতীয় রুপি',
'BRL' => 'ব্রাজিলিয়ান রিয়াল',
'ZAR' => 'দক্ষিণ আফ্রিকান র্যান্ড',
'DKK' => 'ড্যানিশ ক্রোন',
'PLN' => 'পোলিশ জ্লোটি',
'TWD' => 'তাইওয়ান ডলার',
'THB' => 'থাই ভাট',
'MYR' => 'মালয়েশিয়ান রিঙ্গিত',
'IDR' => 'ইন্দোনেশিয়ান রুপিয়াহ',
'CZK' => 'চেক ক্রোনা',
'HUF' => 'হাঙ্গেরিয়ান ফোরিন্ট',
'ILS' => 'ইসরায়েলি শেকেল',
'CLP' => 'চিলিয়ান পেসো',
'PHP' => 'ফিলিপাইন পেসো',
'PKR' => 'পাকিস্তানি রুপি',
'EGP' => 'মিশরীয় পাউন্ড',
'NGN' => 'নাইজেরিয়ান নাইরা',
'VND' => 'ভিয়েতনামি ডং',
'KZT' => 'কাজাখস্তানি তেঙ্গে',
'PEN' => 'পেরুভিয়ান সোল',
'SAR' => 'সৌদি রিয়াল',
'AED' => 'সংযুক্ত আরব আমিরাত দিরহাম',
'ARS' => 'আর্জেন্টাইন পেসো',
'COP' => 'কলম্বিয়ান পেসো',
'UYU' => 'উরুগুয়ান পেসো',
'RON' => 'রোমানিয়ান লেউ',
'BGN' => 'বুলগেরিয়ান লেভ',
'HRK' => 'ক্রোয়েশিয়ান কুনা',
'BHD' => 'বাহরাইনি দিনার',
'OMR' => 'ওমানি রিয়াল',
'QAR' => 'কাতারি রিয়াল',
'JOD' => 'জর্ডানিয়ান দিনার',
'KWD' => 'কুয়েতি দিনার',
'MAD' => 'মরোক্কান দিরহাম',
'TND' => 'তিউনিসিয়ান দিনার',
'LBP' => 'লেবানিজ পাউন্ড',
'SYP' => 'সিরিয়ান পাউন্ড',
'GHS' => 'ঘানিয়ান সেদি',
'KES' => 'কেনিয়ান শিলিং',
'TZS' => 'তানজানিয়ান শিলিং',
'UGX' => 'উগান্ডান শিলিং',
'RWF' => 'রুয়ান্ডান ফ্রাঁ',
'MUR' => 'মরিশাসিয়ান রুপি',
'XOF' => 'ওয়েস্ট আফ্রিকান CFA ফ্রাঙ্ক',
'XAF' => 'সেন্ট্রাল আফ্রিকান CFA ফ্রাঙ্ক',
'BWP' => 'বতসোয়ানা পুলা',
'ZMW' => 'জাম্বিয়ান কোয়াচা',
'NAD' => 'নামিবিয়ান ডলার',
'MZN' => 'মোজাম্বিকান মেটিকল',
'BND' => 'ব্রুনাই ডলার',
'FJD' => 'ফিজিয়ান ডলার',
'PGK' => 'পাপুয়া নিউ গিনিয়ান কিনা',
'SBD' => 'সলোমন দ্বীপপুঞ্জের ডলার',
'WST' => 'সামোয়ান টালা',
'TOP' => 'টোঙ্গান পা\'আঙ্গা',
'KWD' => 'কুয়েতি দিনার',
'BHD' => 'বাহরাইনি দিনার',
'OMR' => 'ওমানি রিয়াল',
'JOD' => 'জর্ডানিয়ান দিনার',
'QAR' => 'কাতারি রিয়াল',
'AED' => 'সংযুক্ত আরব আমিরাত দিরহাম',
'IRR' => 'ইরানি রিয়াল',
'IQD' => 'ইরাকি দিনার',
'SYP' => 'সিরিয়ান পাউন্ড',
'YER' => 'ইয়েমেনি রিয়াল'
    ];

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
function cc_get_currencies() {
    return [
       'USD' => 'আমেরিকান ডলার',
'BDT' => 'বাংলাদেশি টাকা',
'EUR' => 'ইউরো',
'JPY' => 'জাপানি ইয়েন',
'GBP' => 'ব্রিটিশ পাউন্ড',
'AUD' => 'অস্ট্রেলিয়ান ডলার',
'CAD' => 'কানাডিয়ান ডলার',
'CHF' => 'সুইস ফ্রাঁ',
'CNY' => 'চীনা ইউয়ান',
'SEK' => 'সুইডিশ ক্রোনা',
'NZD' => 'নিউজিল্যান্ড ডলার',
'MXN' => 'মেক্সিকান পেসো',
'SGD' => 'সিঙ্গাপুর ডলার',
'HKD' => 'হংকং ডলার',
'NOK' => 'নরওয়েজিয়ান ক্রোন',
'KRW' => 'দক্ষিণ কোরিয়ান ওন',
'TRY' => 'তুর্কি লিরা',
'RUB' => 'রাশিয়ান রুবল',
'INR' => 'ভারতীয় রুপি',
'BRL' => 'ব্রাজিলিয়ান রিয়াল',
'ZAR' => 'দক্ষিণ আফ্রিকান র্যান্ড',
'DKK' => 'ড্যানিশ ক্রোন',
'PLN' => 'পোলিশ জ্লোটি',
'TWD' => 'তাইওয়ান ডলার',
'THB' => 'থাই ভাট',
'MYR' => 'মালয়েশিয়ান রিঙ্গিত',
'IDR' => 'ইন্দোনেশিয়ান রুপিয়াহ',
'CZK' => 'চেক ক্রোনা',
'HUF' => 'হাঙ্গেরিয়ান ফোরিন্ট',
'ILS' => 'ইসরায়েলি শেকেল',
'CLP' => 'চিলিয়ান পেসো',
'PHP' => 'ফিলিপাইন পেসো',
'PKR' => 'পাকিস্তানি রুপি',
'EGP' => 'মিশরীয় পাউন্ড',
'NGN' => 'নাইজেরিয়ান নাইরা',
'VND' => 'ভিয়েতনামি ডং',
'KZT' => 'কাজাখস্তানি তেঙ্গে',
'PEN' => 'পেরুভিয়ান সোল',
'SAR' => 'সৌদি রিয়াল',
'AED' => 'সংযুক্ত আরব আমিরাত দিরহাম',
'ARS' => 'আর্জেন্টাইন পেসো',
'COP' => 'কলম্বিয়ান পেসো',
'UYU' => 'উরুগুয়ান পেসো',
'RON' => 'রোমানিয়ান লেউ',
'BGN' => 'বুলগেরিয়ান লেভ',
'HRK' => 'ক্রোয়েশিয়ান কুনা',
'BHD' => 'বাহরাইনি দিনার',
'OMR' => 'ওমানি রিয়াল',
'QAR' => 'কাতারি রিয়াল',
'JOD' => 'জর্ডানিয়ান দিনার',
'KWD' => 'কুয়েতি দিনার',
'MAD' => 'মরোক্কান দিরহাম',
'TND' => 'তিউনিসিয়ান দিনার',
'LBP' => 'লেবানিজ পাউন্ড',
'SYP' => 'সিরিয়ান পাউন্ড',
'GHS' => 'ঘানিয়ান সেদি',
'KES' => 'কেনিয়ান শিলিং',
'TZS' => 'তানজানিয়ান শিলিং',
'UGX' => 'উগান্ডান শিলিং',
'RWF' => 'রুয়ান্ডান ফ্রাঁ',
'MUR' => 'মরিশাসিয়ান রুপি',
'XOF' => 'ওয়েস্ট আফ্রিকান CFA ফ্রাঙ্ক',
'XAF' => 'সেন্ট্রাল আফ্রিকান CFA ফ্রাঙ্ক',
'BWP' => 'বতসোয়ানা পুলা',
'ZMW' => 'জাম্বিয়ান কোয়াচা',
'NAD' => 'নামিবিয়ান ডলার',
'MZN' => 'মোজাম্বিকান মেটিকল',
'BND' => 'ব্রুনাই ডলার',
'FJD' => 'ফিজিয়ান ডলার',
'PGK' => 'পাপুয়া নিউ গিনিয়ান কিনা',
'SBD' => 'সলোমন দ্বীপপুঞ্জের ডলার',
'WST' => 'সামোয়ান টালা',
'TOP' => 'টোঙ্গান পা\'আঙ্গা',
'KWD' => 'কুয়েতি দিনার',
'BHD' => 'বাহরাইনি দিনার',
'OMR' => 'ওমানি রিয়াল',
'JOD' => 'জর্ডানিয়ান দিনার',
'QAR' => 'কাতারি রিয়াল',
'AED' => 'সংযুক্ত আরব আমিরাত দিরহাম',
'IRR' => 'ইরানি রিয়াল',
'IQD' => 'ইরাকি দিনার',
'SYP' => 'সিরিয়ান পাউন্ড',
'YER' => 'ইয়েমেনি রিয়াল'

        // Add other currencies here
    ];
}

function cc_currency_options($selected = '') {
    $currencies = cc_get_currencies();
    foreach ($currencies as $code => $name) {
        echo '<option value="' . $code . '"' . selected($selected, $code, false) . '>' . $name . '</option>';
    }
}

// Create the Currency Converter Shortcode
function cc_currency_converter($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'BDT'
    ), $atts);

    ob_start();
    ?>
    <div class="cc-container">
        <div class="main-div">
            <div>
                <div class="sub-amount-box">
                    <label for="cc-amount">Amount</label>
                    <input type="number" id="cc-amount" value="1" min="1" />
                </div>
            </div>
            <div class="sub-dropdown-column">
                <div class="cc-column">
                    <label for="cc-from-currency">From</label>
                    <select id="cc-from-currency">
                        <?php cc_currency_options($atts['from']); ?>
                    </select>
                </div>
                <div class="cc-column">
                    <label for="cc-to-currency">To</label>
                    <select id="cc-to-currency">
                        <?php cc_currency_options($atts['to']); ?>
                    </select>
                </div>
                <div class="cc-column">
                    <button id="cc-reverse">⇄</button>
                </div>
                <div class="cc-column">
                    <label for="cc-preview-rate">Preview Interbank Rate</label>
                    <select id="cc-preview-rate">
                        <option value="0">0%</option>
                        <option value="1">+1%</option>
                        <option value="-1">-1%</option>
                        <option value="2">+2%</option>
                        <option value="-2">-2%</option>
                        <!-- Add more options as needed -->
                    </select>
                </div>
            </div>
        </div>
        <button id="cc-convert">🔄রেট দেখুন</button>
        <button id="cc-print">🖶 প্রিন্ট করুন</button>
        <div id="cc-loader" style="display: none; color:green">⌛লোড নিচ্ছে..... অপেক্ষা করুন!</div>
        <div class="rate-showing">
            <div id="cc-result"></div>
        <div id="cc-increased-rate"></div>
        </div>
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
        <div id="cc-additional-info"></div>
        <div id="cc-stats-table">
            <h3>গত কিছুদিনের টাকার রেট</h3>
            <table>
                <thead>
                    <tr>
                        <th>সময়কাল</th>
                        <th>রেট</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>শেষ ৭ দিন</td><td id="cc-stats-7days"></td></tr>
                    <tr><td>শেষ ১৫ দিন</td><td id="cc-stats-15days"></td></tr>
                    <tr><td>শেষ ৩০ দিন</td><td id="cc-stats-30days"></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_converter', 'cc_currency_converter');

// Add "Shortcodes" menu item
function cc_register_shortcodes_menu() {
    add_menu_page(
        'Shortcodes',
        'Shortcodes',
        'manage_options',
        'cc-shortcodes',
        'cc_display_shortcodes_page',
        'dashicons-editor-code',
        20
    );
}
add_action('admin_menu', 'cc_register_shortcodes_menu');

function cc_display_shortcodes_page() {
    ?>
    <div class="wrap">
        <h1>Available Shortcodes</h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>[currency_converter]</td>
                    <td>Display the currency converter</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}
?>
