document.addEventListener("DOMContentLoaded", function () {
  const searchBar = document.getElementById("searchBar");
  const noResults = document.getElementById("noResults");
  const currencyTableBody = document.getElementById("currencyTableBody");
  const prevPageButton = document.getElementById("prevPage");
  const nextPageButton = document.getElementById("nextPage");
  const pageIndicator = document.getElementById("pageIndicator");
  const loader = document.getElementById("loader");

  let currentPage = 1;
  const rowsPerPage = 7;
  let isFetching = false;

  function fetchData(page = 1) {
    if (isFetching) return;
    isFetching = true;
    loader.style.display = "block";

    fetch(ccAjax.ajax_url + "?action=load_currency_table", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        page: page,
        per_page: rowsPerPage,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          currencyTableBody.innerHTML = data.data.html;
          prevPageButton.disabled = page === 1;
          nextPageButton.disabled = !data.data.has_more;
          pageIndicator.textContent = `Page ${page}`;
        }
        isFetching = false;
        loader.style.display = "none";
      })
      .catch(() => {
        isFetching = false;
        loader.style.display = "none";
      });
  }

  prevPageButton.addEventListener("click", function () {
    if (currentPage > 1) {
      currentPage--;
      fetchData(currentPage);
    }
  });

  nextPageButton.addEventListener("click", function () {
    currentPage++;
    fetchData(currentPage);
  });

  fetchData();

  searchBar.addEventListener("input", filterTable);

  function filterTable() {
    const filter = searchBar.value.toLowerCase();
    const rows = currencyTableBody.querySelectorAll("tr");
    let hasVisibleRows = false;

    rows.forEach((row) => {
      const cell = row.querySelector("td:first-child");
      if (cell && cell.textContent.toLowerCase().includes(filter)) {
        row.style.display = "";
        hasVisibleRows = true;
      } else {
        row.style.display = "none";
      }
    });

    noResults.style.display = hasVisibleRows ? "none" : "block";
  }
});
