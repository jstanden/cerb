{foreach from=$prompts item=prompt}
    <b>{$prompt.label}</b>
    {if !$prompt.required}<small>({'common.optional'|devblocks_translate|lower})</small>{/if}
    <div style="margin-left:10px;" data-cerb-snippet-prompt {if $prompt.required}data-required{/if}>
        {if $prompt.type == 'checkbox'}
            <label><input type="radio" name="prompts[{$prompt.name}]" class="placeholder" value="1" {if $prompt.default}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
            <label><input type="radio" name="prompts[{$prompt.name}]" class="placeholder" value="0" {if !$prompt.default}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
        {elseif $prompt.type == 'text'}
            {if $prompt.params.multiple}
                <textarea name="prompts[{$prompt.name}]" class="placeholder" rows="3" cols="45" style="width:98%;">{$prompt.default}</textarea>
            {else}
                <input type="text" name="prompts[{$prompt.name}]" class="placeholder" value="{$prompt.default}" style="width:98%;">
            {/if}
        {elseif $prompt.type == 'picklist'}
            <select name="prompts[{$prompt.name}]">
                {foreach from=$prompt.params.options item=option}
                    <option value="{$option}" {if $prompt.default==$option}selected="selected"{/if}>{$option}</option>
                {/foreach}
            </select>
        {elseif $prompt.type == 'chooser'}
            [[chooser]]
        {/if}
    </div>
{/foreach}
