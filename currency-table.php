<?php
// Function to convert numbers to Bengali format and add Taka and Poisa
function format_bengali_currency($amount) {
    $bengali_nums = array("০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯");
    $formatted_amount = number_format($amount, 2, '.', '');
    list($taka, $poisa) = explode('.', $formatted_amount);

    $bengali_taka = str_replace(range(0, 9), $bengali_nums, $taka);
    $bengali_poisa = str_replace(range(0, 9), $bengali_nums, $poisa);

    return "{$bengali_taka} টাকা {$bengali_poisa} পয়সা";
}

// Include the currency names from an external file
include_once 'currency-table-names.php';

// Cache currency names permanently using WordPress options
function get_permanent_currency_names() {
    $currencies = get_option('permanent_currency_names');
    if ($currencies === false) {
        $currencies = get_table_currency_names();
        update_option('permanent_currency_names', $currencies);
    }
    return $currencies;
}

// Create the Currency Table Shortcode
function cc_currency_table() {
    // Get cached data
    $currency_data = get_transient('cached_currency_data');

    if ($currency_data === false) {
        $currencies = get_permanent_currency_names();
        $currency_data = [];

        foreach ($currencies as $currency_code => $currency_name) {
            $rate = get_conversion_rate($currency_code, 'BDT');
            if ($rate !== false) {
                $bank_rate = $rate;
                $exchange_rate = $bank_rate * 1.02;

                // Convert to Bengali numbers and format
                $currency_data[] = [
                    'currency_name' => $currency_name,
                    'bank_rate' => format_bengali_currency($bank_rate),
                    'exchange_rate' => format_bengali_currency($exchange_rate)
                ];
            }
        }
        // Cache data for 12 hours
        set_transient('cached_currency_data', $currency_data, 12 * HOUR_IN_SECONDS);
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
                <?php foreach ($currency_data as $data): ?>
                    <tr>
                        <td><?php echo $data['currency_name']; ?></td>
                        <td><?php echo $data['bank_rate']; ?></td>
                        <td><?php echo $data['exchange_rate']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="pagination"></div>
    </div>
    <p id="noResults" style="display:none;">ফলাফল পাওয়া যায়নি</p>
    <style>
        .cc-table-container { margin: 20px 0; }
        .cc-currency-table { width: 100%; border-collapse: collapse; }
        .cc-currency-table th, .cc-currency-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        #searchBar { margin-bottom: 10px; padding: 8px; width: 100%; }
        .cc-loader { display: none; }
        #noResults { color: red; }
    </style>
    <script>
        document.getElementById('searchBar').addEventListener('input', function() {
            var filter = this.value.toLowerCase();
            var rows = document.querySelectorAll('#currencyTableBody tr');
            var found = false;
            rows.forEach(function(row) {
                var cell = row.querySelector('td').innerText.toLowerCase();
                if (cell.includes(filter)) {
                    row.style.display = '';
                    found = true;
                } else {
                    row.style.display = 'none';
                }
            });
            document.getElementById('noResults').style.display = found ? 'none' : 'block';
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_table', 'cc_currency_table');
?>
