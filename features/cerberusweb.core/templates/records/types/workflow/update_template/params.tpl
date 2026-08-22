{if $workflow_instructions}
<fieldset class="peek">
    <legend>{{'common.instructions'|devblocks_translate|capitalize}}</legend>
    {$workflow_instructions nofilter}
</fieldset>
{/if}

<fieldset class="peek">
    <legend>{{'common.configuration'|devblocks_translate|capitalize}}</legend>
    {if $config_options}
    {foreach from=$config_options item=config_option}
        <div style="margin-bottom:0.5em;">
            <div>
                <b>{if $config_option.params.label}{$config_option.params.label}{else}{$config_option.key}:{/if}</b>
            </div>
            {if 'chooser' == $config_option.type}
                <div class="cerb-ui-record-chooser cerb-config-chooser" data-context="{$config_option.params.record_type}" data-name="config_values[{$config_option.key}]" data-query="{$config_option.params.record_query}"{if $config_option.params.multiple} data-multiple="true"{/if}>
                    {if $config_option.params.multiple}
                        {if is_array($config_option.value)}
                            {foreach from=$config_option.value item=v}
                            <li data-context="{$config_option.params.record_type}" data-context-id="{$v}" data-label="{$config_option.params.record_labels[$v]|default:$v}"></li>
                            {/foreach}
                        {/if}
                    {else}
                        {if $config_option.value}
                        <li data-context="{$config_option.params.record_type}" data-context-id="{$config_option.value}" data-label="{$config_option.params.record_label|default:$config_option.value}"></li>
                        {/if}
                    {/if}
                </div>
            {elseif 'picklist' == $config_option.type}
                {if $config_option.params.multiple}
                    {foreach from=$config_option.params.options item=option}
                    <label><input type="checkbox" name="config_values[{$config_option.key}][]" value="{$option}" {if in_array($option, $config_option.value)}checked="checked"{/if}> {$option}</label>
                    {/foreach}
                {else}
                    <select name="config_values[{$config_option.key}]">
                    {foreach from=$config_option.params.options item=option}
                        <option value="{$option}" {if $config_option.value==$option}selected="selected"{/if}>{$option}</option>
                    {/foreach}
                    </select>
                {/if}
            {elseif 'query' == $config_option.type}
                <div class="cerb-ui-searchquery cerb-config-query" data-context="{$config_option.params.record_type}">
                    <span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
                    <div class="cerb-ui-searchquery--field">
                        <div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
                        <textarea name="config_values[{$config_option.key}]" class="cerb-ui-searchquery--input" rows="1">{$config_option.value}</textarea>
                        <span class="cerb-ui-searchquery--caret-anchor"></span>
                    </div>
                    <div class="cerb-ui-searchquery--right">
                        <a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/&#8984;+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
                    </div>
                </div>
            {elseif 'text' == $config_option.type}
                {if $config_option.params.multiple}
                    <textarea name="config_values[{$config_option.key}]" style="width:100%;height:5em;">{$config_option.value}</textarea>
                {else}
                    <input type="text" name="config_values[{$config_option.key}]" value="{$config_option.value}" style="width:100%;">
                {/if}
            {/if}
        </div>
    {/foreach}
    {else}
        <p>
            (no configuration options)
        </p>
    {/if}
</fieldset>

{$script_id = uniqid('script')}

{if $config_options}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_id}">
$(function() {
    let $script = $('#{$script_id}');
    let $fieldset = $script.prev('fieldset');
    if(window.CerbUI && CerbUI.RecordChooser)
        $fieldset.find('.cerb-config-chooser').each(function() {
            new CerbUI.RecordChooser(this, {
                context: this.getAttribute('data-context'),
                name: this.getAttribute('data-name'),
                multiple: this.hasAttribute('data-multiple'),
                query: this.getAttribute('data-query') || ''
            });
        });

    if(window.CerbUI && CerbUI.SearchQuery)
        $fieldset.find('.cerb-config-query').each(function() {
            const ctx = this.getAttribute('data-context') || '';
            if(!ctx) return;
            const sq = new CerbUI.SearchQuery(this, {
                onAutocomplete: CerbUI.SearchQuery.queryFieldSource(ctx),
                context: ctx
            });
            const acBtn = this.querySelector('[data-action=autocomplete]');
            if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
        });
});
</script>
{/if}