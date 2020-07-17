{if !empty($interactions_menu)}
	<button type="button" title="Bot interactions" class="cerb-bot-interactions-button {if $button_classes}{$button_classes}{/if}"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:22px;height:22px;margin:-3px 0px 0px 2px;"></button>
	
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
					<ul style="width:200px;">
						{menu keys=$data->children level=$level+1}
					</ul>
				</li>
			{elseif !is_null($data->interaction_id)}
				<li class="cerb-bot-trigger" data-interaction-id="{$data->interaction_id}" data-interaction="{$data->interaction}" data-interaction-params="{http_build_query($data->params)}">
					<div style="font-weight:bold;">
						{$data->label}
					</div>
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
	
	<ul class="cerb-bot-interactions-menu cerb-float" style="display:none;width:200px;max-width:50%;">
	{menu keys=$interactions_menu}
	</ul>
{/if}
