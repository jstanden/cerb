{function menu level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize|truncate:30}</div>
				{else}
					<div>{$idx|capitalize|truncate:30}</div>
				{/if}
				<ul style="{if count($data->children) > 15}width:calc(50vw);column-width:200px;column-count:auto;{else}width:200px;{/if}">
					{menu keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			{$item_context = explode(':', $data->key)}
			<li data-token="{$data->key}" data-label="{$data->label}">
				<div style="font-weight:bold;">
					<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$item_context.0}&context_id={$item_context.1}{/devblocks_url}?v={$smarty.const.APP_BUILD}">
					{$data->l|capitalize|truncate:30}
				</div>
			</li>
		{/if}
	{/foreach}
{/function}

<ul class="chooser-container bubbles">
{$owner_context_ext = Extension_DevblocksContext::get($model->owner_context|default:'')}
{if is_a($owner_context_ext, 'Extension_DevblocksContext')}
	{$meta = $owner_context_ext->getMeta($model->owner_context_id)}
	{if $meta}
		<li>
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$owner_context_ext->manifest->params.alias}&context_id={$model->owner_context_id}{/devblocks_url}?v={$meta.updated|default:$smarty.const.APP_BUILD}">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$model->owner_context}" data-context-id="{$model->owner_context_id}">{$meta.name}</a>
			<input type="hidden" name="owner" value="{$model->owner_context}:{$model->owner_context_id}">
			<a href="javascript:;" onclick="$(this).trigger('bubble-remove');"><span class="glyphicons glyphicons-circle-remove"></span></a>
		</li>
	{/if}
{/if}
</ul>

<ul class="owners-menu" style="width:150px;{if $model->owner_context}display:none;{/if}">
{menu keys=$owners_menu}
</ul>
