<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.context'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">e.g. "ticket"</span> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}</label>
	<input type="text" name="{$namePrefix}[context]" class="placeholders" spellcheck="false" value="{$params.context}" placeholder="e.g. ticket">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.id'|devblocks_translate}<span class="cerb-ui-form--hint">e.g. "123"</span></label>
	<input type="text" name="{$namePrefix}[id]" class="placeholders" spellcheck="false" value="{$params.id}" placeholder="e.g. 123">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also delete records in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
