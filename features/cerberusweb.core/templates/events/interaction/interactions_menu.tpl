{if !empty($interactions_menu)}
	<button type="button" class="cerb-bot-interactions-button"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:22px;height:22px;margin:-3px 0px 0px 2px;"></button>
	
	{function menu level=0}
		{foreach from=$keys item=data key=idx}
			{if is_array($data->children) && !empty($data->children)}
				<li>
					<div>{$data->label}</div>
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
	
	<ul class="cerb-bot-interactions-menu cerb-float" style="display:none;">
	{menu keys=$interactions_menu}
	</ul>
{/if}
