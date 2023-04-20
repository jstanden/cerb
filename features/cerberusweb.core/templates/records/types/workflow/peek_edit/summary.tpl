{$config_values = $model->getConfig()}
{$config_options = $model->getConfigOptions($config_values)}
{$config_uniqid = uniqid('workflow_config_')}

{if $config_options}
<fieldset class="peek" id="{$config_uniqid}">
    <legend>{{'common.configuration'|devblocks_translate|capitalize}}</legend>

    <div style="padding:0 1em;column-width:350px;column-count:2;">
    {foreach from=$config_options item=$config_option}
        <div style="margin-bottom:0.5em;break-inside:avoid-column;page-break-inside:avoid;">
            <div>
                <b>{if $config_option.params.label}{$config_option.params.label}{else}{$config_option.key}:{/if}</b>
            </div>
            <div style="margin-left:0.5em;">
                {if 'chooser' == $config_option.type}
                    <ul class="bubbles" style="display:inline-block;">
                        {if $config_option.params.multiple}
                            {if is_array($config_option.value)}
                                {foreach from=$config_option.value item=v}
                                    <li>
                                        <a class="cerb-peek-trigger" data-context="{$config_option.params.record_type}" data-context-id="{$v}">{$config_option.params.record_labels[$v]|default:$v}</a>
                                    </li>
                                {/foreach}
                            {/if}
                        {else}
                            {if $config_option.value}
                                <li>
                                    <a class="cerb-peek-trigger" data-context="{$config_option.params.record_type}" data-context-id="{$config_option.value}">{$config_option.params.record_label|default:$config_option.value}</a>
                                </li>
                            {/if}
                        {/if}
                    </ul>
                {else}
                    {$config_option.value}
                {/if}
            </div>
        </div>
    {/foreach}
    </div>
</fieldset>

<script type="application/javascript">
$(function() {
   let $config = $('#{$config_uniqid}');
   $config.find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>
{/if}

{if $rows}
<fieldset class="peek">
    <legend>{{'common.resources'|devblocks_translate|capitalize}}</legend>
    {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
</fieldset>
{/if}