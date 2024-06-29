document.addEventListener("DOMContentLoaded", function () {
  const searchBar = document.getElementById("specificSearchBar");
  const noResults = document.getElementById("specificNoResults");
  const currencyTableBody = document.getElementById(
    "specificCurrencyTableBody"
  );
  const prevPageButton = document.getElementById("specificPrevPage");
  const nextPageButton = document.getElementById("specificNextPage");
  const pageIndicator = document.getElementById("specificPageIndicator");
  const loader = document.getElementById("specificLoader");

  let currentPage = 1;
  const rowsPerPage = 7;
  let isFetching = false;

  const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  };

  const fetchData = (page = 1, search = "") => {
    if (isFetching) return;
    isFetching = true;
    loader.style.display = "block";

    fetch(ccAjax.ajax_url + "?action=load_specific_currency_table", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        page: page,
        per_page: rowsPerPage,
        search: search.trim(),
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          currencyTableBody.innerHTML = data.data.html;
          prevPageButton.disabled = page === 1;
          nextPageButton.disabled = !data.data.has_more;
          pageIndicator.textContent = `Page ${page}`;
          noResults.style.display =
            data.data.html.trim() === "" ? "block" : "none";
        }
        isFetching = false;
        loader.style.display = "none";
      })
      .catch(() => {
        isFetching = false;
        loader.style.display = "none";
      });
  };

  const debouncedFetchData = debounce(fetchData, 300);

  prevPageButton.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      debouncedFetchData(currentPage, searchBar.value);
    }
  });

  nextPageButton.addEventListener("click", () => {
    currentPage++;
    debouncedFetchData(currentPage, searchBar.value);
  });

  searchBar.addEventListener("input", () => {
    currentPage = 1;
    debouncedFetchData(currentPage, searchBar.value);
  });

  searchBar.addEventListener("change", () => {
    const searchText = searchBar.value.trim();
    if (searchText === "") {
      currentPage = 1;
      fetchData(currentPage, "");
    } else {
      debouncedFetchData(currentPage, searchText);
    }
  });

  fetchData();
});
