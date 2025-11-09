document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on currency filter change
    const currencySelect = document.getElementById('currency');

    if (currencySelect) {
        currencySelect.addEventListener('change', function() {
            this.form.submit();
        });
    }

    // Table row hover effect enhancement
    const tableRows = document.querySelectorAll('.currency-rate-table tbody tr');

    tableRows.forEach(function(row) {
        row.style.cursor = 'default';
    });

    // Smooth scroll to table after filter
    if (window.location.search.includes('currency=') || window.location.search.includes('search=')) {
        const table = document.querySelector('.currency-rate-table');

        if (table) {
            setTimeout(function() {
                table.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }

    // Currency rates modal functionality
    const currencyRatesBtn = document.querySelector('.currency-rates-btn');
    const currencyRatesModal = document.getElementById('currencyRatesModal');

    if (currencyRatesBtn && currencyRatesModal) {
        if (typeof $ !== 'undefined' && $.fn.modal) {
            currencyRatesBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                $('#currencyRatesModal').modal('show');
            });

            const closeButtons = currencyRatesModal.querySelectorAll('[data-dismiss="modal"]');

            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $('#currencyRatesModal').modal('hide');
                });
            });
        } 
    }

    // Move button next to the product price
    const productPriceContainer = document.querySelector('.product-prices .current-price');

    if (productPriceContainer && currencyRatesBtn) {
        productPriceContainer.appendChild(currencyRatesBtn);

        currencyRatesBtn.style.display = 'inline-block';
    }

    // Currency table filtering, sorting, and pagination
    const currencySearch = document.getElementById('currency-search');
    const clearSearchBtn = document.getElementById('clear-search');
    const currencyTable = document.getElementById('currency-rates-table');
    const searchResultsCount = document.getElementById('search-results-count');
    const noResultsMessage = document.getElementById('no-results-message');
    const paginationControls = document.getElementById('pagination-controls');
    const paginationInfo = document.getElementById('pagination-info');

    if (currencySearch && currencyTable) {
        let currentSortColumn = null;
        let currentSortDirection = 'asc';
        let currentPage = 1;
        let itemsPerPage = window.currencyRatesPaginationConfig ? window.currencyRatesPaginationConfig.itemsPerPage : 10;

        function getVisibleRows() {
            const searchTerm = currencySearch.value.toLowerCase().trim();
            const rows = Array.from(currencyTable.querySelectorAll('tbody tr'));

            return rows.filter(function(row) {
                const currencyCode = row.getAttribute('data-currency-code').toLowerCase();
                const currencyName = row.getAttribute('data-currency-name');

                return currencyCode.includes(searchTerm) || currencyName.includes(searchTerm);
            });
        }

        function updatePagination(visibleRows) {
            const totalPages = Math.ceil(visibleRows.length / itemsPerPage);

            // Ensure current page is within bounds
            if (currentPage > totalPages) {
                currentPage = Math.max(1, totalPages);
            }

            // Hide all rows first
            const allRows = currencyTable.querySelectorAll('tbody tr');

            allRows.forEach(function(row) {
                row.style.display = 'none';
            });

            // Show only rows for current page
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const rowsToShow = visibleRows.slice(startIndex, endIndex);

            rowsToShow.forEach(function(row) {
                row.style.display = '';
            });

            // Update pagination controls
            renderPaginationControls(totalPages, visibleRows.length);

            // Update info
            if (paginationInfo && visibleRows.length > 0) {
                const showing = Math.min(endIndex, visibleRows.length);

                paginationInfo.textContent = 'Showing ' + (startIndex + 1) + '-' + showing + ' of ' + visibleRows.length;
            }

            // Handle no results
            if (noResultsMessage) {
                if (visibleRows.length === 0) {
                    noResultsMessage.style.display = 'block';
                    currencyTable.style.display = 'none';

                    if (paginationControls)  {
                        paginationControls.parentElement.parentElement.style.display = 'none';
                    }
                } else {
                    noResultsMessage.style.display = 'none';
                    currencyTable.style.display = 'table';

                    if (paginationControls && totalPages > 1) {
                        paginationControls.parentElement.parentElement.style.display = 'block';
                    } else if (paginationControls) {
                        paginationControls.parentElement.parentElement.style.display = 'none';
                    }
                }
            }
        }

        function renderPaginationControls(totalPages) {
            if (!paginationControls) {
                return;
            } 

            paginationControls.innerHTML = '';

            if (totalPages <= 1) {
                return;
            }

            // Previous button
            if (currentPage > 1) {
                const prevLi = document.createElement('li');

                prevLi.className = 'page-item';

                const prevLink = document.createElement('a');

                prevLink.className = 'page-link';
                prevLink.href = 'javascript:void(0)';
                prevLink.innerHTML = '<i class="material-icons">chevron_left</i>';
                prevLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentPage--;
                    filterTable();
                });

                prevLi.appendChild(prevLink);
                paginationControls.appendChild(prevLi);
            }

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage || i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    const pageLi = document.createElement('li');
                    pageLi.className = 'page-item' + (i === currentPage ? ' active' : '');

                    const pageLink = document.createElement('a');
                    pageLink.className = 'page-link';
                    pageLink.href = 'javascript:void(0)';
                    pageLink.textContent = i;
                    pageLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = i;
                        filterTable();
                    });
                    pageLi.appendChild(pageLink);
                    paginationControls.appendChild(pageLi);
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    const dotsLi = document.createElement('li');
                    dotsLi.className = 'page-item disabled';
                    const dotsSpan = document.createElement('span');
                    dotsSpan.className = 'page-link';
                    dotsSpan.textContent = '...';
                    dotsLi.appendChild(dotsSpan);
                    paginationControls.appendChild(dotsLi);
                }
            }

            // Next button
            if (currentPage < totalPages) {
                const nextLi = document.createElement('li');
                nextLi.className = 'page-item';
                const nextLink = document.createElement('a');
                nextLink.className = 'page-link';
                nextLink.href = 'javascript:void(0)';
                nextLink.innerHTML = '<i class="material-icons">chevron_right</i>';
                nextLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentPage++;
                    filterTable();
                });
                nextLi.appendChild(nextLink);
                paginationControls.appendChild(nextLi);
            }
        }

        function filterTable() {
            const searchTerm = currencySearch.value.toLowerCase().trim();
            const visibleRows = getVisibleRows();

            // Update results count
            if (searchResultsCount) {
                if (searchTerm) {
                    searchResultsCount.textContent = visibleRows.length + ' of ' + currencyTable.querySelectorAll('tbody tr').length + ' currencies shown';
                } else {
                    searchResultsCount.textContent = '';
                }
            }

            // Update pagination
            updatePagination(visibleRows);
        }

        // Sort function
        function sortTable(column, direction) {
            const tbody = currencyTable.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort(function(a, b) {
                let aValue, bValue;

                if (column === 'currency') {
                    aValue = a.getAttribute('data-currency-code');
                    bValue = b.getAttribute('data-currency-code');
                } else if (column === 'rate') {
                    aValue = parseFloat(a.getAttribute('data-rate'));
                    bValue = parseFloat(b.getAttribute('data-rate'));
                } else if (column === 'price') {
                    aValue = parseFloat(a.getAttribute('data-price'));
                    bValue = parseFloat(b.getAttribute('data-price'));
                }

                if (typeof aValue === 'string') {
                    aValue = aValue.toLowerCase();
                    bValue = bValue.toLowerCase();
                }

                if (direction === 'asc') {
                    return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
                } else {
                    return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
                }
            });

            tbody.innerHTML = '';

            rows.forEach(function(row) {
                tbody.appendChild(row);
            });

            const sortIcons = currencyTable.querySelectorAll('.sort-icon');

            sortIcons.forEach(function(icon) {
                icon.textContent = 'unfold_more';
            });

            const activeHeader = currencyTable.querySelector('th[data-sort="' + column + '"]');

            if (activeHeader) {
                const icon = activeHeader.querySelector('.sort-icon');

                if (icon) {
                    icon.textContent = direction === 'asc' ? 'arrow_upward' : 'arrow_downward';
                }
            }

            // Reset to page 1 after sorting and re-apply pagination
            currentPage = 1;
            filterTable();
        }

        // Search input event (prevent adding item to the cart when the input is submitted)
        if (currencySearch) {
            currencySearch.addEventListener('input', function() {
                currentPage = 1; // Reset to page 1 when searching
                filterTable();
            });

            currencySearch.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });

            currencySearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        }

        // Clear search button
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                currencySearch.value = '';
                currentPage = 1; // Reset to page 1
                filterTable();
            });
        }

        // Sortable headers
        const sortableHeaders = currencyTable.querySelectorAll('th.sortable');
        
        sortableHeaders.forEach(function(header) {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-sort');

                if (currentSortColumn === column) {
                    // Toggle direction
                    currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    // New column, default to ascending
                    currentSortColumn = column;
                    currentSortDirection = 'asc';
                }

                sortTable(currentSortColumn, currentSortDirection);
            });
        });

        // Initialize pagination on modal open
        if (currencyRatesModal) {
            $('#currencyRatesModal').on('shown.bs.modal', function () {
                currentPage = 1;
                filterTable();
            });
        }

        // Initial pagination setup
        filterTable();
    }
});