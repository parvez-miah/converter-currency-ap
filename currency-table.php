<?php
// Function to convert numbers to Bengali format and add Taka and Poisa
function format_bengali_currency($amount) {
    $bengali_nums = array("০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯");
    $formatted_amount = number_format($amount, 2, '.', '');
    $parts = explode('.', $formatted_amount);

    $bengali_taka = str_replace(range(0, 9), $bengali_nums, $parts[0]);
    $bengali_poisa = str_replace(range(0, 9), $bengali_nums, $parts[1]);

    return $bengali_taka . ' টাকা ' . $bengali_poisa . ' পয়সা';
}

// Include the currency names from an external file
include 'currency-names.php';

// Create the Currency Table Shortcode
function cc_currency_table() {
    // Get cached data
    $cached_data = get_transient('cached_currency_data');

    if ($cached_data === false) {
        $currencies = get_currency_names();
        $currency_rates = []; // Cache currency rates

        foreach ($currencies as $currency_code => $currency_name) {
            $rate = get_conversion_rate($currency_code, 'BDT');
            if ($rate !== false) {
                $currency_rates[$currency_code] = $rate;
                $bank_rate = $rate;
                $exchange_rate = $bank_rate * 1.02;

                // Convert to Bengali numbers and format
                $bank_rate_bengali = format_bengali_currency($bank_rate);
                $exchange_rate_bengali = format_bengali_currency($exchange_rate);

                $currency_data[] = [
                    'currency_name' => $currency_name,
                    'bank_rate' => $bank_rate_bengali,
                    'exchange_rate' => $exchange_rate_bengali
                ];
            }
        }
        // Cache data for 12 hours
        set_transient('cached_currency_data', $currency_data, 12 * HOUR_IN_SECONDS);
    } else {
        $currency_data = $cached_data;
    }

    ob_start();
    ?>
    <input type="text" id="searchBar" placeholder="দেশের নাম সার্চ করুন...">
    <div class="cc-loader"></div>
    <div class="cc-table-container">
        <table class="cc-currency-table">
            <thead>
                <tr>
                    <th>মুদ্রা</th>
                    <th>ব্যাংক রেট</th>
                    <th>এক্সচেঞ্জ রেট</th>
                </tr>
            </thead>
            <tbody id="currencyTableBody">
                <?php
                foreach ($currency_data as $data) {
                    echo '<tr>';
                    echo '<td>' . $data['currency_name'] . '</td>';
                    echo '<td>' . $data['bank_rate'] . '</td>';
                    echo '<td>' . $data['exchange_rate'] . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <div id="pagination"></div>
    </div>
    <p id="noResults" style="display:none;">ফলাফল পাওয়া যায়নি</p>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_table', 'cc_currency_table');
?>
