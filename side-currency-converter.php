<?php

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
