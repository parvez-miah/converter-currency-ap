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

  function debounce(func, wait) {
    let timeout;
    return function (...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  function fetchData(page = 1, search = "") {
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
        search: search,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          currencyTableBody.innerHTML = data.data.html;
          prevPageButton.disabled = page === 1;
          nextPageButton.disabled = !data.data.has_more;
          pageIndicator.textContent = `Page ${page}`;
          if (search === "") {
            noResults.style.display = data.data.html ? "none" : "block";
          } else {
            noResults.style.display = "none";
          }
        }
        isFetching = false;
        loader.style.display = "none";
      })
      .catch(() => {
        isFetching = false;
        loader.style.display = "none";
      });
  }

  const debouncedFetchData = debounce(fetchData, 300);

  prevPageButton.addEventListener("click", function () {
    if (currentPage > 1) {
      currentPage--;
      debouncedFetchData(currentPage, searchBar.value);
    }
  });

  nextPageButton.addEventListener("click", function () {
    currentPage++;
    debouncedFetchData(currentPage, searchBar.value);
  });

  searchBar.addEventListener("input", function () {
    currentPage = 1;
    debouncedFetchData(currentPage, searchBar.value);
  });

  searchBar.addEventListener("change", function () {
    if (searchBar.value === "") {
      currentPage = 1;
      fetchData(currentPage, "");
    }
  });

  fetchData();
});
