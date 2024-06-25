document.addEventListener("DOMContentLoaded", function () {
  const loader = document.querySelector(".cc-loader");
  const tableContainer = document.querySelector(".cc-table-container");
  const searchBar = document.getElementById("searchBar");
  const noResults = document.getElementById("noResults");
  const currencyTableBody = document.getElementById("currencyTableBody");
  const paginationContainer = document.getElementById("pagination");

  let allRows = [];
  let filteredRows = [];
  let currentPage = 1;
  const rowsPerPage = 6;

  function fetchData() {
    fetch(ccAjax.ajax_url + "?action=cc_fetch_currency_data")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const currencyData = data.data;
          allRows = currencyData.map(createRow);
          filteredRows = [...allRows];
          loader.style.display = "none";
          tableContainer.style.display = "block";
          displayPage(1);
          setupPagination();
        }
      });
  }

  function createRow(data) {
    const row = document.createElement("tr");
    row.innerHTML = `
          <td>${data.currency_name}</td>
          <td>${data.bank_rate}</td>
          <td>${data.exchange_rate}</td>
        `;
    return row;
  }

  function setupPagination() {
    paginationContainer.innerHTML = "";
    const pageCount = Math.ceil(filteredRows.length / rowsPerPage);

    if (pageCount > 1) {
      const prevButton = createPaginationButton(
        "← পূর্ববর্তী",
        currentPage === 1,
        () => {
          if (currentPage > 1) displayPage(currentPage - 1);
        }
      );

      const nextButton = createPaginationButton(
        "পরবর্তী →",
        currentPage === pageCount,
        () => {
          if (currentPage < pageCount) displayPage(currentPage + 1);
        }
      );

      paginationContainer.appendChild(prevButton);
      paginationContainer.appendChild(nextButton);
    }
  }

  function createPaginationButton(text, disabled, onClick) {
    const button = document.createElement("button");
    button.innerText = text;
    button.classList.add("page-btn");
    button.disabled = disabled;
    button.addEventListener("click", onClick);
    return button;
  }

  function displayPage(page) {
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    currencyTableBody.innerHTML = "";
    filteredRows
      .slice(start, end)
      .forEach((row) => currencyTableBody.appendChild(row));
    updatePaginationButtons();
  }

  function updatePaginationButtons() {
    const buttons = paginationContainer.querySelectorAll(".page-btn");
    if (buttons.length > 0) {
      buttons[0].disabled = currentPage === 1;
      buttons[1].disabled =
        currentPage === Math.ceil(filteredRows.length / rowsPerPage);
    }
  }

  searchBar.addEventListener("input", filterTable);

  function filterTable() {
    const filter = searchBar.value.toLowerCase();
    filteredRows = allRows.filter((row) => {
      const cell = row.querySelector("td:first-child");
      return cell ? cell.textContent.toLowerCase().includes(filter) : false;
    });

    noResults.style.display = filteredRows.length === 0 ? "block" : "none";
    displayPage(1);
    setupPagination();
  }

  fetchData();
});
