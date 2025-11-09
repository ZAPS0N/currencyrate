<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Currency Rate Settings' d='Modules.Currencyrate.Admin'}
    </div>
    
    <div class="panel-body">
        <form method="post" action="{$current_index|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}" class="form-horizontal">
        
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Enabled Currencies' d='Modules.Currencyrate.Admin'}
                </label>
                <div class="col-lg-9">
                    <select name="enabled_currencies[]" multiple="multiple" class="chosen form-control" size="10">
                        {foreach from=$available_currencies item=currency}
                            <option value="{$currency.code|escape:'htmlall':'UTF-8'}" 
                                {if in_array($currency.code, $enabled_currencies)}selected="selected"{/if}>
                                {$currency.name|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                    <p class="help-block">
                        {l s='Select currencies to track and display' d='Modules.Currencyrate.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='NBP Table Type' d='Modules.Currencyrate.Admin'}
                </label>
                <div class="col-lg-9">
                    <select name="table_type" class="form-control">
                        <option value="A" {if $table_type == 'A'}selected="selected"{/if}>
                            {l s='Table A - Average rates' d='Modules.Currencyrate.Admin'}
                        </option>
                        <option value="B" {if $table_type == 'B'}selected="selected"{/if}>
                            {l s='Table B - Average rates (additional)' d='Modules.Currencyrate.Admin'}
                        </option>
                        <option value="C" {if $table_type == 'C'}selected="selected"{/if}>
                            {l s='Table C - Bid/Ask rates' d='Modules.Currencyrate.Admin'}
                        </option>
                    </select>
                    <p class="help-block">
                        {l s='Table A: Most common currencies with average rates' d='Modules.Currencyrate.Admin'}<br>
                        {l s='Table B: Additional currencies with average rates' d='Modules.Currencyrate.Admin'}<br>
                        {l s='Table C: Buy and sell rates for currency exchange' d='Modules.Currencyrate.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Cache Time (seconds)' d='Modules.Currencyrate.Admin'}
                </label>
                <div class="col-lg-9">
                    <input type="number" name="cache_ttl" value="{$cache_ttl|escape:'htmlall':'UTF-8'}"
                           class="form-control" min="3600" max="604800">
                    <p class="help-block">
                        {l s='How long to cache API responses (3600 = 1 hour, 86400 = 1 day)' d='Modules.Currencyrate.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Items Per Page' d='Modules.Currencyrate.Admin'}
                </label>
                <div class="col-lg-9">
                    <select name="items_per_page" class="form-control">
                        <option value="10" {if $items_per_page == 10}selected="selected"{/if}>10</option>
                        <option value="20" {if $items_per_page == 20}selected="selected"{/if}>20</option>
                        <option value="50" {if $items_per_page == 50}selected="selected"{/if}>50</option>
                        <option value="100" {if $items_per_page == 100}selected="selected"{/if}>100</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Automatic Update Time' d='Modules.Currencyrate.Admin'}
                </label>
                <div class="col-lg-9">
                    <input type="time" name="cron_time" value="{$cron_time|escape:'htmlall':'UTF-8'}" 
                           class="form-control">
                    <p class="help-block">
                        {l s='Time for automatic daily updates (24h format, e.g., 03:00 for 3 AM)' d='Modules.Currencyrate.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Auto Cleanup Old Data' d='Modules.Currencyrate.Admin'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="auto_cleanup" id="auto_cleanup_on" value="1" 
                               {if $auto_cleanup}checked="checked"{/if}>
                        <label for="auto_cleanup_on">{l s='Yes' d='Modules.Currencyrate.Admin'}</label>
                        <input type="radio" name="auto_cleanup" id="auto_cleanup_off" value="0" 
                               {if !$auto_cleanup}checked="checked"{/if}>
                        <label for="auto_cleanup_off">{l s='No' d='Modules.Currencyrate.Admin'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">
                        {l s='Automatically delete rates older than 30 days' d='Modules.Currencyrate.Admin'}
                    </p>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" name="submitCurrencyRateConfig" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save' d='Modules.Currencyrate.Admin'}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-refresh"></i> {l s='Manual Update' d='Modules.Currencyrate.Admin'}
    </div>
    <div class="panel-body">
        <p>{l s='Click the button below to manually update currency rates from NBP API.' d='Modules.Currencyrate.Admin'}</p>
        <form method="post" action="{$current_index|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}">
            <button type="submit" name="submitManualUpdate" class="btn btn-primary">
                <i class="icon-refresh"></i> {l s='Update Now' d='Modules.Currencyrate.Admin'}
            </button>
        </form>
        {if $last_update}
            <p class="alert alert-info mt-3">
                <i class="icon-info-circle"></i> {$last_update|escape:'htmlall':'UTF-8'}
            </p>
        {/if}
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-clock-o"></i> {l s='Cron Setup' d='Modules.Currencyrate.Admin'}
    </div>
    <div class="panel-body">
        <p>{l s='To enable automatic updates, add the following cron job to your server:' d='Modules.Currencyrate.Admin'}</p>
        <pre class="well">0 3 * * * curl -s "{$cron_url|escape:'htmlall':'UTF-8'}" > /dev/null 2>&1</pre>
        <p class="help-block">
            {l s='This will update rates daily at 3:00 AM. Adjust the time according to your preferences.' d='Modules.Currencyrate.Admin'}
        </p>
    </div>
</div>