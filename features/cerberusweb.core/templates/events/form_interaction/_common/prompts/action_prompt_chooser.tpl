<b>{'common.label'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[label]" class="placeholders">{$params.label}</textarea>
</div>

<b>{'common.record.type'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[record_type]">
		<option value=""></option>
		{foreach from=$record_contexts item=record_context}
			<option value="{$record_context->id}" {if $params.record_type==$record_context->id}selected="selected"{/if}>{$record_context->name}</option>
		{/foreach}
	</select>
</div>

<b>{'common.query'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[record_query]" class="placeholders">{$params.record_query}</textarea>
</div>

<b>{'common.query.required'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[record_query_required]" class="placeholders">{$params.record_query_required}</textarea>
</div>

<b>{'common.selection'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[selection]" value="single" {if $params.selection=='single'}checked="checked"{/if}> {'common.selection.single'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[selection]" value="multiple" {if $params.selection!='single'}checked="checked"{/if}> {'common.selection.multiple'|devblocks_translate|capitalize}</label>
</div>

<b>{'common.autocomplete'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[autocomplete]" value="0" {if !$params.autocomplete}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[autocomplete]" value="1" {if $params.autocomplete}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
</div>

<b>Save the response to a placeholder named:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#saving-placeholders"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<b>Validate the placeholder with this template:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#validation"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[var_validate]" class="placeholders">{$params.var_validate}</textarea>
</div>

