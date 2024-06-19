jQuery(document).ready(function ($) {
  function updateConversion() {
    var fromCurrency = $("#cc-from-currency").val();
    var toCurrency = $("#cc-to-currency").val();
    var amount = $("#cc-amount").val();

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
        if (response.success) {
          var bankRates = "";
          var quantities = [1, 5, 20, 50, 100];

          quantities.forEach(function (quantity) {
            bankRates +=
              "<tr><td>" +
              quantity +
              "</td><td>" +
              (response.data.bank_rate * quantity).toFixed(2) +
              "</td><td>" +
              (response.data.exchange_rate * quantity).toFixed(2) +
              "</td></tr>";
          });

          $("#cc-result").html(
            fromCurrency +
              " ১ টাকা " +
              toCurrency +
              " " +
              response.data.converted_amount.toFixed(2) +
              " টাকা"
          );
          $("#cc-rate-table tbody").html(bankRates);
        } else {
          $("#cc-result").html("Error: " + response.data);
        }
      },
      error: function () {
        $("#cc-result").html("An error occurred");
      },
    });
  }

  $("#cc-amount, #cc-from-currency, #cc-to-currency").change(updateConversion);
  $("#cc-convert").click(updateConversion);

  // Initial conversion on load
  updateConversion();
});
