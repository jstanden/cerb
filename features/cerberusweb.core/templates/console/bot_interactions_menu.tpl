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
				<ul style="width:300px;">
					{menu keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif !is_null($data->key)}
			<li class="cerb-bot-trigger" data-interaction="{$data->interaction}" data-behavior-id="{$data->key}"{foreach from=$data->params item=param_value key=param_key} data-interaction-param-{$param_key}="{$param_value}"{/foreach}>
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