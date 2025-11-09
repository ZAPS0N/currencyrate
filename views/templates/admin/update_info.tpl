<div class="panel">
    <div class="panel-heading">
        <i class="icon-info-circle"></i> {l s='Update information' d='Modules.Currencyrate.Admin'}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-8">
                <p class="alert alert-info" style="margin-bottom: 0;">
                    <i class="icon-clock-o"></i>
                    {$last_update}
                </p>
            </div>
            <div class="col-lg-4 text-right">
                <form method="post" style="display: inline-block;">
                    <button type="submit" name="submitManualUpdate" class="btn btn-success btn-lg"
                        onclick="return confirm('{l s='This will fetch the latest rates from NBP API. Continue?' d='Modules.Currencyrate.Admin' js=1}');">
                        <i class="icon-refresh"></i>
                        {l s='Update Rates Now' d='Modules.Currencyrate.Admin'}
                    </button>
                </form>
            </div>
        </div>

        <hr style="margin: 20px 0;">

        <h4>{l s='Cron setup' d='Modules.Currencyrate.Admin'}</h4>

        <p>
            {l s='To enable automatic updates, add the following cron URL to your server:' d='Modules.Currencyrate.Admin'}
        </p>

        <pre class="well" style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;word-break: break-all;">{$cron_url}?token={$cron_token}</pre>

        <p style="margin-top: 15px;">
            <strong>{l s='Example cron command:' d='Modules.Currencyrate.Admin'}</strong>
        </p>
        <pre
            style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;">0 2 * * * curl -s "{$cron_url}" > /dev/null 2>&1</pre>
        <small
            class="help-block">{l s='This example runs the update daily at 2:00 AM' d='Modules.Currencyrate.Admin'}</small>
    </div>
</div>