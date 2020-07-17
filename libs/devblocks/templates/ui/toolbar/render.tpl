{function toolbar_menu level=0}
    {foreach from=$items item=item key=item_key}
        {$item_key_parts = explode('/', $item_key)}
        {if !$item.hidden}
            {if 'menu' == $item_key_parts[0]}
                <li>
                    {if $item.icon}
                        <span class="glyphicons glyphicons-{$item.icon}"></span>
                    {/if}
                    {$item.label}
                    {if $item.items}
                    <ul>
                        {toolbar_menu items=$item.items}
                    </ul>
                    {/if}
                </li>
            {elseif 'interaction' == $item_key_parts[0]}
                <li class="cerb-bot-trigger"
                    data-interaction-uri="{$item.name}"
                    data-interaction-params="{if is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}"
                    data-interaction-done="{if is_array($item['event/done'])}{DevblocksPlatform::services()->url()->arrayToQueryString($item['event/done'])}{/if}"
                    >
                    {if $item.icon}
                        <span class="glyphicons glyphicons-{$item.icon}"></span>
                    {/if}
                    <b>{$item.label}</b>
                </li>
            {/if}
        {/if}
    {/foreach}
{/function}

{foreach from=$toolbar item=toolbar_item}
    {if !$toolbar_item.schema.hidden}
        {if 'interaction' == $toolbar_item.type}
            {if $toolbar_item.schema.name}
                <button type="button" class="cerb-bot-trigger"
                        data-cerb-toolbar-button
                        data-interaction-uri="{$toolbar_item.schema.name}"
                        data-interaction-params="{if is_array($toolbar_item.schema.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.schema.inputs)}{/if}"
                        data-interaction-done="{if is_array($toolbar_item.schema['event/done'])}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.schema['event/done'])}{/if}"
                        >
                    {if $toolbar_item.schema.icon}
                        <span class="glyphicons glyphicons-{$toolbar_item.schema.icon}"></span>
                    {/if}
                    {$toolbar_item.schema.label}
                </button>
            {/if}
        {elseif 'menu' == $toolbar_item.type}
            <button type="button" data-cerb-toolbar-menu>
                {if $toolbar_item.schema.icon}
                    <span class="glyphicons glyphicons-{$toolbar_item.schema.icon}"></span>
                {/if}
                {$toolbar_item.schema.label}
            </button>
            <ul class="cerb-float" style="display:none;text-align:left;">
                {toolbar_menu items=$toolbar_item.schema.items}
            </ul>
        {/if}
    {/if}
{/foreach}
