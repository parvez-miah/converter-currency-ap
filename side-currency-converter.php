<?php
function cc_currency_converter($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'BDT'
    ), $atts);

    // Get today's date and convert it to Bengali
    $today_date = date('Y-m-d');
    $months = array(
        '01' => 'জানুয়ারি', '02' => 'ফেব্রুয়ারি', '03' => 'মার্চ', '04' => 'এপ্রিল', 
        '05' => 'মে', '06' => 'জুন', '07' => 'জুলাই', '08' => 'আগস্ট', 
        '09' => 'সেপ্টেম্বর', '10' => 'অক্টোবর', '11' => 'নভেম্বর', '12' => 'ডিসেম্বর'
    );
    $bengali_numbers = array('0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯');
    
    $year = strtr(date('Y', strtotime($today_date)), $bengali_numbers);
    $month = $months[date('m', strtotime($today_date))];
    $day = strtr(date('d', strtotime($today_date)), $bengali_numbers);
    
    $today_date_bengali = $day . ' ' . $month . ' ' . $year;

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
                <div class="dropdown-main">
                    <div style="margin-right: 10px" class="cc-column">
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
                </div>
                <div class="cc-column">
                    <button id="cc-reverse">⇄</button>
                </div>
                <div class="cc-column">
                    <label for="cc-preview-rate">ব্যাংক রেট অ্যাডজাস্ট করুন</label>
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
        <div class="rate-print-button" style="display: flex; align-items: center">
            <button id="cc-convert" style="margin-right: 7px;">🔄রেট দেখুন</button>
            <button id="cc-print">🖶 প্রিন্ট করুন</button>
        </div>
        <div id="cc-loader" style="display: none; color:green">⌛লোড নিচ্ছে..... অপেক্ষা করুন!</div>
        <div class="rate-showing">
            <div id="cc-result"></div>
            <div id="cc-increased-rate"></div>
        </div>
        <br>
        <div>
                    <h3 id="cc-title">আজকের টাকার রেট : <?php echo $atts['from']; ?> হতে <?php echo $atts['to']; ?></h3>

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
        <p style="text-align:center">আজকের টাকার রেট : <?php echo $today_date_bengali; ?></p>
        <br>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fromCurrencyDropdown = document.getElementById('cc-from-currency');
            const toCurrencyDropdown = document.getElementById('cc-to-currency');
            const titleElement = document.getElementById('cc-title');

            function updateTitle() {
                const fromCurrency = fromCurrencyDropdown.options[fromCurrencyDropdown.selectedIndex].text;
                const toCurrency = toCurrencyDropdown.options[toCurrencyDropdown.selectedIndex].text;
                titleElement.innerHTML = `আজকের টাকার রেট : ${fromCurrency} হতে ${toCurrency}`;
            }

            fromCurrencyDropdown.addEventListener('change', updateTitle);
            toCurrencyDropdown.addEventListener('change', updateTitle);
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_converter', 'cc_currency_converter');
?>
