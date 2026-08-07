{* Markup for the global command bar (CerbUI.Menu inside a CerbUI.Dialog — see button.tpl). CerbUI.Menu parses
   this UL/LI tree, reads each LI's label from its text, and mirrors only data-* attributes onto its own rendered
   rows (icons/avatars/keys are injected by the onRenderItem hook from data-icon / data-image / data-keyboard).
   Keep labels as plain text; a nested <ul> is a submenu; an empty <li> is a separator. *}
{function command_bar_menu}
	{foreach from=$items item=item key=item_key}
		{$item_key_parts = explode('/', $item_key)}
		{if !array_key_exists('hidden', $item) || !$item.hidden}
			{if 'menu' == $item_key_parts[0]}
				<li{if array_key_exists('icon', $item) && $item.icon} data-icon="{$item.icon}"{/if}>{$item.label}
					{if $item.items}<ul>{command_bar_menu items=$item.items}</ul>{/if}
				</li>
			{elseif 'divider' == $item_key_parts[0]}
				<li></li>
			{elseif 'behavior' == $item_key_parts[0]}
				<li class="cerb-bot-trigger"
					data-behavior-id="{$item.id}"
					data-interaction="{$item.interaction}"
					data-interaction-params="{if is_array($item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.params)}{/if}"
					{if array_key_exists('image', $item) && $item.image}data-image="{$item.image}"{/if}
					>{$item.label}</li>
			{elseif 'interaction' == $item_key_parts[0]}
				{$item_subtitle = ''}
				{if array_key_exists('description', $item) && $item.description}{$item_subtitle = $item.description}{elseif array_key_exists('tooltip', $item) && $item.tooltip}{$item_subtitle = $item.tooltip}{/if}
				<li class="cerb-bot-trigger"
					data-interaction-uri="{$item.uri}"
					data-interaction-params="{if array_key_exists('inputs', $item) && is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}"
					data-interaction-done="{if array_key_exists('after', $item) && is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}"
					{if array_key_exists('icon', $item) && $item.icon}data-icon="{$item.icon}"{/if}
					{if array_key_exists('keyboard', $item) && $item.keyboard}data-keyboard="{$item.keyboard}"{/if}
					{if $item_subtitle}data-subtitle="{$item_subtitle}"{/if}
					>{$item.label}</li>
			{elseif 'resume' == $item_key_parts[0]}
				<li class="cerb-bot-resume-trigger"
					data-continuation-token="{$item.token}"
					{if array_key_exists('icon', $item) && $item.icon}data-icon="{$item.icon}"{/if}
					{if array_key_exists('description', $item) && $item.description}data-subtitle="{$item.description}"{/if}
					>{$item.label}</li>
			{/if}
		{/if}
	{/foreach}
{/function}

<ul class="cerb-bot-interactions-menu">
{if $interactions_menu}
	{foreach from=$interactions_menu item=item}
		{if !array_key_exists('hidden', $item) || !$item.hidden}
			{if 'behavior' == $item.type}
				<li class="cerb-bot-trigger"
					data-behavior-id="{$item.id}"
					data-interaction="{$item.interaction}"
					data-interaction-params="{if array_key_exists('params', $item) && is_array($item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.params)}{/if}"
					{if array_key_exists('image', $item) && $item.image}data-image="{$item.image}"{/if}
					>{$item.label}</li>
			{elseif 'interaction' == $item.type}
				{$item_subtitle = ''}
				{if array_key_exists('description', $item) && $item.description}{$item_subtitle = $item.description}{elseif array_key_exists('tooltip', $item) && $item.tooltip}{$item_subtitle = $item.tooltip}{/if}
				<li class="cerb-bot-trigger"
					data-interaction-uri="{$item.uri}"
					data-interaction-params="{if array_key_exists('inputs', $item) && is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}"
					data-interaction-done="{if array_key_exists('after', $item) && is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}"
					{if array_key_exists('icon', $item) && $item.icon}data-icon="{$item.icon}"{/if}
					{if array_key_exists('keyboard', $item) && $item.keyboard}data-keyboard="{$item.keyboard}"{/if}
					{if $item_subtitle}data-subtitle="{$item_subtitle}"{/if}
					>{$item.label}</li>
			{elseif 'resume' == $item.type}
				<li class="cerb-bot-resume-trigger"
					data-continuation-token="{$item.token}"
					{if array_key_exists('icon', $item) && $item.icon}data-icon="{$item.icon}"{/if}
					{if array_key_exists('description', $item) && $item.description}data-subtitle="{$item.description}"{/if}
					>{$item.label}</li>
			{elseif 'menu' == $item.type}
				<li{if array_key_exists('icon', $item) && $item.icon} data-icon="{$item.icon}"{/if}>{$item.label}
					{if $item.items}<ul>{command_bar_menu items=$item.items}</ul>{/if}
				</li>
			{elseif 'divider' == $item.type}
				<li></li>
			{/if}
		{/if}
	{/foreach}
{else}
	<li>No interactions are available.</li>
{/if}
</ul>
