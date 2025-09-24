// ================================
// Product Dashboard JS
// ================================
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("productSearch");
    const table = document.querySelector(".products-table tbody");
    const rows = Array.from(table.querySelectorAll("tr"));
    const btnSortUp = document.querySelector(".btn-sort-up");
    const btnSortDown = document.querySelector(".btn-sort-down");
    const btnDownloadAll = document.querySelector(".btn-download-all");
    const paginationLinks = document.querySelector(".pagination-links");
    const entriesInfo = document.querySelector(".entries-info");

    let currentPage = 1;
    const rowsPerPage = 5;

    // Cache initially
    let visibleRows = rows;

    // ================================
    // Live Search
    // ================================
    function sanitizeInput(input) {
        return input.replace(/[<>\"']/g, '');
    }

    searchInput.addEventListener("keyup", function () {
        const value = sanitizeInput(this.value.toLowerCase());
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(value) ? "" : "none";
        });
        paginate(); // re-run pagination after search
    });

    // ================================
    // Sort Functions
    // ================================
    function sortTable(order = "asc") {
        const sorted = [...rows].sort((a, b) => {
            const nameA = a.cells[1].innerText.toLowerCase();
            const nameB = b.cells[1].innerText.toLowerCase();
            return order === "asc"
                ? nameA.localeCompare(nameB)
                : nameB.localeCompare(nameA);
        });
        table.innerHTML = "";
        sorted.forEach(row => table.appendChild(row));
        paginate();
    }

    btnSortUp.addEventListener("click", () => sortTable("asc"));
    btnSortDown.addEventListener("click", () => sortTable("desc"));

    // ================================
    // Download Table as CSV
    // ================================
    btnDownloadAll.addEventListener("click", () => {
        let csv = [];
        const rows = document.querySelectorAll("table tr");
        rows.forEach(row => {
            let cols = row.querySelectorAll("td, th");
            let rowData = [];
            cols.forEach(col => rowData.push(col.innerText));
            csv.push(rowData.join(","));
        });
        downloadCSV(csv.join("\n"), "products.csv");
    });

    function downloadCSV(csv, filename) {
        let csvFile = new Blob([csv], { type: "text/csv" });
        let link = document.createElement("a");
        link.download = filename;
        link.href = window.URL.createObjectURL(csvFile);
        link.style.display = "none";
        document.body.appendChild(link);
        link.click();
    }

    // ================================
    // Pagination
    // ================================
    function paginate() {
        // Use cached visibleRows
        const totalPages = Math.ceil(visibleRows.length / rowsPerPage);

        visibleRows.forEach((row, index) => {
            row.style.display =
                index >= (currentPage - 1) * rowsPerPage &&
                index < currentPage * rowsPerPage
                    ? ""
                    : "none";
        });

        // Update pagination UI
        paginationLinks.innerHTML = "";
        if (totalPages > 1) {
            let prev = document.createElement("button");
            prev.innerText = "«";
            prev.className = "page-link";
            prev.disabled = currentPage === 1;
            prev.addEventListener("click", () => {
                if (currentPage > 1) {
                    currentPage--;
                    paginate();
                }
            });
            paginationLinks.appendChild(prev);

            for (let i = 1; i <= totalPages; i++) {
                let btn = document.createElement("button");
                btn.innerText = i;
                btn.className = "page-link" + (i === currentPage ? " active" : "");
                btn.addEventListener("click", () => {
                    currentPage = i;
                    paginate();
                });
                paginationLinks.appendChild(btn);
            }

            let next = document.createElement("button");
            next.innerText = "»";
            next.className = "page-link";
            next.disabled = currentPage === totalPages;
            next.addEventListener("click", () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    paginate();
                }
            });
            paginationLinks.appendChild(next);
        }

        // Update info text
        const start = (currentPage - 1) * rowsPerPage + 1;
        const end = Math.min(currentPage * rowsPerPage, visibleRows.length);
        entriesInfo.innerText = `Showing ${start} to ${end} of ${visibleRows.length} entries`;
    }

    paginate();

    // ================================
    // Action Buttons
    // ================================
    document.querySelectorAll(".btn-view").forEach(btn => {
        btn.addEventListener("click", () =>
            alert("📄 Viewing product details...")
        );
    });

    document.querySelectorAll(".btn-update").forEach(btn => {
        btn.addEventListener("click", () =>
            alert("✏️ Update product form goes here...")
        );
    });

    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete')) {
            e.preventDefault();
            if (confirm("⚠️ Are you sure you want to delete this product?")) {
                // Add loading state
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                btn.disabled = true;
                // Submit form or AJAX
                this.closest('form').submit();
            }
        }
    });
});

// ...existing code...

// ================================
// Live Search
// ================================
function sanitizeInput(input) {
    return input.replace(/[<>\"']/g, ''); // Basic sanitization to prevent XSS
}

searchInput.addEventListener("keyup", function () {
    const query = sanitizeInput(this.value.toLowerCase());
    const rows = Array.from(table.querySelectorAll("tr"));
    let visibleRows = [];

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = "";
            visibleRows.push(row);
        } else {
            row.style.display = "none";
        }
    });

    // Handle case where no rows match
    if (visibleRows.length === 0) {
        // Optionally, show a "no results" message
        console.log("No matching products found.");
    }

    // Update pagination after search
    currentPage = 1;
    paginate(visibleRows.length > 0 ? visibleRows : rows);
});
// ...existing code...

// ================================
// Sort Functions
// ================================
function sortTable(order = "asc") {
    const sorted = [...rows].sort((a, b) => {
        const nameA = a.cells[1].innerText.toLowerCase();
        const nameB = b.cells[1].innerText.toLowerCase();
        return order === "asc"
            ? nameA.localeCompare(nameB)
            : nameB.localeCompare(nameA);
    });
    // Instead of clearing innerHTML, reorder by appending sorted rows
    sorted.forEach(row => table.appendChild(row));
    paginate();
}
document.addEventListener('DOMContentLoaded', function() {
    const matBtn = document.getElementById('toggle-materials');
    const matSection = document.getElementById('materials-section');
    if (matBtn && matSection) {
        matBtn.addEventListener('click', function() {
            if (matSection.style.display === 'none') {
                matSection.style.display = '';
                matBtn.textContent = 'Hide Materials';
            } else {
                matSection.style.display = 'none';
                matBtn.textContent = 'Show Materials';
            }
        });
    }

    const inkBtn = document.getElementById('toggle-inks');
    const inkSection = document.getElementById('inks-section');
    if (inkBtn && inkSection) {
        inkBtn.addEventListener('click', function() {
            if (inkSection.style.display === 'none') {
                inkSection.style.display = '';
                inkBtn.textContent = 'Hide Inks';
            } else {
                inkSection.style.display = 'none';
                inkBtn.textContent = 'Show Inks';
            }
        });
    }
});


// ...existing code...
// ...existing code...