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
	<textarea name="{$namePrefix}[schema]" data-editor-mode="ace/mode/cerb_kata">{$params.schema}</textarea>
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	var $editor_schema = $action.find('textarea[name="{$namePrefix}[schema]"]');

	$editor_schema
		.addClass('placeholders')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaSheet
		})
	;
});
</script>
