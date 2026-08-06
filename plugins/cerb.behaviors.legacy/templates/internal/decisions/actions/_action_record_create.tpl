<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.context'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">e.g. "ticket"</span> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}</label>
	<input type="text" name="{$namePrefix}[context]" class="placeholders" spellcheck="false" value="{$params.context}" placeholder="e.g. ticket">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.changeset'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">Records API JSON</span> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}</label>
	<textarea name="{$namePrefix}[changeset_json]" class="placeholders" spellcheck="false" rows="5">{$params.changeset_json}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also create records in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save record dictionary to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_record"}" required="required" spellcheck="false" size="32" placeholder="e.g. _record">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
