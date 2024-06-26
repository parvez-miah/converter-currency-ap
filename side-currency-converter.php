<?php
function cc_currency_converter($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'BDT'
    ), $atts);
    // header("Access-Control-Allow-Origin: *");

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

    // Define transient key for caching
    $transient_key = 'cc_currency_converter_' . $atts['from'] . '_' . $atts['to'];
    $table_cache_enabled = get_option('cc_table_cache_enabled', 'yes');

    // Attempt to fetch cached HTML
    if ($table_cache_enabled === 'yes') {
        $cached_html = get_transient($transient_key);
    } else {
        $cached_html = false;
    }

    if ($cached_html === false) {
        // Start output buffering
        ob_start();
        ?>
        <div class="cc-container">
        <div class="full-currency-converter-box">
    <!-- Navigation Bar -->
    <div class="graph-navbar-elements">
        <div class="navbar-sub-div">
            <img style="margin-top: 12px" src="https://ajkertakarrate.com/wp-content/uploads/2024/07/money-exchange_11zon.png" alt="">
        <a href="#convert">কনভার্টার</a>
        </div>
        <div class="navbar-sub-div">
            <img style="margin-top: 11px" src="https://ajkertakarrate.com/wp-content/uploads/2024/07/exchange-rate_11zon.png" alt="">
        <a href="https://ajkertakarrate.com/%e0%a6%9f%e0%a6%be%e0%a6%95%e0%a6%be%e0%a6%b0-%e0%a6%b0%e0%a7%87%e0%a6%9f-%e0%a6%90%e0%a6%a4%e0%a6%bf%e0%a6%b9%e0%a6%be%e0%a6%b8%e0%a6%bf%e0%a6%95-%e0%a6%97%e0%a7%8d%e0%a6%b0%e0%a6%be%e0%a6%ab/">গ্রাফ</a>
      
        </div>
       <div class="navbar-sub-div">
        <img style="margin-top: 12px" src="https://ajkertakarrate.com/wp-content/uploads/2024/07/notification_11zon.png" alt="">
       <a href="#alerts" title="শীঘ্রই এটা ব্যবহার করতে পারবেন!">নোটিফিকেশন</a>
       </div>
    </div>
    <div class="currency-converter-box">
        <!-- Amount Box -->
        <div class="sub-amount-box">
            <label for="cc-amount">পরিমাণ</label>
            <input type="number" id="cc-amount" value="1" min="1" />
        </div>
        <!-- From Box -->
        <div class="cc-column">
            <label for="cc-from-currency">From</label>
            <select id="cc-from-currency">
                <?php cc_currency_options($atts['from']); ?>
            </select>
        </div>
        <!-- Reverse Button -->
        <div>
            <button id="cc-reverse">⇄</button>
        </div>
        <!-- To Box -->
        <div class="cc-column">
            <label for="cc-to-currency">To</label>
            <select id="cc-to-currency">
                <?php cc_currency_options($atts['to']); ?>
            </select>
        </div>
    </div>
    <!-- Bank Adjustment -->
    <div class="bank-adjust">
      <div class="title-bank-adjust">
      <label for="cc-preview-rate">ব্যাংক রেট অ্যাডজাস্ট করুন</label>
      </div>
    <div class="cc-preview-rate">
                      
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
    <!-- Exchange Button -->
    <div class="rate-print-button" style="display: flex; align-items: center">
                    <button id="cc-convert" style="margin-right: 7px;">রেট দেখুন</button>
                    <button id="cc-print">প্রিন্ট করুন</button>
                </div>
    <div class="rate-showing">
        <div id="cc-loader" style="display: none; color:green">⌛টাকার রেট আপডেট হচ্ছে..... অপেক্ষা করুন!</div>
        <div id="cc-result"></div>
        <div id="cc-increased-rate"></div>
    </div>
    
</div>

 <!-- <div id="historical-graph-container">
            <?php echo do_shortcode('[historical_currency_graph_only from="' . $atts['from'] . '" to="' . $atts['to'] . '" period="1M"]'); ?>
            </div>  -->

            
            
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
        
       <!-- <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" defer crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/@amcharts/amcharts4/core.js" defer crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/@amcharts/amcharts4/charts.js" defer crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/@amcharts/amcharts4/themes/animated.js" defer crossorigin="anonymous"></script>
        <script src="<?php echo plugin_dir_url(__FILE__); ?>graph-script.js" defer crossorigin="anonymous"></script>  -->
        <?php
        // Get the buffered content and clean the buffer
        $cached_html = ob_get_clean();

        if ($table_cache_enabled === 'yes') {
            // Cache the HTML for a year
            set_transient($transient_key, $cached_html, 31536000);
        }
    }

    return $cached_html;
}
add_shortcode('currency_converter', 'cc_currency_converter');
?>
