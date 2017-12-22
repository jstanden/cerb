<b>{'common.message'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[message_source]" class="placeholders" spellcheck="false" cols="45" rows="5" style="width:100%;">{$params.message_source|default:""}</textarea>
</div>

<b>Also parse messages in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save result to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_result"}" required="required" spellcheck="false" size="32" placeholder="e.g. _result">&#125;&#125;
	<div>
	(with properties: <tt>.ticket_id</tt> &nbsp; <tt>.error</tt>)
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
