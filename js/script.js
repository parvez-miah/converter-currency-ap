jQuery(document).ready(function ($) {
  function convertToBengali(num) {
    const bengaliNums = ["০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯"];
    return num.toString().replace(/[0-9]/g, function (w) {
      return bengaliNums[+w];
    });
  }

  function formatBengaliCurrency(amount) {
    const parts = amount.toFixed(2).split(".");
    const taka = convertToBengali(parts[0]);
    const poisa = convertToBengali(parts[1]);
    return taka + " টাকা " + poisa + " পয়সা";
  }

  function updateConversion() {
    var fromCurrency = $("#cc-from-currency").val();
    var toCurrency = $("#cc-to-currency").val();
    var amount = $("#cc-amount").val();

    $("#cc-loader").show();

    $.ajax({
      url: ccAjax.ajax_url,
      type: "post",
      data: {
        action: "cc_convert_currency",
        from_currency: fromCurrency,
        to_currency: toCurrency,
        amount: amount,
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
              "</td><td>" +
              bankRateFormatted +
              "</td><td>" +
              exchangeRateFormatted +
              "</td></tr>";
          });

          const convertedAmountFormatted = formatBengaliCurrency(
            response.data.converted_amount
          );

          $("#cc-result").html(
            fromCurrency +
              " ১ টাকা = " +
              toCurrency +
              " " +
              convertedAmountFormatted
          );
          $("#cc-rate-table tbody").html(bankRates);
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

  $("#cc-amount, #cc-from-currency, #cc-to-currency").change(updateConversion);
  $("#cc-convert").click(updateConversion);

  // Initial conversion on load
  updateConversion();
});
