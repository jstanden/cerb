{*
	Shared placeholder-insert menu for inline template fields. Renders a hidden <ul class="menu"> tree of
	{{token}} placeholders for one or more record-type contexts; enhanced into a CerbUI.Menu by the host
	(template_field.tpl) or lazy-loaded via c=internal&a=invoke&module=records&action=templatePlaceholders.
	Expects $placeholders (an Extension_DevblocksContext::getPlaceholderTree() result).
*}
{function tree level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize}</div>
				{else}
					<div>{$idx|capitalize}</div>
				{/if}
				<ul>
					{tree keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
		{/if}
	{/foreach}
{/function}

<ul class="menu" style="width:250px;display:none;" data-cerb-template-placeholders>
{tree keys=$placeholders}
</ul>
