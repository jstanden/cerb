{if $snippet->prompts_kata}
<div style="border:dotted 1px rgb(200,200,200);padding:5px;">
	<div class="snippet-placeholders">
	{foreach from=$snippet->getPrompts() item=prompt}
		{$prompt_value = $params.placeholders[$prompt.name]|default:$prompt.default}
		<b>{$prompt.label}</b>
		<div style="margin-left:10px;padding-bottom:5px;">
			{if $prompt.type == 'checkbox'}
				<textarea name="{$namePrefix}[placeholders][{$prompt.name}]" class="placeholders" rows="3" cols="45" style="width:98%;">{if $prompt_value}1{else}0{/if}</textarea>
				<div>
					<small>
						<b>{'common.options'|devblocks_translate|capitalize}:</b>
						<code>0</code>, <code>1</code>
					</small>
				</div>
			{elseif $prompt.type == 'text'}
				{if $prompt.params.multiple}
					<textarea name="{$namePrefix}[placeholders][{$prompt.name}]" class="placeholders" rows="3" cols="45" style="width:98%;">{if is_string($prompt_value)}{$prompt_value}{/if}</textarea>
				{else}
					<input type="text" name="{$namePrefix}[placeholders][{$prompt.name}]" class="placeholders" style="width:98%;" value="{if is_string($prompt_value)}{$prompt_value}{/if}">
				{/if}
			{elseif $prompt.type == 'picklist'}
				<textarea name="{$namePrefix}[placeholders][{$prompt.name}]" class="placeholders" rows="3" cols="45" style="width:98%;">{if is_string($prompt_value)}{$prompt_value}{/if}</textarea>
				<div>
					<small>
					<b>{'common.options'|devblocks_translate|capitalize}:</b>
					{foreach from=$prompt.params.options item=option name=prompts}
						<code>{$option}</code>{if !$smarty.foreach.prompts.last}, {/if}
					{/foreach}
					</small>
				</div>
			{/if}
		</div>
	{/foreach}
	</div>
</div>
{/if}