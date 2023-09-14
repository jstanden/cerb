{foreach from=$prompts item=prompt}
    {if $prompt.type == 'chooser'}
        {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_chooser.tpl" prompt=$prompt}
    {elseif $prompt.type == 'date_range'}
        {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_date_range.tpl" prompt=$prompt}
    {elseif $prompt.type == 'picklist' && !$prompt.params.multiple}
        {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_picklist_single.tpl" prompt=$prompt}
    {elseif $prompt.type == 'picklist' && $prompt.params.multiple}
        {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_picklist_multiple.tpl" prompt=$prompt}
    {elseif $prompt.type == 'text'}
        {if $prompt.params.hidden}
            {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_hidden.tpl" prompt=$prompt}
        {else}
            {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_text.tpl" prompt=$prompt}
        {/if}
    {/if}
{/foreach}
