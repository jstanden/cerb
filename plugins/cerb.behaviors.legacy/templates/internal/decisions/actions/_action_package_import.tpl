<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.package'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">JSON</span></label>
	<textarea id="{$namePrefix}_package_json_{$nonce}" name="{$namePrefix}[package_json]" data-editor-lines="20" spellcheck="false">{$params.package_json}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.params'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">JSON</span></label>
	<textarea name="{$namePrefix}[prompts_json]" class="placeholders" spellcheck="false" placeholder="e.g. ticket">{$params.prompts_json}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also import packages in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save package results to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	new CerbUI.JsonEditor($action.find('#{$namePrefix}_package_json_{$nonce}')[0], { validate: true, minLines: 5 });
});
</script>
