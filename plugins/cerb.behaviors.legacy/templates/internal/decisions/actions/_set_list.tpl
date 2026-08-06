<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[mode]" value="delta" {if $params.mode!='replace'}checked="checked"{/if}> Add/Remove</label>
		<label><input type="radio" name="{$namePrefix}[mode]" value="replace" {if $params.mode=='replace'}checked="checked"{/if}> {'common.replace'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">One value per line</label>
	<textarea name="{$namePrefix}[values]" data-editor-lines="4" spellcheck="false">{$params.values}</textarea>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $condition = $('#{$namePrefix}_{$nonce}');
	// One value per line — a plain ScriptingEditor (no autocomplete, no Twig).
	var listEl = $condition.find('textarea[name="{$namePrefix}[values]"]')[0];
	if(listEl && window.CerbUI && CerbUI.ScriptingEditor)
		new CerbUI.ScriptingEditor(listEl);
})
</script>