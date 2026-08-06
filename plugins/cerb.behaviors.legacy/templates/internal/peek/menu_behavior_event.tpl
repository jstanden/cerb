{function menu level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l}</div>
				{else}
					<div>{$idx}</div>
				{/if}
				<ul>
					{menu keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			<li data-token="{$data->key}" data-label="{$events[$data->key]->name}">
				<div style="font-weight:bold;">
					{$data->l}
				</div>
			</li>
		{/if}
	{/foreach}
{/function}

<ul class="chooser-container bubbles"></ul>

<button type="button" class="events-menu-trigger"{if $model && $model->event_point} style="display:none;"{/if}><span class="cerb-icons cerb-icon-search"></span></button>

<ul class="events-menu" style="display:none;">
{menu keys=$events_menu}
</ul>
