document.addEventListener("DOMContentLoaded", function () {
  const fromCurrencyDropdown = document.getElementById("cc-from-currency");
  const toCurrencyDropdown = document.getElementById("cc-to-currency");
  const titleElement = document.getElementById("cc-title");

  function updateTitle() {
    const fromCurrency =
      fromCurrencyDropdown.options[fromCurrencyDropdown.selectedIndex].text;
    const toCurrency =
      toCurrencyDropdown.options[toCurrencyDropdown.selectedIndex].text;
    titleElement.innerHTML = `আজকের টাকার রেট : ${fromCurrency} হতে ${toCurrency}`;
  }

  function fetchCurrencyData(from, to, period) {
    let endDate = new Date();
    let startDate = new Date();

    switch (period) {
      case "1M":
        startDate.setMonth(startDate.getMonth() - 1);
        break;
      case "3M":
        startDate.setMonth(startDate.getMonth() - 3);
        break;
      case "6M":
        startDate.setMonth(startDate.getMonth() - 6);
        break;
      case "1Y":
        startDate.setFullYear(startDate.getFullYear() - 1);
        break;
    }

    let start = startDate.toISOString().split("T")[0];
    let end = endDate.toISOString().split("T")[0];

    fetch(
      `https://currencies.apps.grandtrunk.net/getrange/${start}/${end}/${from}/${to}`
    )
      .then((response) => response.text())
      .then((data) => {
        let lines = data.split("\n");
        let chartData = lines
          .map((line) => {
            let [date, value] = line.split(" ");
            let floatVal = parseFloat(value);
            return {
              date: new Date(date),
              value: floatVal,
              adjustedRate: (floatVal * 1.02).toFixed(4),
            };
          })
          .filter((item) => !isNaN(item.value));

        updateGraph(chartData.reverse());
      });
  }

  function updateGraph(data) {
    am4core.ready(function () {
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

      series.tooltipText =
        "{dateX.formatDate('yyyy-MM-dd')} (ব্যাংক রেট: [bold]{valueY}[/]) (এক্সচেঞ্জ রেট: [bold]{adjustedRate}[/])";
      series.tooltip.pointerOrientation = "vertical";
      series.tooltip.background.cornerRadius = 20;
      series.tooltip.background.fillOpacity = 0.5;
      series.tooltip.label.padding(12, 12, 12, 12);

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
    const period = "1M"; // Default period, can be changed based on user input if needed

    fetchCurrencyData(fromCurrency, toCurrency, period);
  }

  fromCurrencyDropdown.addEventListener("change", () => {
    updateTitle();
    updateGraphContainer();
  });

  toCurrencyDropdown.addEventListener("change", () => {
    updateTitle();
    updateGraphContainer();
  });

  updateGraphContainer(); // Initial load
});
