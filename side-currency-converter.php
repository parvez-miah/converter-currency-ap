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

    // Define transient key for caching
    $transient_key = 'cc_currency_converter_' . $atts['from'] . '_' . $atts['to'];

    // Attempt to fetch cached HTML
    $cached_html = get_transient($transient_key);

    if ($cached_html === false) {
        // Start output buffering
        ob_start();
        ?>
        <div class="cc-container">
            <div class="main-div">
                <div>
                    <div class="sub-amount-box">
                        <label for="cc-amount">পরিমাণ</label>
                        <input type="number" id="cc-amount" value="1" min="1" />
                    </div>
                </div>
                <div class="sub-dropdown-column">
                    <div class="dropdown-main">
                        <div style="margin-right: 10px" class="cc-column">
                            <label for="cc-from-currency">হতে</label>
                            <select id="cc-from-currency">
                                <?php cc_currency_options($atts['from']); ?>
                            </select>
                        </div>
                        <div class="cc-column">
                            <label for="cc-to-currency">যাওয়া</label>
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
                <div class="rate-print-button" style="display: flex; align-items: center">
                    <button id="cc-convert" style="margin-right: 7px;">🔄রেট দেখুন</button>
                    <button id="cc-print">🖶 প্রিন্ট করুন</button>
                </div>
            </div>
            
            <div class="rate-showing">
                <div id="cc-loader" style="display: none; color:green">⌛লোড নিচ্ছে..... অপেক্ষা করুন!</div>
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
            <div>
                <h3 id="cc-title">ঐতিহাসিক টাকার রেট : <?php echo $atts['from']; ?> হতে <?php echo $atts['to']; ?></h3>
            </div>
            <div id="historical-graph-container">
            <?php echo do_shortcode('[historical_currency_graph_only from="' . $atts['from'] . '" to="' . $atts['to'] . '" period="1M"]'); ?>
        </div>
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
        
        <!-- Insert Historical Currency Graph Here -->
        

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

                function fetchCurrencyData(from, to, period) {
                    let endDate = new Date();
                    let startDate = new Date();
                    
                    switch (period) {
                        case '1M':
                            startDate.setMonth(startDate.getMonth() - 1);
                            break;
                        case '3M':
                            startDate.setMonth(startDate.getMonth() - 3);
                            break;
                        case '6M':
                            startDate.setMonth(startDate.getMonth() - 6);
                            break;
                        case '1Y':
                            startDate.setFullYear(startDate.getFullYear() - 1);
                            break;
                    }

                    let start = startDate.toISOString().split('T')[0];
                    let end = endDate.toISOString().split('T')[0];

                    fetch(`https://currencies.apps.grandtrunk.net/getrange/${start}/${end}/${from}/${to}`)
                        .then(response => response.text())
                        .then(data => {
                            let lines = data.split('\n');
                            let chartData = lines.map(line => {
                                let [date, value] = line.split(' ');
                                let floatVal = parseFloat(value);
                                return { date: new Date(date), value: floatVal, adjustedRate: (floatVal * 1.02).toFixed(4) };
                            }).filter(item => !isNaN(item.value));
                            
                            updateGraph(chartData.reverse());
                        });
                }

                function updateGraph(data) {
                    am4core.ready(function() {
                        am4core.useTheme(am4themes_animated);

                        var chart = am4core.create("currency-chart", am4charts.XYChart);
                        chart.data = data;
                        chart.scrollbarX = new am4core.Scrollbar();
                        
                        var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                        var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

                        var series = chart.series.push(new am4charts.LineSeries());
                        series.dataFields.valueY = "value";
                        series.dataFields.dateX = "date";
                        series.strokeWidth = 2;
                        series.minBulletDistance = 15;

                        series.tooltipText = "{dateX.formatDate('yyyy-MM-dd')} (ব্যাংক রেট: [bold]{valueY}[/]) (এক্সচেঞ্জ রেট: [bold]{adjustedRate}[/])";
                        series.tooltip.pointerOrientation = "vertical";
                        series.tooltip.background.cornerRadius = 20;
                        series.tooltip.background.fillOpacity = 0.5;
                        series.tooltip.label.padding(12, 12, 12, 12)

                        chart.cursor = new am4charts.XYCursor();
                        chart.cursor.xAxis = dateAxis;
                        chart.cursor.snapToSeries = series;

                        chart.scrollbarY = new am4core.Scrollbar();
                        chart.legend = new am4charts.Legend();
                    });
                }

                function updateGraphContainer() {
                    const fromCurrency = fromCurrencyDropdown.value;
                    const toCurrency = toCurrencyDropdown.value;
                    const period = '1M'; // Default period, can be changed based on user input if needed

                    fetchCurrencyData(fromCurrency, toCurrency, period);
                }

                fromCurrencyDropdown.addEventListener('change', () => {
                    updateTitle();
                    updateGraphContainer();
                });

                toCurrencyDropdown.addEventListener('change', () => {
                    updateTitle();
                    updateGraphContainer();
                });

                updateGraphContainer(); // Initial load
            });
        </script>
        <?php
        // Get the buffered content and clean the buffer
        $cached_html = ob_get_clean();

        // Cache the HTML for 12 hours (adjust as needed)
        set_transient($transient_key, $cached_html, 31536000);
    }

    return $cached_html;
}
add_shortcode('currency_converter', 'cc_currency_converter');

?>
