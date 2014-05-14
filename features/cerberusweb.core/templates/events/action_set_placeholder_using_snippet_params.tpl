<div style="border:dotted 1px rgb(200,200,200);padding:5px;">
	<div style="margin-bottom:10px;font-weight:bold;font-size:120%;">{$snippet->title}</div>
	<input type="hidden" name="{$namePrefix}[snippet_id]" value="{$snippet->id}">

	<div class="snippet-placeholders">
	{foreach from=$snippet->custom_placeholders item=placeholder key=placeholder_key}
		<b>{$placeholder.label}</b>
		<div style="margin-left:10px;padding-bottom:5px;">
			{if $placeholder.type == Model_CustomField::TYPE_CHECKBOX}
				<select name="{$namePrefix}[placeholders][{$placeholder_key}]">
					<option value="1" {if $params.placeholders.$placeholder_key==1}selected="selected"{/if}>{'common.yes'|devblocks_translate|capitalize}</option>
					<option value="0" {if $params.placeholders.$placeholder_key==0}selected="selected"{/if}>{'common.no'|devblocks_translate|capitalize}</option>
				</select>
			{elseif $placeholder.type == Model_CustomField::TYPE_SINGLE_LINE}
				<input type="text" name="{$namePrefix}[placeholders][{$placeholder_key}]" class="placeholders" style="width:98%;" value="{$params.placeholders.$placeholder_key|default:$placeholder.default}">
			{elseif $placeholder.type == Model_CustomField::TYPE_MULTI_LINE}
				<textarea name="{$namePrefix}[placeholders][{$placeholder_key}]" class="placeholders" rows="3" cols="45" style="width:98%%;">{$params.placeholders.$placeholder_key|default:$placeholder.default}</textarea>
			{/if}
		</div>
	{/foreach}
	</div>
</div>