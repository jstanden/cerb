<div class="cerb-ui-header">
    <div>
        <div class="cerb-ui-header--title">Requirements</div>
        <div class="cerb-ui-header--subtitle"></div>
    </div>
</div>

<div id="cerbConfigRequirements">
{if $errors}
    {foreach from=$errors item=error}
        <div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
            <div class="cerb-ui-header cerb-ui-header--center">
                <div class="cerb-ui-callout">
                    <span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
                    <div>
                        <div class="cerb-ui-header--subtitle">{$error}</div>
                    </div>
                </div>
            </div>
        </div>
    {/foreach}
{else}
    <div class="help-box">Your server is fully compatible with Cerb.</div>
{/if}
</div>