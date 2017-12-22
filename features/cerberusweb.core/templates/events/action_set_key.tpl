<b>{'common.key'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[key]" size="32" style="width:100%;" class="placeholders" value="{$params.key}" required="required" spellcheck="false">
</div>

<b>{'common.value'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[value]" rows="5" cols="45" style="height:5em;width:100%;" class="placeholders" required="required" spellcheck="false">{$params.value}</textarea>
</div>

<b>{'common.expires'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[expires_at]" size="32" style="width:100%;" class="placeholders" value="{$params.expires_at}" spellcheck="false">
	<div>
		<i>(e.g. &quot;1 day&quot;, &quot;next Friday 2pm&quot;; leave blank to never expire)</i>
	</div>
</div>

{*
<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
}
</script>
*}