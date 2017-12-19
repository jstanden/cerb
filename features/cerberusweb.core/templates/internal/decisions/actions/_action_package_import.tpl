<b>{'common.package'|devblocks_translate|capitalize}:</b> (JSON)
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[package_json]" class="cerb-json-editor" data-editor-mode="ace/mode/json" spellcheck="false" rows="5" style="width:100%;">{$params.package_json}</textarea>
</div>

<b>{'common.params'|devblocks_translate|capitalize}:</b> (JSON)
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[prompts_json]" class="placeholders" spellcheck="false" style="width:100%;" placeholder="e.g. ticket">{$params.prompts_json}</textarea>
</div>

<b>Also import packages in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save package results to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	
	$action.find('.cerb-json-editor')
		.cerbCodeEditor()
		;
});
</script>
