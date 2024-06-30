<?php
function render_specific_currency_table() {
    $html = '
    <input type="text" id="specificSearchBar" class="search-bar" placeholder="দেশের নাম সার্চ করুন..." autocomplete="off">
    <table id="specific-currency-table" class="cc-currency-table">
        <thead>
            <tr>
                <th>মুদ্রা</th>
                <th>ব্যাংক রেট</th>
                <th>এক্সচেঞ্জ রেট</th>
            </tr>
        </thead>
        <tbody id="specificCurrencyTableBody"></tbody>
    </table>
    <p id="specificNoResults" class="no-results" style="display:none;">দেশের নাম সঠিকভাবে লিখুন। এই নামে কোন ডাটা পাওয়া যায়নি..</p>
    <div id="specificLoader" style="display: none; color:green; text-align: center; margin-bottom: 10px">⌛লোড নিচ্ছে..... অপেক্ষা করুন!</div>
    <div id="specificPagination" class="pagination">
        <button id="specificPrevPage" class="pagination-button" disabled> ◀️পূর্ববর্তী টাকার রেট</button>
        <span id="specificPageIndicator">Page 1</span>
        <button id="specificNextPage" class="pagination-button">পরবর্তী টাকার রেট▶️ </button>
    </div>
    <script src="' . plugin_dir_url(__FILE__) . 'js/specific-currency-table.min.js" defer></script>';
    return $html;
}

function cc_load_specific_currency_table() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 7;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $currencies = array(
        'BDT' => 'বাংলাদেশ',
        'USD' => 'আমেরিকা',
        'EUR' => 'ইউরোপ',
        'GBP' => 'যুক্তরাজ্য',
        'SAR' => 'সৌদি আরব',
        'AED' => 'সংযুক্ত আরব আমিরাত',
        'BHD' => 'বাহরাইন',
        'OMR' => 'ওমান',
        'QAR' => 'কাতার',
        'KWD' => 'কুয়েত',
        'JOD' => 'জর্ডান'
    );

    if ($search) {
        $currencies = array_filter($currencies, function($currency_name) use ($search) {
            return stripos($currency_name, $search) !== false;
        });
    }

    $total = count($currencies);
    $start = ($page - 1) * $per_page;
    $unique_currencies = array_slice($currencies, $start, $per_page, true);
    $has_more = ($start + $per_page) < $total;

    $html = '';
    foreach ($unique_currencies as $currency_code => $currency_name) {
        $rate = get_conversion_rate($currency_code, 'BDT');
        if ($rate !== false) {
            $bank_rate = $rate;
            $exchange_rate = $bank_rate * 1.02; // Assume a 2% markup for exchange rate

            $html .= '<tr style="height: 50px;">
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

add_action('wp_ajax_load_specific_currency_table', 'cc_load_specific_currency_table');
add_action('wp_ajax_nopriv_load_specific_currency_table', 'cc_load_specific_currency_table');

function cc_specific_currency_table_shortcode() {
    return render_specific_currency_table();
}
add_shortcode('specific_currency_table', 'cc_specific_currency_table_shortcode');

?>