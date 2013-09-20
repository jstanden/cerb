<input type="hidden" name="oper" value="=">

<b>{'search.value'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<label><input type="radio" name="bool" value="1" {if !empty($param->value)}checked="checked"{/if}>{'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="bool" value="0" {if !empty($param) && empty($param->value)}checked="checked"{/if}>{'common.no'|devblocks_translate|capitalize}</label>
	<br>
</blockquote>

