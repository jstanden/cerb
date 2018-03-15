{function menu level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li>
				<div style="font-weight:bold;">
					{if $data->image}
					<img class="cerb-avatar" src="{$data->image}">
					{/if}
					{$data->label}
				</div>
				<ul>
					{menu keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif !is_null($data->key)}
			<li class="cerb-bot-trigger" data-behavior-id="{$data->key}" data-interaction="{$data->interaction}" data-interaction-params="{http_build_query($data->params)}">
				<div style="font-weight:bold;">
					{$data->label}
				</div>
			</li>
		{/if}
	{/foreach}
{/function}

<ul class="cerb-bot-interactions-menu cerb-float" style="width:250px;">
{if $interactions_menu}
{menu keys=$interactions_menu}
{else}
<li>No bots are available.</li>
{/if}
</ul>