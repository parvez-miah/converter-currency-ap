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


include_once(plugin_dir_path(__FILE__) . 'currency-table.php');
include_once(plugin_dir_path(__FILE__) . 'side-currency-converter.php');


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
        // If rate is still not found, return an error
        wp_send_json_error('Conversion rate could not be fetched.');
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
'IRR' => 'ইরানি রিয়াল',
'IQD' => 'ইরাকি দিনার',
'YER' => 'ইয়েমেনি রিয়াল',
'ALL' => 'আলবেনিয়ান লেক',
'AMD' => 'আর্মেনিয়ান ড্রাম',
'AZN' => 'আজারবাইজানি মানাত',
'BAM' => 'বসনিয়ান কনভার্টিবল মার্ক',
'BBD' => 'বার্বাডোস ডলার',
'BMD' => 'বারমুডান ডলার',
'BOB' => 'বলিভিয়ান বলিভিয়ানো',
'BSD' => 'বাহামিয়ান ডলার',
'BTN' => 'ভুটানি এনগুল্ট্রুম',
'BZD' => 'বেলিজ ডলার',
'CDF' => 'কঙ্গোলিজ ফ্রাঁ',
'DOP' => 'ডোমিনিকান পেসো',
'ETB' => 'ইথিওপিয়ান বির',
'GEL' => 'জর্জিয়ান লারি',
'GTQ' => 'গুয়াতেমালান কেটজাল',
'GYD' => 'গিয়েনিজ ডলার',
'HTG' => 'হাইতিয়ান গর্ড',
'ISK' => 'আইসল্যান্ডিক ক্রোনা',
'JMD' => 'জ্যামাইকান ডলার',
'KGS' => 'কিরগিজিস্তানি সোম',
'KHR' => 'ক্যাম্বোডিয়ান রিয়েল',
'KYD' => 'কেম্যান দ্বীপপুঞ্জের ডলার',
'LAK' => 'লাও কিপ',
'LSL' => 'লেসোথো লোটি',
'LKR' => 'শ্রীলঙ্কান রুপি',
'LRD' => 'লিবেরিয়ান ডলার',
'MGA' => 'মালাগাসি আরিয়ারি',
'MKD' => 'ম্যাসিডোনিয়ান ডেনার',
'MMK' => 'মিয়ানমারের কিয়াত',
'MNT' => 'মঙ্গোলিয়ান তুগ্রিক',
'MOP' => 'ম্যাকানিজ পাতাকা',
'MVR' => 'মালদিভিয়ান রুফিয়া',
'MWK' => 'মালাউইয়ান কওয়াচা',
'NIO' => 'নিকারাগুয়ান কোরডোবা',
'NPR' => 'নেপালি রুপি',
'PAB' => 'পানামানিয়ান বালবোয়া',
'PGK' => 'পাপুয়া নিউ গিনিয়ান কিনা',
'PYG' => 'প্যারাগুয়ান গুয়ারানি',
'RSD' => 'সার্বিয়ান দিনার',
'SCR' => 'সিসিলিয়ান রুপি',
'SDG' => 'সুদানি পাউন্ড',
'SHP' => 'সেন্ট হেলেনা পাউন্ড',
'SLL' => 'সিয়েরা লিওন লিওন',
'SRD' => 'সুরিনামী ডলার',
'SSP' => 'দক্ষিণ সুদানি পাউন্ড',
'STN' => 'সাও টোমে ও প্রিনসিপে ডোবরা',
'SZL' => 'সোয়াজিল্যান্ড লিলাঙ্গেনি',
'TJS' => 'তাজিকিস্তানি সোমোনি',
'TMT' => 'তুর্কমেনিস্তানি মানাত',
'TVD' => 'তুভালু ডলার',
'UAK' => 'ইউক্রেনিয়ান কারবোভানেটস',
'UZS' => 'উজবেকিস্তানি সোম',
'VES' => 'ভেনেজুয়েলান বলিভার',
'VUV' => 'ভানুয়াতু ভাতু',
'XCD' => 'পূর্ব ক্যারিবিয়ান ডলার',
'YER' => 'ইয়েমেনি রিয়াল',
'ZWL' => 'জিম্বাবুয়ান ডলার'

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
'IRR' => 'ইরানি রিয়াল',
'IQD' => 'ইরাকি দিনার',
'YER' => 'ইয়েমেনি রিয়াল',
'ALL' => 'আলবেনিয়ান লেক',
'AMD' => 'আর্মেনিয়ান ড্রাম',
'AZN' => 'আজারবাইজানি মানাত',
'BAM' => 'বসনিয়ান কনভার্টিবল মার্ক',
'BBD' => 'বার্বাডোস ডলার',
'BMD' => 'বারমুডান ডলার',
'BOB' => 'বলিভিয়ান বলিভিয়ানো',
'BSD' => 'বাহামিয়ান ডলার',
'BTN' => 'ভুটানি এনগুল্ট্রুম',
'BZD' => 'বেলিজ ডলার',
'CDF' => 'কঙ্গোলিজ ফ্রাঁ',
'DOP' => 'ডোমিনিকান পেসো',
'ETB' => 'ইথিওপিয়ান বির',
'GEL' => 'জর্জিয়ান লারি',
'GTQ' => 'গুয়াতেমালান কেটজাল',
'GYD' => 'গিয়েনিজ ডলার',
'HTG' => 'হাইতিয়ান গর্ড',
'ISK' => 'আইসল্যান্ডিক ক্রোনা',
'JMD' => 'জ্যামাইকান ডলার',
'KGS' => 'কিরগিজিস্তানি সোম',
'KHR' => 'ক্যাম্বোডিয়ান রিয়েল',
'KYD' => 'কেম্যান দ্বীপপুঞ্জের ডলার',
'LAK' => 'লাও কিপ',
'LSL' => 'লেসোথো লোটি',
'LKR' => 'শ্রীলঙ্কান রুপি',
'LRD' => 'লিবেরিয়ান ডলার',
'MGA' => 'মালাগাসি আরিয়ারি',
'MKD' => 'ম্যাসিডোনিয়ান ডেনার',
'MMK' => 'মিয়ানমারের কিয়াত',
'MNT' => 'মঙ্গোলিয়ান তুগ্রিক',
'MOP' => 'ম্যাকানিজ পাতাকা',
'MVR' => 'মালদিভিয়ান রুফিয়া',
'MWK' => 'মালাউইয়ান কওয়াচা',
'NIO' => 'নিকারাগুয়ান কোরডোবা',
'NPR' => 'নেপালি রুপি',
'PAB' => 'পানামানিয়ান বালবোয়া',
'PGK' => 'পাপুয়া নিউ গিনিয়ান কিনা',
'PYG' => 'প্যারাগুয়ান গুয়ারানি',
'RSD' => 'সার্বিয়ান দিনার',
'SCR' => 'সিসিলিয়ান রুপি',
'SDG' => 'সুদানি পাউন্ড',
'SHP' => 'সেন্ট হেলেনা পাউন্ড',
'SLL' => 'সিয়েরা লিওন লিওন',
'SRD' => 'সুরিনামী ডলার',
'SSP' => 'দক্ষিণ সুদানি পাউন্ড',
'STN' => 'সাও টোমে ও প্রিনসিপে ডোবরা',
'SZL' => 'সোয়াজিল্যান্ড লিলাঙ্গেনি',
'TJS' => 'তাজিকিস্তানি সোমোনি',
'TMT' => 'তুর্কমেনিস্তানি মানাত',
'TVD' => 'তুভালু ডলার',
'UAK' => 'ইউক্রেনিয়ান কারবোভানেটস',
'UZS' => 'উজবেকিস্তানি সোম',
'VES' => 'ভেনেজুয়েলান বলিভার',
'VUV' => 'ভানুয়াতু ভাতু',
'XCD' => 'পূর্ব ক্যারিবিয়ান ডলার',
'YER' => 'ইয়েমেনি রিয়াল',
'ZWL' => 'জিম্বাবুয়ান ডলার'

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
        <h1>Shortcodes</h1>
        <p>Use the following shortcodes to embed the currency converter:</p>
        <code>[currency_converter]</code>
    </div>
    <?php
}
