document.addEventListener("DOMContentLoaded", function () {
  var loader = document.querySelector(".cc-loader");
  var tableContainer = document.querySelector(".cc-table-container");
  var searchBar = document.getElementById("searchBar");
  var noResults = document.getElementById("noResults");
  var currencyTableBody = document.getElementById("currencyTableBody");
  var loadMoreButton = document.getElementById("loadMore");
  var paginationContainer = document.getElementById("pagination");

  var initialRows = Array.from(currencyTableBody.querySelectorAll("tr"));
  var remainingData = JSON.parse(
    document.getElementById("currencyData").textContent
  );
  var allRows = initialRows.slice();
  var currentPage = 1;
  var rowsPerPage = 6;

  loadMoreButton.addEventListener("click", function () {
    loader.style.display = "block";
    loadMoreButton.style.display = "none";

    setTimeout(function () {
      var fragment = document.createDocumentFragment();
      remainingData.forEach(function (data) {
        var row = document.createElement("tr");
        var nameCell = document.createElement("td");
        nameCell.textContent = data.currency_name;
        var bankRateCell = document.createElement("td");
        bankRateCell.textContent = data.bank_rate;
        var exchangeRateCell = document.createElement("td");
        exchangeRateCell.textContent = data.exchange_rate;
        row.appendChild(nameCell);
        row.appendChild(bankRateCell);
        row.appendChild(exchangeRateCell);
        fragment.appendChild(row);
        allRows.push(row);
      });

      currencyTableBody.appendChild(fragment);
      loader.style.display = "none";
      setupPagination(allRows);
      displayPage(1);
    }, 500); // Simulate loading time
  });

  searchBar.addEventListener("input", filterTable);

  function filterTable() {
    var filter = searchBar.value.toLowerCase();
    var filteredRows = allRows.filter(function (row) {
      var cell = row.querySelector("td:first-child");
      if (cell) {
        var txtValue = cell.textContent || cell.innerText;
        return txtValue.toLowerCase().includes(filter);
      }
      return false;
    });

    noResults.style.display = filteredRows.length === 0 ? "block" : "none";
    displayPage(1, filteredRows);
    setupPagination(filteredRows);
  }

  function setupPagination(filteredRows) {
    var rowsToPaginate = filteredRows || allRows;
    var pageCount = Math.ceil(rowsToPaginate.length / rowsPerPage);
    paginationContainer.innerHTML = "";

    if (pageCount > 1) {
      var prevButton = document.createElement("button");
      prevButton.innerText = "Previous";
      prevButton.classList.add("page-btn");
      prevButton.disabled = currentPage === 1;
      prevButton.addEventListener("click", function () {
        if (currentPage > 1) {
          displayPage(currentPage - 1, rowsToPaginate);
          currentPage--;
          updatePaginationButtons();
        }
      });

      var nextButton = document.createElement("button");
      nextButton.innerText = "Next";
      nextButton.classList.add("page-btn");
      nextButton.disabled = currentPage === pageCount;
      nextButton.addEventListener("click", function () {
        if (currentPage < pageCount) {
          displayPage(currentPage + 1, rowsToPaginate);
          currentPage++;
          updatePaginationButtons();
        }
      });

      paginationContainer.appendChild(prevButton);
      paginationContainer.appendChild(nextButton);
    }
  }

  function updatePaginationButtons() {
    var buttons = paginationContainer.querySelectorAll(".page-btn");
    buttons[0].disabled = currentPage === 1;
    buttons[1].disabled =
      currentPage === Math.ceil(allRows.length / rowsPerPage);
  }

  function displayPage(page, filteredRows) {
    var start = (page - 1) * rowsPerPage;
    var end = start + rowsPerPage;
    var rowsToDisplay = filteredRows || allRows;

    allRows.forEach(function (row) {
      row.style.display = "none";
    });

    rowsToDisplay.slice(start, end).forEach(function (row) {
      row.style.display = "";
    });

    currentPage = page;
    updatePaginationButtons();
  }

  // Initial load
  loader.style.display = "none";
  tableContainer.style.display = "block";
  displayPage(1);
  setupPagination(initialRows);
});
