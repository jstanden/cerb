{function toolbar_menu level=0}
	{foreach from=$items item=item key=item_key}
		{$item_key_parts = explode('/', $item_key)}
		{if !array_key_exists('hidden', $item) || !$item.hidden}
			{if 'menu' == $item_key_parts[0]}
				<li>
					{if array_key_exists('icon', $item) && $item.icon}
						<span class="glyphicons glyphicons-{$item.icon}"></span>
					{/if}
					{$item.label}
					{if $item.items}
						<ul>
							{toolbar_menu items=$item.items}
						</ul>
					{/if}
				</li>
			{elseif 'divider' == $item_key_parts[0]}
				<li>
					<hr/>
				</li>
			{elseif 'behavior' == $item_key_parts[0]}
				<li class="cerb-bot-trigger"
					data-behavior-id="{$item.id}"
					data-interaction="{$item.interaction}"
					data-interaction-params="{if is_array($item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.params)}{/if}"
					>
					{if $item.image}
						<img class="cerb-avatar" src="{$item.image}">
					{/if}
					<b>{$item.label}</b>
				</li>
			{elseif 'interaction' == $item_key_parts[0]}
				<li class="cerb-bot-trigger"
					data-interaction-uri="{$item.uri}"
					data-interaction-params="{if array_key_exists('inputs', $item) && is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}"
					data-interaction-done="{if array_key_exists('after', $item) && is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}"
					>
					{if array_key_exists('icon', $item) && $item.icon}
						<span class="glyphicons glyphicons-{$item.icon}"></span>
					{/if}
					<b>{$item.label}</b>
				</li>
			{/if}
		{/if}
	{/foreach}
{/function}

<ul class="cerb-bot-interactions-menu cerb-float" style="width:250px;">
{if $interactions_menu}
	{foreach from=$interactions_menu item=$item}
		{if !array_key_exists('hidden', $item) || !$item.hidden}
			{if 'behavior' == $item.type}
				<li class="cerb-bot-trigger"
					data-behavior-id="{$item.id}"
					data-interaction="{$item.interaction}"
					data-interaction-params="{if array_key_exists('params', $item) && is_array($item.params)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.params)}{/if}"
					>
					{if $item.image}
						<img class="cerb-avatar" src="{$item.image}">
					{/if}
					<b>{$item.label}</b>
				</li>
			{elseif 'interaction' == $item.type}
				<li class="cerb-bot-trigger"
					data-interaction-uri="{$item.uri}"
					data-interaction-params="{if array_key_exists('inputs', $item) && is_array($item.inputs)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.inputs)}{/if}"
					data-interaction-done="{if array_key_exists('after', $item) && is_array($item.after)}{DevblocksPlatform::services()->url()->arrayToQueryString($item.after)}{/if}"
					>
					{if array_key_exists('icon', $item) && $item.icon}
						<span class="glyphicons glyphicons-{$item.icon}"></span>
					{/if}
					<b>{$item.label}</b>
				</li>
			{elseif 'menu' == $item.type}
				<li>
					{if array_key_exists('icon', $item) && $item.icon}
						<span class="glyphicons glyphicons-{$item.icon}"></span>
					{/if}
					{$item.label}
					{if $item.items}
						<ul>
							{toolbar_menu items=$item.items}
						</ul>
					{/if}
				</li>
			{elseif 'divider' == $item.type}
				<li>
					<hr/>
				</li>
			{/if}
		{/if}
	{/foreach}
{else}
<li>No interactions are available.</li>
{/if}
</ul>