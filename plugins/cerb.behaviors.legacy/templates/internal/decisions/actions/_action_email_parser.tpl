<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.message'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[message_source]" class="placeholders" spellcheck="false" rows="5">{$params.message_source|default:""}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also parse messages in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save result to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_result"}" required="required" spellcheck="false" size="32" placeholder="e.g. _result">&#125;&#125;
	</div>
	<div class="cerb-ui-form--help">
		(with properties: <tt>.ticket_id</tt> &nbsp; <tt>.error</tt>)
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
