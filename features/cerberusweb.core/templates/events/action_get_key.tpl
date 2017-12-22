<b>{'common.key'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[key]" size="32" style="width:100%;" class="placeholders" value="{$params.key}" required="required" spellcheck="false">
</div>

<b>Save value to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

{*
<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
}
</script>
*}