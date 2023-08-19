{function toolbar_menu level=0}
    {foreach from=$items item=item key=item_key}
        {$item_key_parts = explode('/', $item_key)}
        {if !$item.hidden}
            {if 'menu' == $item_key_parts[0]}
                <li>
                    <div>
                        {if $item.icon && ('start' == $item.icon_at || !$item.icon_at)}
                            <span class="glyphicons glyphicons-{$item.icon}"></span>
                        {/if}
                        {$item.label}
                        {if $item.icon && 'end' == $item.icon_at}
                            <span class="glyphicons glyphicons-{$item.icon}"></span>
                        {/if}
                    </div>
                    {if $item.items}
                    <ul>
                        {toolbar_menu items=$item.items}
                    </ul>
                    {/if}
                </li>
            {elseif 'divider' == $item_key_parts[0]}
                <li>
                    <div>
                        <hr/>
                    </div>
                </li>
            {elseif 'interaction' == $item_key_parts[0]}
                <li class="{$interaction_class}"
                    data-interaction-uri="{$item.uri}"
                    data-interaction-params="{if is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}"
                    data-interaction-done="{if is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}"
                    {if $item.tooltip}title="{$item.tooltip}"{/if}
                    {if $item.keyboard}data-interaction-keyboard="{$item.keyboard}"{/if}
                    >
                    <div>
                        {if $item.icon && ('start' == $item.icon_at || !$item.icon_at)}
                            <span class="glyphicons glyphicons-{$item.icon}"></span>
                        {/if}
                        <b>{$item.label}</b>
                        {if $item.keyboard}<small>({$item.keyboard})</small>{/if}
                        {if $item.icon && 'end' == $item.icon_at}
                            <span class="glyphicons glyphicons-{$item.icon}"></span>
                        {/if}
                    </div>
                </li>
            {elseif 'behavior' == $item_key_parts[0]}
                <li class="{$interaction_class}"
                    data-behavior-id="{$item.id}"
                    data-interaction="{$item.interaction}"
                    data-interaction-params="{if is_array($item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.params)}{/if}"
                    data-interaction-done="{if is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}"
                    >
                    <div>
                        {if $item.image}
                            <img class="cerb-avatar" src="{$item.image}">
                        {/if}
                        <b>{$item.label}</b>
                    </div>
                </li>
            {/if}
        {/if}
    {/foreach}
{/function}

{foreach from=$toolbar item=toolbar_item}
    {if !array_key_exists('hidden', $toolbar_item) || !$toolbar_item.hidden}
        {if 'interaction' == $toolbar_item.type}
            {if $toolbar_item.uri}
                <button type="button" class="{$interaction_class} {$toolbar_item.class}"
                        data-cerb-toolbar-button
                        data-interaction-uri="{$toolbar_item.uri}"
                        data-interaction-params="{if is_array($toolbar_item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.inputs)}{/if}"
                        data-interaction-done="{if is_array($toolbar_item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.after)}{/if}"
                        {if $toolbar_item.tooltip || $toolbar_item.keyboard}title="{$toolbar_item.tooltip} {if $toolbar_item.keyboard}({$toolbar_item.keyboard}){/if}"{/if}
                        {if $toolbar_item.keyboard}data-interaction-keyboard="{$toolbar_item.keyboard}"{/if}
                        >
                    {if !is_null($toolbar_item.badge)}
                        <div class="badge-count">{$toolbar_item.badge}</div>
                    {/if}
                    {if $toolbar_item.icon && ('start' == $toolbar_item.icon_at || !$toolbar_item.icon_at)}
                        <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                    {$toolbar_item.label}
                    {if $toolbar_item.icon && 'end' == $toolbar_item.icon_at}
                        <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                </button>
            {/if}
        {elseif 'behavior' == $toolbar_item.type}
            {if $toolbar_item.id}
                <button type="button" class="{$interaction_class} {$toolbar_item.class}"
                        data-cerb-toolbar-button
                        data-behavior-id="{$toolbar_item.id}"
                        data-interaction="{$toolbar_item.interaction}"
                        data-interaction-params="{if is_array($toolbar_item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.params)}{/if}"
                        data-interaction-done="{if is_array($toolbar_item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.after)}{/if}"
                        {if $toolbar_item.tooltip}title="{$toolbar_item.tooltip}"{/if}
                        {if $toolbar_item.keyboard}data-interaction-keyboard="{$toolbar_item.keyboard}"{/if}
                        >
                    {if !is_null($toolbar_item.badge)}
                        <div class="badge-count">{$toolbar_item.badge}</div>
                    {/if}
                    {if $toolbar_item.icon && ('start' == $toolbar_item.icon_at || !$toolbar_item.icon_at)}
                        <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                    {$toolbar_item.label}
                    {if $toolbar_item.icon && 'end' == $toolbar_item.icon_at}
                        <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                </button>
            {/if}
        {elseif 'menu' == $toolbar_item.type}
            {$item_key_parts = explode('/', $toolbar_item.default|default:'')}
            {$default = $toolbar_item.items[$toolbar_item.default]}

            {* Split menu button *}
            {if $default}
                <button type="button" class="split-left {$interaction_class} {$toolbar_item.class}"
                        data-cerb-toolbar-button
                        data-interaction-uri="{$default.uri}"
                        data-interaction-params="{if is_array($default.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($default.inputs)}{/if}"
                        data-interaction-done="{if is_array($default.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($default.after)}{/if}"
                        {if $default.label}title="{$default.label}"{/if}
                        >
                    {if !is_null($default.badge)}
                    <div class="badge-count">{$toolbar_item.badge}</div>
                    {/if}
                    {if $toolbar_item.icon && ('start' == $toolbar_item.icon_at || !$toolbar_item.icon_at)}
                    <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                    {$toolbar_item.label}
                    {if $toolbar_item.icon && 'end' == $toolbar_item.icon_at}
                    <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                </button><button type="button" class="split-right {$toolbar_item.class}" data-cerb-toolbar-menu {if $toolbar_item.hover}data-cerb-toolbar-menu-hover{/if}>
                    <span class="glyphicons glyphicons-chevron-down"></span>
                </button>
            {else}
                <button type="button"  class="{$toolbar_item.class}"
                        data-cerb-toolbar-menu 
                        {if $toolbar_item.tooltip}title="{$toolbar_item.tooltip}"{/if} 
                        {if $toolbar_item.hover}data-cerb-toolbar-menu-hover{/if}
                        >
                    {if !is_null($toolbar_item.badge)}
                        <div class="badge-count">{$toolbar_item.badge}</div>
                    {/if}
                    {if $toolbar_item.icon && ('start' == $toolbar_item.icon_at || !$toolbar_item.icon_at)}
                        <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                    {$toolbar_item.label}
                    {if $toolbar_item.icon && 'end' == $toolbar_item.icon_at}
                        <span class="glyphicons glyphicons-{$toolbar_item.icon}"></span>
                    {/if}
                </button>
            {/if}
            <ul class="cerb-float" style="display:none;text-align:left;">
                {toolbar_menu items=$toolbar_item.items}
            </ul>

        {elseif 'divider' == $toolbar_item.type}
            <div class="cerb-code-editor-toolbar-divider"></div>
        {/if}
    {/if}
{/foreach}
