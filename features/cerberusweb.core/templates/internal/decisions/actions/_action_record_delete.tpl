<b>{'common.context'|devblocks_translate|capitalize}:</b> <i>(e.g. "ticket")</i> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[context]" class="placeholders" spellcheck="false" style="width:100%;" value="{$params.context}" placeholder="e.g. ticket">
</div>

<b>{'common.id'|devblocks_translate}:</b> <i>(e.g. "123")</i>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[id]" class="placeholders" spellcheck="false" style="width:100%;" value="{$params.id}" placeholder="e.g. 123">
</div>

<b>Also delete records in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
