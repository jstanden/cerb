<b>{'common.label'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[label]" class="placeholders">{$params.label}</textarea>
</div>

<b>{'common.data'|devblocks_translate|capitalize}:</b> (JSON) {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#sheet"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[data]" class="placeholders">{$params.data}</textarea>
</div>

<b>{'common.schema'|devblocks_translate|capitalize}:</b> (KATA) {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/sheets/"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[schema]" data-editor-mode="ace/mode/yaml">{$params.schema}</textarea>
</div>

<b>{'common.selection'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[mode]" value="" {if $params.mode != 'multiple'}checked="checked"{/if}> {'common.selection.single'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[mode]" value="multiple" {if $params.mode == 'multiple'}checked="checked"{/if}> {'common.selection.multiple'|devblocks_translate|capitalize}</label>
</div>

<b>Selection key:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[selection_key]" class="placeholders">{$params.selection_key}</textarea>
</div>

<b>Save the response to a placeholder named:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#saving-placeholders"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<b>Format the placeholder with this template:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#formatting"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[var_format]" class="placeholders">{$params.var_format}</textarea>
</div>

<b>Validate the placeholder with this template:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#validation"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[var_validate]" class="placeholders">{$params.var_validate}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	var $editor_schema = $action.find('textarea[name="{$namePrefix}[schema]"]');

	$editor_schema
		.addClass('placeholders')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteYaml({
			autocomplete_suggestions: cerbAutocompleteSuggestions.yamlSheetSchema
		})
	;
});
</script>
