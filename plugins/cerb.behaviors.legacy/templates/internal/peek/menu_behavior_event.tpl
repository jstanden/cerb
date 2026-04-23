{function menu level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$events[$data->key]->name}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l}</div>
				{else}
					<div>{$idx}</div>
				{/if}
				<ul style="width:200px;">
					{menu keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			{$item_context = explode(':', $data->key)}
			<li data-token="{$data->key}" data-label="{$events[$data->key]->name}">
				<div style="font-weight:bold;">
					{$data->l}
				</div>
			</li>
		{/if}
	{/foreach}
{/function}

<ul class="chooser-container bubbles"></ul>

<ul class="events-menu" style="width:150px;{if $model && $model->event_point}display:none;{/if}">
{menu keys=$events_menu}
</ul>
