<fieldset>
    <legend>To record type:</legend>

    {if $field->params.context}
        <input type="hidden" name="params[context]" value="{$field->params.context}">
        {$context = $contexts.{$field->params.context}}
        {if $context->name}
            {$context->name}
        {/if}
    {else}
        <select name="params[context]">
            {foreach from=$contexts item=context}
                <option value="{$context->id}" {if $field->params.context == $context->id}selected="selected"{/if}>{$context->name}</option>
            {/foreach}
        </select>
    {/if}
</fieldset>
