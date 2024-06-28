<?php
function format_bengali_currency($amount) {
    $bengali_nums = array("০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯");
    $formatted_amount = number_format($amount, 2, '.', '');
    list($taka, $poisa) = explode('.', $formatted_amount);

    $bengali_taka = str_replace(range(0, 9), $bengali_nums, $taka);
    $bengali_poisa = str_replace(range(0, 9), $bengali_nums, $poisa);

    return "{$bengali_taka} টাকা {$bengali_poisa} পয়সা।";
}

include_once 'currency-table-names.php';

function get_permanent_currency_names() {
    $currencies = get_transient('permanent_currency_names');
    if ($currencies === false) {
        $currencies = get_table_currency_names();
        $currencies = array_unique($currencies);
        set_transient('permanent_currency_names', $currencies, 12 * HOUR_IN_SECONDS); // Cache for 12 hours
    }
    return $currencies;
}
function render_currency_table() {
    ob_start();
    ?>
    <input type="text" id="searchBar" class="search-bar" placeholder="দেশের নাম সার্চ করুন...">
    <table id="currency-table" class="cc-currency-table">
        <thead>
            <tr>
                <th>মুদ্রা</th>
                <th>ব্যাংক রেট</th>
                <th>এক্সচেঞ্জ রেট</th>
            </tr>
        </thead>
        <tbody id="currencyTableBody">
            <!-- Initial rows will be loaded here -->
            <tr style="height: 50px;"></tr>
             <!-- Placeholder row to reserve space -->
        </tbody>
    </table>
    <p id="noResults" class="no-results" style="display:none;">দেশের নাম সঠিকভাবে লিখুন। এই নামে কোন ডাটা পাওয়া যায়নি..</p>
   
    <div id="pagination" class="pagination">
        <button id="prevPage" class="pagination-button" disabled> > পূর্ববর্তী টাকার রেট</button>
        <span id="pageIndicator">Page 1</span>
        <button id="nextPage" class="pagination-button">পরবর্তী টাকার রেট < </button>
    </div>
     <script src="<?php echo plugin_dir_url(__FILE__); ?>js/currency-table.js" defer></script>
    <?php
    return ob_get_clean();
}



function cc_load_currency_table() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;

    $currencies = get_permanent_currency_names();
    $total = count($currencies);
    $start = ($page - 1) * $per_page;
    $end = min($start + $per_page, $total);
    $has_more = $end < $total;

    $html = '';
    $unique_currencies = array_slice(array_unique($currencies, SORT_REGULAR), $start, $per_page);

    foreach ($unique_currencies as $currency_code => $currency_name) {
        $rate = get_conversion_rate($currency_code, 'BDT');
        if ($rate !== false) {
            $bank_rate = $rate;
            $exchange_rate = $bank_rate * 1.02;

            $html .= '<tr>
                <td>' . esc_html($currency_name) . '</td>
                <td>' . esc_html(format_bengali_currency($bank_rate)) . '</td>
                <td>' . esc_html(format_bengali_currency($exchange_rate)) . '</td>
            </tr>';
        }
    }

    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $has_more,
        'page' => $page
    ));
}

add_action('wp_ajax_load_currency_table', 'cc_load_currency_table');
add_action('wp_ajax_nopriv_load_currency_table', 'cc_load_currency_table');

function cc_currency_table_shortcode() {
    return render_currency_table();
}
add_shortcode('currency_table', 'cc_currency_table_shortcode');


?>
