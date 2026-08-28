{* Render a parsed toolbar (DevblocksUiToolbar::parse) as CerbUI.Toolbar markup: a nested ul/li the
   CerbUI.Toolbar component enhances, emitting data-* attributes (this replaced the legacy <button> +
   <ul class="cerb-float"> chrome). Hidden (caller-denied) items are skipped server-side. The host page
   constructs `new CerbUI.Toolbar(el, {...})`. *}
{function cerbui_toolbar_menu items=[]}
    {foreach from=$items item=item key=item_key}
        {$item_key_parts = explode('/', $item_key)}
        {if !$item.hidden}
            {if 'menu' == $item_key_parts[0]}
                <li{if $item.icon} data-icon="{$item.icon}"{/if}{if $item.icon_at} data-icon-at="{$item.icon_at}"{/if}{if $item.class} data-class="{$item.class}"{/if}{if $item.tooltip} title="{$item.tooltip}"{/if}>{$item.label}{if $item.items}<ul>{cerbui_toolbar_menu items=$item.items}</ul>{/if}</li>
            {elseif 'divider' == $item_key_parts[0]}
                <li></li>
            {elseif 'interaction' == $item_key_parts[0]}
                <li class="cerb-bot-trigger"{if $item.icon} data-icon="{$item.icon}"{/if}{if $item.icon_at} data-icon-at="{$item.icon_at}"{/if}{if $item.image} data-image="{$item.image}"{/if}{if $item.description} data-description="{$item.description}"{/if}{if $item.class} data-class="{$item.class}"{/if}{if $item.keyboard} data-keyboard="{$item.keyboard}" data-interaction-keyboard="{$item.keyboard}"{/if} data-interaction-uri="{$item.uri}" data-interaction-params="{if is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}" data-interaction-done="{if is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}">{$item.label}</li>
            {elseif 'behavior' == $item_key_parts[0]}
                <li class="cerb-bot-trigger"{if $item.icon} data-icon="{$item.icon}"{/if}{if $item.class} data-class="{$item.class}"{/if} data-behavior-id="{$item.id}" data-interaction="{$item.interaction}" data-interaction-params="{if is_array($item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.params)}{/if}" data-interaction-done="{if is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}">{$item.label}</li>
            {/if}
        {/if}
    {/foreach}
{/function}
<ul class="cerb-ui-toolbar{if $wrapper_class} {$wrapper_class}{/if}"{if $wrapper_attr} {$wrapper_attr nofilter}{/if}>
{foreach from=$toolbar item=toolbar_item}
    {if !array_key_exists('hidden', $toolbar_item) || !$toolbar_item.hidden}
        {if 'interaction' == $toolbar_item.type}
            {if $toolbar_item.uri}
                <li class="cerb-bot-trigger"{if $toolbar_item.icon} data-icon="{$toolbar_item.icon}"{/if}{if $toolbar_item.icon_at} data-icon-at="{$toolbar_item.icon_at}"{/if}{if $toolbar_item.image} data-image="{$toolbar_item.image}"{/if}{if $toolbar_item.description} data-description="{$toolbar_item.description}"{/if}{if $toolbar_item.class} data-class="{$toolbar_item.class}"{/if} data-value="{$toolbar_item.key}"{if $toolbar_item.tooltip} title="{$toolbar_item.tooltip}"{/if}{if $toolbar_item.keyboard} data-keyboard="{$toolbar_item.keyboard}" data-interaction-keyboard="{$toolbar_item.keyboard}"{/if} data-interaction-uri="{$toolbar_item.uri}" data-interaction-params="{if is_array($toolbar_item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.inputs)}{/if}" data-interaction-done="{if is_array($toolbar_item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.after)}{/if}"{if !is_null($toolbar_item.badge)} data-badge="{$toolbar_item.badge}"{/if}{if $toolbar_item.badge_color} data-badge-color="{$toolbar_item.badge_color}"{/if}>{$toolbar_item.label}</li>
            {/if}
        {elseif 'behavior' == $toolbar_item.type}
            {if $toolbar_item.id}
                <li class="cerb-bot-trigger"{if $toolbar_item.icon} data-icon="{$toolbar_item.icon}"{/if}{if $toolbar_item.class} data-class="{$toolbar_item.class}"{/if} data-value="{$toolbar_item.key}"{if $toolbar_item.tooltip} title="{$toolbar_item.tooltip}"{/if}{if $toolbar_item.keyboard} data-keyboard="{$toolbar_item.keyboard}" data-interaction-keyboard="{$toolbar_item.keyboard}"{/if} data-behavior-id="{$toolbar_item.id}" data-interaction="{$toolbar_item.interaction}" data-interaction-params="{if is_array($toolbar_item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.params)}{/if}" data-interaction-done="{if is_array($toolbar_item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($toolbar_item.after)}{/if}"{if !is_null($toolbar_item.badge)} data-badge="{$toolbar_item.badge}"{/if}{if $toolbar_item.badge_color} data-badge-color="{$toolbar_item.badge_color}"{/if}>{$toolbar_item.label}</li>
            {/if}
        {elseif 'menu' == $toolbar_item.type}
            <li{if $toolbar_item.icon} data-icon="{$toolbar_item.icon}"{/if}{if $toolbar_item.icon_at} data-icon-at="{$toolbar_item.icon_at}"{/if}{if $toolbar_item.class} data-class="{$toolbar_item.class}"{/if} data-value="{$toolbar_item.key}"{if $toolbar_item.tooltip} title="{$toolbar_item.tooltip}"{/if}{if !is_null($toolbar_item.badge)} data-badge="{$toolbar_item.badge}"{/if}{if $toolbar_item.badge_color} data-badge-color="{$toolbar_item.badge_color}"{/if}>{$toolbar_item.label}{if $toolbar_item.items}<ul>{cerbui_toolbar_menu items=$toolbar_item.items}</ul>{/if}</li>
        {elseif 'divider' == $toolbar_item.type}
            <li></li>
        {/if}
    {/if}
{/foreach}
</ul>
