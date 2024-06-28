<?php
function format_bengali_currency($amount) {
    $bengali_nums = array("০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯");
    $formatted_amount = number_format($amount, 2, '.', '');
    list($taka, $poisa) = explode('.', $formatted_amount);

    $bengali_taka = str_replace(range(0, 9), $bengali_nums, $taka);
    $bengali_poisa = str_replace(range(0, 9), $bengali_nums, $poisa);

    return "{$bengali_taka} টাকা {$bengali_poisa} পয়সা";
}

include_once 'currency-table-names.php';

function get_permanent_currency_names() {
    $currencies = get_option('permanent_currency_names');
    if ($currencies === false) {
        $currencies = get_table_currency_names();
        update_option('permanent_currency_names', $currencies);
    }
    return $currencies;
}

function cc_currency_table() {
    ob_start();
    ?>
    <input type="text" id="searchBar" placeholder="দেশের নাম সার্চ করুন...">
    <div class="cc-loader" style="display: block;"></div>
    <div class="cc-table-container" style="display:none;">
        <table class="cc-currency-table">
            <thead>
                <tr>
                    <th>মুদ্রা</th>
                    <th>ব্যাংক রেট</th>
                    <th>এক্সচেঞ্জ রেট</th>
                </tr>
            </thead>
            <tbody id="currencyTableBody"></tbody>
        </table>
        <div id="pagination"></div>
    </div>
    <p id="noResults" style="display:none;">দেশের নাম সঠিকভাবে লিখুন। এই নামে কোন ডাটা পাওয়া যায়নি..</p>
    <br>
    <script src="<?php echo plugin_dir_url(__FILE__); ?>js/currency-table.js"></script>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_table', 'cc_currency_table');

function cc_fetch_currency_data() {
    $currency_data = get_transient('cached_currency_data');

    if ($currency_data === false) {
        $currencies = get_permanent_currency_names();
        $currency_data = [];

        foreach ($currencies as $currency_code => $currency_name) {
            $rate = get_conversion_rate($currency_code, 'BDT');
            if ($rate !== false) {
                $bank_rate = $rate;
                $exchange_rate = $bank_rate * 1.02;

                $currency_data[] = [
                    'currency_name' => $currency_name,
                    'bank_rate' => format_bengali_currency($bank_rate),
                    'exchange_rate' => format_bengali_currency($exchange_rate)
                ];
            }
        }
        set_transient('cached_currency_data', $currency_data, 12 * HOUR_IN_SECONDS);
    }

    // Return the first 6 rows initially
    $initial_data = array_slice($currency_data, 0, 6);

    wp_send_json_success([
        'initial_data' => $initial_data,
        'all_data' => $currency_data
    ]);
}
add_action('wp_ajax_cc_fetch_currency_data', 'cc_fetch_currency_data');
add_action('wp_ajax_nopriv_cc_fetch_currency_data', 'cc_fetch_currency_data');
