{if $rates && count($rates) > 0}
    <button type="button" class="btn btn-primary currency-rates-btn" style="display: none;">
        {l s='Show prices in other currencies' mod='currencyrate'}
    </button>

    {* Pagination configuration *}
    <script type="text/javascript">
        const currencyRatesPaginationConfig = {
            itemsPerPage: {$items_per_page|intval},
            totalRates: {$total_rates|intval},
            currentPage: 1
        };
    </script>

    <div class="modal fade" id="currencyRatesModal" tabindex="-1" role="dialog" aria-labelledby="currencyRatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="currencyRatesModalLabel">
                        <i class="material-icons">attach_money</i>
                        {l s='Price in Other Currencies' mod='currencyrate'}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>{l s='Current price:' mod='currencyrate'}</strong> {$product_price_formatted}

                        {if $current_currency !== 'PLN' && isset($product_price_pln_formatted)}
                            <br>
                            <small class="text-muted">({l s='≈' mod='currencyrate'} {$product_price_pln_formatted})</small>
                        {/if}
                    </div>

                    <div class="form-currency-search form-group mb-3">
                        <label for="currency-search" class="sr-only">{l s='Search currency' mod='currencyrate'}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="material-icons">search</i>
                                </span>
                            </div>
                            <input type="text" id="currency-search" class="form-control"
                                   placeholder="{l s='Search by currency code or name...' mod='currencyrate'}">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button" id="clear-search">
                                    <i class="material-icons">clear</i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted" id="search-results-count"></small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0" id="currency-rates-table">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="currency">
                                        <a href="javascript:void(0)">
                                            {l s='Currency' mod='currencyrate'}
                                            <i class="material-icons sort-icon">unfold_more</i>
                                        </a>
                                    </th>
                                    <th class="text-right sortable" data-sort="rate">
                                        <a href="javascript:void(0)">
                                            {l s='Exchange Rate' mod='currencyrate'}
                                            <i class="material-icons sort-icon">unfold_more</i>
                                        </a>
                                    </th>
                                    <th class="text-right sortable" data-sort="price">
                                        <a href="javascript:void(0)">
                                            {l s='Approx. Price' mod='currencyrate'}
                                            <i class="material-icons sort-icon">unfold_more</i>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="currency-rates-tbody">
                                {foreach from=$rates item=rate}
                                    <tr data-currency-code="{$rate.currency_code|escape:'htmlall':'UTF-8'}"
                                        data-currency-name="{$rate.currency_name|escape:'htmlall':'UTF-8'|lower}"
                                        data-rate="{$rate.rate}"
                                        data-price="{$rate.converted_price}">
                                        <td>
                                            <strong>{$rate.currency_code|escape:'htmlall':'UTF-8'}</strong><br>
                                            <small class="text-muted">{$rate.currency_name|escape:'htmlall':'UTF-8'}</small>
                                        </td>
                                        <td class="text-right">
                                            <small>1 {$rate.currency_code|escape:'htmlall':'UTF-8'} = {$rate.rate|string_format:"%.4f"} PLN</small>
                                        </td>
                                        <td class="text-right">
                                            <strong>{$rate.converted_price|string_format:"%.2f"} {$rate.currency_code|escape:'htmlall':'UTF-8'}</strong>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>

                    <div id="no-results-message" class="alert alert-info mt-3" style="display: none;">
                        <i class="material-icons">info</i>
                        {l s='No currencies match your search.' mod='currencyrate'}
                    </div>

                    {* Pagination *}
                    <div id="currency-rates-pagination" class="mt-3">
                        <nav aria-label="Currency rates pagination">
                            <ul class="pagination justify-content-center mb-0" id="pagination-controls">
                                {* Pagination will be generated by JavaScript *}
                            </ul>
                        </nav>
                        <div class="text-center text-muted small mt-2" id="pagination-info">
                            {* Pagination info will be generated by JavaScript *}
                        </div>
                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        <i class="material-icons" style="font-size: 14px;">info</i>
                        {l s='Prices calculated based on current NBP exchange rates. For reference only.' mod='currencyrate'}
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Close' mod='currencyrate'}</button>
                </div>
            </div>
        </div>
    </div>
{/if}