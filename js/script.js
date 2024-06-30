jQuery(document).ready(function ($) {
  const bengaliNums = ["০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯"];

  const convertToBengali = (num) =>
    num.toString().replace(/[0-9]/g, (w) => bengaliNums[+w]);

  const addCommasToNumber = (num) =>
    num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");

  const formatBengaliCurrency = (amount) => {
    const [taka, poisa] = amount.toFixed(2).split(".");
    return `${convertToBengali(
      addCommasToNumber(taka)
    )} টাকা ${convertToBengali(poisa)} পয়সা`;
  };

  const updateConversion = () => {
    const fromCurrency = $("#cc-from-currency").val();
    const toCurrency = $("#cc-to-currency").val();
    const amount = parseFloat($("#cc-amount").val());
    const date = $("#cc-date").val();
    const previewRate = $("#cc-preview-rate").val();

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
      success: (response) => {
        $("#cc-loader").hide();
        if (response.success) {
          const quantities = [1, 5, 20, 50, 100];
          const bankRates = quantities
            .map(
              (quantity) => `
            <tr>
              <td>${convertToBengali(quantity)} ${
                response.data.from_currency_name
              }</td>
              <td>${formatBengaliCurrency(
                response.data.bank_rate * quantity
              )}</td>
              <td>${formatBengaliCurrency(
                response.data.exchange_rate * quantity
              )}</td>
            </tr>
          `
            )
            .join("");

          $("#cc-result").html(`
            ${convertToBengali(amount)} ${response.data.from_currency_name} = ${
            response.data.to_currency_name
          } ${formatBengaliCurrency(response.data.converted_amount)}।
          `);

          $("#cc-rate-table tbody").html(bankRates);

          $("#cc-additional-info").html(`
            <p>এখান থেকে আপনি জেনে নিতে পারবেন, ${
              response.data.from_currency_name
            } থেকে ${
            response.data.to_currency_name
          } রূপান্তর করার পরিবর্তে আপনি কত টাকা পেতে পারেন সেই সংক্রান্ত যাবতীয় তথ্য। অর্থাৎ আজকের টাকার রেট হিসেবে আপনি জেনে নিতে পারবেন, ${
            response.data.from_currency_name
          } থেকে ${response.data.to_currency_name} সংক্রান্ত যাবতীয় তথ্য।</p>
            <p>সেজন্য আপনি যদি এই সংক্রান্ত যাবতীয় তথ্য জেনে নিতে চান এবং একই সাথে আজকের টাকার রেট সংক্রান্ত তথ্য জেনে নিতে চান তাহলে সেটি এখান থেকে জেনে নিতে পারেন।</p>
            <h2>${response.data.from_currency_name} থেকে ${
            response.data.to_currency_name
          } জন্য, আজকের টাকার মান কত সেটা নিয়ে কিছু প্রশ্ন উত্তর?</h2>
            <h3>আজকের ${response.data.from_currency_name} থেকে ${
            response.data.to_currency_name
          } টাকার রেট কত?</h3>
            <h3> ১ ${response.data.from_currency_name} সমান কত ${
            response.data.to_currency_name
          }?</h3>
            <p>১ ${
              response.data.from_currency_name
            } সমান হবে, ${formatBengaliCurrency(
            response.data.converted_amount
          )} ${response.data.to_currency_name}।</p>
            <h3>৫ ${response.data.from_currency_name} সমান কত ${
            response.data.to_currency_name
          } হবে? </h3>
            <p> ৫ ${
              response.data.from_currency_name
            } সমান হবে ${formatBengaliCurrency(
            response.data.converted_amount * 5
          )} ${response.data.to_currency_name}।</p>
            <p>একেবারে সর্বশেষ আপডেট অনুযায়ী এখানে বিস্তারিত আলোচনা করা হয়েছে, আজকের টাকা রেট হিসেবে, ${
              response.data.from_currency_name
            } থেকে ${
            response.data.to_currency_name
          } ক্ষেত্রে আজকে টাকা রেট কত টাকা হবে সেই সংক্রান্ত তথ্য।</p>
          `);

          $("#cc-additional-info").append(`
            <h2>বিপরীত মুদ্রা রেট</h2>
            <h4>${response.data.to_currency_name} থেকে ${
            response.data.from_currency_name
          }:</h4>
            <ul>
              <li><p>১ ${
                response.data.to_currency_name
              } = ${formatBengaliCurrency(response.data.reverse_rate)} ${
            response.data.from_currency_name
          }</p></li>
              <li><p>${convertToBengali(amount)} ${
            response.data.to_currency_name
          } = ${formatBengaliCurrency(
            response.data.reverse_converted_amount
          )} ${response.data.from_currency_name}</p></li>
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
          $("#cc-increased-rate").html(
            `<b>এক্সচেঞ্জ রেট</b> : ${response.data.from_currency_name} = ${
              response.data.to_currency_name
            } ${formatBengaliCurrency(response.data.exchange_rate)}`
          );
        } else {
          $("#cc-result").html("Error: " + response.data);
        }
      },
      error: () => {
        $("#cc-loader").hide();
        $("#cc-result").html("An error occurred");
      },
    });
  };

  const reverseCurrencies = () => {
    const fromCurrency = $("#cc-from-currency").val();
    const toCurrency = $("#cc-to-currency").val();
    $("#cc-from-currency").val(toCurrency).change();
    $("#cc-to-currency").val(fromCurrency).change();
    $("#cc-loader").show();
  };

  const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  };

  const debouncedUpdateConversion = debounce(updateConversion, 300);

  $(
    "#cc-amount, #cc-from-currency, #cc-to-currency, #cc-date, #cc-preview-rate"
  ).change(debouncedUpdateConversion);
  $("#cc-convert").click(updateConversion);
  $("#cc-reverse").click(reverseCurrencies);
  $("#cc-print").click(() => window.print());

  updateConversion();
});
