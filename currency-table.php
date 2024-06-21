

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

// Create the Currency Table Shortcode
function cc_currency_table() {
    ob_start();
    ?>
    <div class="cc-loader"></div>
    <div class="cc-table-container">
        <table class="cc-currency-table">
            <thead>
                <tr>
                    <th>মুদ্রা</th>
                    <th>ব্যাংকের রেট</th>
                    <th>এএক্সচেঞ্জ রেট</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $currencies = array(
                    'USD' => 'আমেরিকান ডলার',
                    'EUR' => 'ইউরো',
                    'AED' => 'দুবাই দিরহাম',
                    'QAR' => 'কাতারি রিয়াল',
                    'KWD' => 'কুয়েতি দিনার',
                    'OMR' => 'ওমানি রিয়াল',
                    'SGD' => 'সিঙ্গাপুরি ডলার',
                    'MYR' => 'মালয়েশিয়ান রিঙ্গিত',
                    'AUD' => 'অস্ট্রেলিয়ান ডলার',
                    'CAD' => 'কানাডিয়ান ডলার',
                    'GBP' => 'ব্রিটিশ পাউন্ড',
                    'CHF' => 'সুইস ফ্রাঁ',
                    'CNY' => 'চীনা ইয়েন',
                    'JPY' => 'জাপানি ইয়েন',
                    'THB' => 'থাই বাথ'
                );

                foreach ($currencies as $currency_code => $currency_name) {
                    $rate = get_conversion_rate($currency_code, 'BDT');
                    if ($rate !== false) {
                        $bank_rate = $rate;
                        $exchange_rate = $bank_rate * 1.02;

                        // Convert to Bengali numbers and format
                        $bank_rate_bengali = format_bengali_currency($bank_rate);
                        $exchange_rate_bengali = format_bengali_currency($exchange_rate);

                        echo '<tr>';
                        echo '<td>' . $currency_name . '</td>';
                        echo '<td>' . $bank_rate_bengali . '</td>';
                        echo '<td>' . $exchange_rate_bengali . '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loader = document.querySelector('.cc-loader');
            var tableContainer = document.querySelector('.cc-table-container');

            // Show the loader
            loader.style.display = 'block';

            // Simulate fetching data
            setTimeout(function() {
                // Hide the loader and show the table
                loader.style.display = 'none';
                tableContainer.style.display = 'block';
            }, 2000); // 2 seconds for demonstration, adjust as needed
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_table', 'cc_currency_table');
