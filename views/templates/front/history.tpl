{extends file='page.tpl'}

{block name='page_title'}
    {l s='Currency Exchange Rates History' d='Modules.Currencyrate.Front'}
{/block}

{block name='page_content'}
    <div class="currency-rate-history">
        {* Filters *}
        <div class="card mb-3">
            <div class="card-body">
                <form method="get" action="" class="form-inline">
                    <div class="form-group mr-3">
                        <label for="currency" class="mr-2">{l s='Currency:' d='Modules.Currencyrate.Front'}</label>
                        <select name="currency" id="currency" class="form-control">
                            <option value="">
                                {l s='All' d='Modules.Currencyrate.Front'}
                            </option>

                            {foreach from=$available_currencies item=currency}
                                <option value="{$currency.currency_code|escape:'htmlall':'UTF-8'}"
                                    {if $currency_filter == $currency.currency_code}selected="selected" {/if}>
                                    {$currency.currency_code|escape:'htmlall':'UTF-8'} -
                                    {$currency.currency_name|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group mr-3">
                        <label for="search" class="mr-2">{l s='Search:' d='Modules.Currencyrate.Front'}</label>
                        <input type="text" name="search" id="search" class="form-control"
                            value="{$search_query|escape:'htmlall':'UTF-8'}"
                            placeholder="{l s='Currency code or name' d='Modules.Currencyrate.Front'}">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons">search</i> {l s='Filter' d='Modules.Currencyrate.Front'}
                    </button>

                    {if $currency_filter || $search_query}
                        <a href="{$link->getModuleLink('currencyrate', 'history')}" class="btn btn-secondary ml-2">
                            <i class="material-icons">clear</i> {l s='Clear' d='Modules.Currencyrate.Front'}
                        </a>
                    {/if}
                </form>
            </div>
        </div>

        {* Results count *}
        {if $total_rates > 0}
            <p class="text-muted mb-3">
                {l s='Showing' d='Modules.Currencyrate.Front'} {$rates|count} {l s='of' d='Modules.Currencyrate.Front'}
                {$total_rates} {l s='rates' d='Modules.Currencyrate.Front'}
            </p>
        {/if}

        {* Rates Table *}
        {if $rates && count($rates) > 0}
            <div class="table-responsive">
                <table class="table table-striped table-bordered currency-rate-table">
                    <thead>
                        <tr>
                            <th>
                                <a
                                    href="?currency={$currency_filter}&search={$search_query}&orderby=effective_date&orderway={if $order_by == 'effective_date' && $order_way == 'desc'}asc{else}desc{/if}">
                                    {l s='Date' d='Modules.Currencyrate.Front'}
                                    {if $order_by == 'effective_date'}
                                        <i class="material-icons">{if $order_way == 'desc'}arrow_downward{else}arrow_upward{/if}</i>
                                    {/if}
                                </a>
                            </th>
                            <th>
                                <a
                                    href="?currency={$currency_filter}&search={$search_query}&orderby=currency_code&orderway={if $order_by == 'currency_code' && $order_way == 'desc'}asc{else}desc{/if}">
                                    {l s='Currency' d='Modules.Currencyrate.Front'}
                                    {if $order_by == 'currency_code'}
                                        <i class="material-icons">{if $order_way == 'desc'}arrow_downward{else}arrow_upward{/if}</i>
                                    {/if}
                                </a>
                            </th>
                            <th class="text-right">
                                {if $table_type == 'C'}
                                    <a
                                        href="?currency={$currency_filter}&search={$search_query}&orderby=rate_bid&orderway={if $order_by == 'rate_bid' && $order_way == 'desc'}asc{else}desc{/if}">
                                        {l s='Bid Rate' d='Modules.Currencyrate.Front'}
                                        {if $order_by == 'rate_bid'}
                                            <i class="material-icons">{if $order_way == 'desc'}arrow_downward{else}arrow_upward{/if}</i>
                                        {/if}
                                    </a>
                                {else}
                                    <a
                                        href="?currency={$currency_filter}&search={$search_query}&orderby=rate_mid&orderway={if $order_by == 'rate_mid' && $order_way == 'desc'}asc{else}desc{/if}">
                                        {l s='Rate' d='Modules.Currencyrate.Front'}
                                        {if $order_by == 'rate_mid'}
                                            <i class="material-icons">{if $order_way == 'desc'}arrow_downward{else}arrow_upward{/if}</i>
                                        {/if}
                                    </a>
                                {/if}
                            </th>
                            {if $table_type == 'C'}
                                <th class="text-right">
                                    <a
                                        href="?currency={$currency_filter}&search={$search_query}&orderby=rate_ask&orderway={if $order_by == 'rate_ask' && $order_way == 'desc'}asc{else}desc{/if}">
                                        {l s='Ask Rate' d='Modules.Currencyrate.Front'}
                                        {if $order_by == 'rate_ask'}
                                            <i class="material-icons">{if $order_way == 'desc'}arrow_downward{else}arrow_upward{/if}</i>
                                        {/if}
                                    </a>
                                </th>
                            {/if}
                            <th>{l s='Table No.' d='Modules.Currencyrate.Front'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$rates item=rate}
                            <tr>
                                <td>{$rate.effective_date|date_format:"%Y-%m-%d"}</td>
                                <td>
                                    <strong>{$rate.currency_code|escape:'htmlall':'UTF-8'}</strong><br>
                                    <small class="text-muted">{$rate.currency_name|escape:'htmlall':'UTF-8'}</small>
                                </td>
                                <td class="text-right">
                                    {if $table_type == 'C'}
                                        {$rate.rate_bid|string_format:"%.4f"} PLN
                                    {else}
                                        {$rate.rate_mid|string_format:"%.4f"} PLN
                                    {/if}
                                </td>
                                {if $table_type == 'C'}
                                    <td class="text-right">{$rate.rate_ask|string_format:"%.4f"} PLN</td>
                                {/if}
                                <td><small>{$rate.table_number|escape:'htmlall':'UTF-8'}</small></td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            {* Pagination *}
            {if $total_pages > 1}
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        {if $current_page > 1}
                            <li class="page-item">
                                <a class="page-link"
                                    href="?currency={$currency_filter}&search={$search_query}&orderby={$order_by}&orderway={$order_way}&p={$current_page-1}">
                                    <i class="material-icons">chevron_left</i>
                                </a>
                            </li>
                        {/if}

                        {for $i=1 to $total_pages}
                            {if $i == $current_page || $i == 1 || $i == $total_pages || ($i >= $current_page-2 && $i <= $current_page+2)}
                                <li class="page-item {if $i == $current_page}active{/if}">
                                    <a class="page-link"
                                        href="?currency={$currency_filter}&search={$search_query}&orderby={$order_by}&orderway={$order_way}&p={$i}">
                                        {$i}
                                    </a>
                                </li>
                            {elseif $i == $current_page-3 || $i == $current_page+3}
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            {/if}
                        {/for}

                        {if $current_page < $total_pages}
                            <li class="page-item">
                                <a class="page-link"
                                    href="?currency={$currency_filter}&search={$search_query}&orderby={$order_by}&orderway={$order_way}&p={$current_page+1}">
                                    <i class="material-icons">chevron_right</i>
                                </a>
                            </li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        {else}
            <div class="alert alert-info">
                <i class="material-icons">info</i>

                {l s='No currency rates found. Please update the rates from the module configuration.' d='Modules.Currencyrate.Front'}
            </div>
        {/if}
    </div>
{/block}