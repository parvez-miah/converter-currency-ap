<?php

function historical_currency_graph($atts) {
    $atts = shortcode_atts(array(
        'from' => 'USD',
        'to' => 'EUR',
        'period' => '1M' // Default period 1 month
    ), $atts);

    $api_key = '6AJXOO6FETV48T49'; // Replace with your Alpha Vantage API key

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
            series.tooltipText = "{value}";
            series.strokeWidth = 2;
            series.minBulletDistance = 15;

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

            // Add data
            fetch(`https://www.alphavantage.co/query?function=FX_DAILY&from_symbol=<?php echo $atts['from']; ?>&to_symbol=<?php echo $atts['to']; ?>&apikey=<?php echo $api_key; ?>`)
                .then(response => response.json())
                .then(data => {
                    let chartData = [];
                    let timeSeries = data['Time Series FX (Daily)'];
                    for (let date in timeSeries) {
                        chartData.push({
                            date: new Date(date),
                            value: parseFloat(timeSeries[date]['4. close'])
                        });
                    }
                    chart.data = chartData.reverse();
                });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('historical_currency_graph', 'historical_currency_graph');
?>