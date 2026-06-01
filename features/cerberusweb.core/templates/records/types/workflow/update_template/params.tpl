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
                <button type="button" data-cerb-chooser data-field-name="config_values[{$config_option.key}]{if $config_option.params.multiple}[]{/if}" data-context="{$config_option.params.record_type}" data-query="{$config_option.params.record_query}" {if !$config_option.params.multiple}data-single="true"{/if}><span class="cerb-icons cerb-icon-search"></span></button>
                <ul class="bubbles chooser-container" style="display:inline-block;">
                    {if $config_option.params.multiple}
                        {if is_array($config_option.value)}
                            {foreach from=$config_option.value item=v}
                                <li>
                                    {$config_option.params.record_labels[$v]|default:$v}
                                    <input type="hidden" name="config_values[{$config_option.key}][]" value="{$v}">
                                </li>
                            {/foreach}
                        {/if}
                    {else}
                        {if $config_option.value}
                        <li>
                            {$config_option.params.record_label|default:$config_option.value}
                            <input type="hidden" name="config_values[{$config_option.key}]{if $config_option.params.multiple}[]{/if}" value="{$config_option.value}">
                        </li>
                        {/if}
                    {/if}
                </ul>
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
    $fieldset.find('[data-cerb-chooser]').cerbChooserTrigger();
});
</script>
{/if}