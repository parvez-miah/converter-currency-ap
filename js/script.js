jQuery(document).ready(function ($) {
  function convertToBengali(num) {
    const bengaliNums = ["০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯"];
    return num.toString().replace(/[0-9]/g, function (w) {
      return bengaliNums[+w];
    });
  }

  function addCommasToNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  function formatBengaliCurrency(amount) {
    const parts = amount.toFixed(2).split(".");
    const taka = convertToBengali(addCommasToNumber(parts[0]));
    const poisa = convertToBengali(parts[1]);
    return taka + " টাকা " + poisa + " পয়সা";
  }

  function updateConversion() {
    var fromCurrency = $("#cc-from-currency").val();
    var toCurrency = $("#cc-to-currency").val();
    var amount = $("#cc-amount").val();
    var date = $("#cc-date").val();
    var previewRate = $("#cc-preview-rate").val();

    if (amount <= 0) {
      alert("Amount must be greater than zero.");
      return;
    }

    $("#cc-loader").show();

    $.ajax({
      url: ccAjax.ajax_url,
      type: "POST",
      data: {
        action: "cc_convert_currency",
        from_currency: fromCurrency,
        to_currency: toCurrency,
        amount: amount,
        date: date,
        preview_rate: previewRate,
      },
      success: function (response) {
        $("#cc-loader").hide();
        if (response.success) {
          var bankRates = "";
          var quantities = [1, 5, 20, 50, 100];

          quantities.forEach(function (quantity) {
            const bankRateFormatted = formatBengaliCurrency(
              response.data.bank_rate * quantity
            );
            const exchangeRateFormatted = formatBengaliCurrency(
              response.data.exchange_rate * quantity
            );
            bankRates +=
              "<tr><td>" +
              convertToBengali(quantity) +
              " " +
              response.data.from_currency_name +
              "</td><td>" +
              bankRateFormatted +
              "</td><td>" +
              exchangeRateFormatted +
              "</td></tr>";
          });

          const convertedAmountFormatted = formatBengaliCurrency(
            response.data.converted_amount
          );

          let fromCurrencyName = response.data.from_currency_name;
          let toCurrencyName = response.data.to_currency_name;

          $("#cc-result").html(
            convertToBengali(amount) +
              " " +
              fromCurrencyName +
              " " +
              (fromCurrency === "fromCurrencyName" ? "ডলার" : "") +
              " = " +
              toCurrencyName +
              " " +
              convertedAmountFormatted +
              "।"
          );

          $("#cc-rate-table tbody").html(bankRates);

          const additionalInfo = `
                      <p>এখান থেকে আপনি জেনে নিতে পারবেন, ${fromCurrencyName} থেকে ${toCurrencyName} রূপান্তর করার পরিবর্তে আপনি কত টাকা পেতে পারেন সেই সংক্রান্ত যাবতীয় তথ্য। অর্থাৎ আজকের টাকার রেট হিসেবে আপনি জেনে নিতে পারবেন, ${fromCurrencyName} থেকে ${toCurrencyName} সংক্রান্ত যাবতীয় তথ্য।</p>
                      <p>সেজন্য আপনি যদি এই সংক্রান্ত যাবতীয় তথ্য জেনে নিতে চান এবং একই সাথে আজকের টাকার রেট সংক্রান্ত তথ্য জেনে নিতে চান তাহলে সেটি এখান থেকে জেনে নিতে পারেন।</p>
                      <h2>${fromCurrencyName} থেকে ${toCurrencyName} জন্য, আজকের টাকার মান কত সেটা নিয়ে কিছু প্রশ্ন উত্তর?</h2>
                      <h3>আজকের ${fromCurrencyName} থেকে ${toCurrencyName} টাকার রেট কত?</h3>
                      <h3> ১ ${fromCurrencyName} সমান কত ${toCurrencyName}?</h3>
                      <p>১ ${fromCurrencyName} সমান হবে, ${convertedAmountFormatted} ${toCurrencyName}।</p>
                       <h3>৫ ${fromCurrencyName} সমান কত ${toCurrencyName} হবে? </h3>
                      <p> ৫ ${fromCurrencyName} সমান হবে ${formatBengaliCurrency(
            response.data.converted_amount * 5
          )} ${toCurrencyName}।</p>
                      <p>একেবারে সর্বশেষ আপডেট অনুযায়ী এখানে বিস্তারিত আলোচনা করা হয়েছে, আজকের টাকা রেট হিসেবে, ${fromCurrencyName} থেকে ${toCurrencyName} ক্ষেত্রে আজকে টাকা রেট কত টাকা হবে সেই সংক্রান্ত তথ্য।</p>
                  `;

          $("#cc-additional-info").html(additionalInfo);

          const reverseRateFormatted = formatBengaliCurrency(
            response.data.reverse_rate
          );
          const reverseConvertedAmountFormatted = formatBengaliCurrency(
            response.data.reverse_converted_amount
          );

          $("#cc-additional-info").append(`
                      <h2>রিভার্স কনভার্সন</h2>
                      <h4>${toCurrencyName} থেকে ${fromCurrencyName}:</h4>
                        <ul>
                     <li> <p>১ ${toCurrencyName} = ${reverseRateFormatted} ${fromCurrencyName}</p></li>
                     <li> <p>${convertToBengali(
                       amount
                     )} ${toCurrencyName} = ${reverseConvertedAmountFormatted} ${fromCurrencyName}</p></li>
                      
                      </ul>
                  `);

          $("#cc-stats-7days").html(
            formatBengaliCurrency(response.data.historical_rates["7days"])
          );
          $("#cc-stats-15days").html(
            formatBengaliCurrency(response.data.historical_rates["15days"])
          );
          $("#cc-stats-30days").html(
            formatBengaliCurrency(response.data.historical_rates["30days"])
          );
          const increasedRate = formatBengaliCurrency(
            response.data.exchange_rate
          );
          $("#cc-increased-rate").html(
            `<b>এক্সচেঞ্জ রেট</b> : ${fromCurrencyName} = ${toCurrencyName} ${increasedRate}`
          );
        } else {
          $("#cc-result").html("Error: " + response.data);
        }
      },
      error: function () {
        $("#cc-loader").hide();
        $("#cc-result").html("An error occurred");
      },
    });
  }

  function reverseCurrencies() {
    const fromCurrency = $("#cc-from-currency").val();
    const toCurrency = $("#cc-to-currency").val();
    $("#cc-from-currency").val(toCurrency).change();
    $("#cc-to-currency").val(fromCurrency).change();
    $("#cc-loader").show();
  }

  $(
    "#cc-amount, #cc-from-currency, #cc-to-currency, #cc-date, #cc-preview-rate"
  ).change(updateConversion);
  $("#cc-convert").click(updateConversion);
  $("#cc-reverse").click(reverseCurrencies);

  $("#cc-print").click(function () {
    window.print();
  });

  updateConversion();
});
