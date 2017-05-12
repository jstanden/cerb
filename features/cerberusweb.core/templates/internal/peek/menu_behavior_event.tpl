{function menu level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize}</div>
				{else}
					<div>{$idx|capitalize}</div>
				{/if}
				<ul style="">
					{menu keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			{$item_context = explode(':', $data->key)}
			<li data-token="{$data->key}" data-label="{$data->label}">
				<div style="font-weight:bold;">
					{$data->l|capitalize}
				</div>
			</li>
		{/if}
	{/foreach}
{/function}

<ul class="chooser-container bubbles"></ul>

<ul class="events-menu" style="width:150px;{if $model->event_point}display:none;{/if}">
{menu keys=$events_menu}
</ul>
