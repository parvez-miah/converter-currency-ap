<?php

// Include the currency names file
require_once plugin_dir_path(__FILE__) . 'currency-names.php';

function historical_currency_graph($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'EUR',
        'period' => '1M' // Default period 1 month
    ), $atts);

    // Get the currency names
    $currency_names = get_currency_names();

    ob_start();
    ?>
    <div>
        <label for="amount">Amount:</label>
        <input type="number" id="amount" value="1.0" step="0.01">
        
        <label for="fromCurrency">From:</label>
        <select id="fromCurrency">
            <?php foreach ($currency_names as $currency_code => $currency_name): ?>
                <option value="<?php echo esc_attr($currency_code); ?>" <?php selected($atts['from'], $currency_code); ?>>
                    <?php echo esc_html($currency_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="toCurrency">To:</label>
        <select id="toCurrency">
            <?php foreach ($currency_names as $currency_code => $currency_name): ?>
                <option value="<?php echo esc_attr($currency_code); ?>" <?php selected($atts['to'], $currency_code); ?>>
                    <?php echo esc_html($currency_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="period">Period:</label>
        <select id="period">
            <option value="1M" <?php selected($atts['period'], '1M'); ?>>1 Month</option>
            <option value="3M" <?php selected($atts['period'], '3M'); ?>>3 Months</option>
            <option value="6M" <?php selected($atts['period'], '6M'); ?>>6 Months</option>
            <option value="1Y" <?php selected($atts['period'], '1Y'); ?>>1 Year</option>
        </select>
        
        <button id="updateGraph">Update Graph</button>
    </div>

    <div id="currency-chart" style="width: 100%; height: 500px;"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        am4core.ready(function() {
            am4core.useTheme(am4themes_animated);

            var chart = am4core.create("currency-chart", am4charts.XYChart);
            chart.scrollbarX = new am4core.Scrollbar();
            
            var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
            var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

            var series = chart.series.push(new am4charts.LineSeries());
            series.dataFields.valueY = "value";
            series.dataFields.dateX = "date";
            series.strokeWidth = 2;
            series.minBulletDistance = 15;

            // Add tooltip with exchange rate
            series.tooltipText = "{dateX.formatDate('yyyy-MM-dd')} (ব্যাংক রেট: [bold]{valueY}[/]) (এক্সচেঞ্জ রেট: [bold]{adjustedRate}[/])";
            series.tooltip.pointerOrientation = "vertical";
            series.tooltip.background.cornerRadius = 20;
            series.tooltip.background.fillOpacity = 0.5;
            series.tooltip.label.padding(12, 12, 12, 12);

            chart.cursor = new am4charts.XYCursor();
            chart.cursor.xAxis = dateAxis;
            chart.cursor.snapToSeries = series;

            chart.scrollbarY = new am4core.Scrollbar();
            chart.scrollbarY.parent = chart.leftAxesContainer;
            chart.scrollbarY.toBack();

            chart.scrollbarX = new am4core.Scrollbar();
            chart.scrollbarX.parent = chart.bottomAxesContainer;

            // Fetch and update the chart data
            const fetchCurrencyData = async (from, to, period) => {
                let cacheKey = `currency_data_${from}_${to}_${period}`;
                let cachedData = <?php echo json_encode(get_transient('currency_data_' . $atts['from'] . '_' . $atts['to'] . '_' . $atts['period'])); ?>;

                if (cachedData) {
                    chart.data = JSON.parse(cachedData);
                    return;
                }

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

                let response = await fetch(`https://currencies.apps.grandtrunk.net/getrange/${start}/${end}/${from}/${to}`);
                let data = await response.text();
                let lines = data.split('\n');
                let chartData = lines.map(line => {
                    let [date, value] = line.split(' ');
                    let floatVal = parseFloat(value);
                    return { date: new Date(date), value: floatVal, adjustedRate: (floatVal * 1.02).toFixed(4) };
                }).filter(item => !isNaN(item.value));

                chart.data = chartData.reverse();
                setTransient(cacheKey, JSON.stringify(chartData.reverse()), 24 * HOUR_IN_SECONDS);
            };

            // Initial fetch
            fetchCurrencyData("<?php echo esc_js($atts['from']); ?>", "<?php echo esc_js($atts['to']); ?>", "<?php echo esc_js($atts['period']); ?>");

            document.getElementById('updateGraph').addEventListener('click', () => {
                let from = document.getElementById('fromCurrency').value;
                let to = document.getElementById('toCurrency').value;
                let period = document.getElementById('period').value;
                fetchCurrencyData(from, to, period);
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function historical_currency_graph_only($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'EUR',
        'period' => '1M' // Default period 1 month
    ), $atts);

    ob_start();
    ?>
    <div id="currency-chart" style="width: 100%; height: 500px;"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        am4core.ready(function() {
            am4core.useTheme(am4themes_animated);

            var chart = am4core.create("currency-chart", am4charts.XYChart);
            chart.scrollbarX = new am4core.Scrollbar();
            
            var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
            var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

            var series = chart.series.push(new am4charts.LineSeries());
            series.dataFields.valueY = "value";
            series.dataFields.dateX = "date";
            series.strokeWidth = 2;
            series.minBulletDistance = 15;

            // Add tooltip with exchange rate
            series.tooltipText = "{dateX.formatDate('yyyy-MM-dd')} (ব্যাংক রেট: [bold]{valueY}[/]) (এক্সচেঞ্জ রেট: [bold]{adjustedRate}[/])";
            series.tooltip.pointerOrientation = "vertical";
            series.tooltip.background.cornerRadius = 20;
            series.tooltip.background.fillOpacity = 0.5;
            series.tooltip.label.padding(12, 12, 12, 12);

            chart.cursor = new am4charts.XYCursor();
            chart.cursor.xAxis = dateAxis;
            chart.cursor.snapToSeries = series;

            chart.scrollbarY = new am4core.Scrollbar();
            chart.scrollbarY.parent = chart.leftAxesContainer;
            chart.scrollbarY.toBack();

            chart.scrollbarX = new am4core.Scrollbar();
            chart.scrollbarX.parent = chart.bottomAxesContainer;

            // Fetch and update the chart data
            const fetchCurrencyData = async (from, to, period) => {
                let cacheKey = `currency_data_${from}_${to}_${period}`;
                let cachedData = <?php echo json_encode(get_transient('currency_data_' . $atts['from'] . '_' . $atts['to'] . '_' . $atts['period'])); ?>;

                if (cachedData) {
                    chart.data = JSON.parse(cachedData);
                    return;
                }

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

                let response = await fetch(`https://currencies.apps.grandtrunk.net/getrange/${start}/${end}/${from}/${to}`);
                let data = await response.text();
                let lines = data.split('\n');
                let chartData = lines.map(line => {
                    let [date, value] = line.split(' ');
                    let floatVal = parseFloat(value);
                    return { date: new Date(date), value: floatVal, adjustedRate: (floatVal * 1.02).toFixed(4) };
                }).filter(item => !isNaN(item.value));

                chart.data = chartData.reverse();
                setTransient(cacheKey, JSON.stringify(chartData.reverse()), 24 * HOUR_IN_SECONDS);
            };

            // Initial fetch
            fetchCurrencyData("<?php echo esc_js($atts['from']); ?>", "<?php echo esc_js($atts['to']); ?>", "<?php echo esc_js($atts['period']); ?>");
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function setTransient($key, $value, $expiration) {
    set_transient($key, $value, $expiration);
}

add_shortcode('historical_currency_graph', 'historical_currency_graph');
add_shortcode('historical_currency_graph_only', 'historical_currency_graph_only');
?>