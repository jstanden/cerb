<label>
	<input type="radio" name="{$namePrefix}[value]" value="1" {if !empty($params.value)}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}
</label>
<label>
	<input type="radio" name="{$namePrefix}[value]" value="0" {if empty($params.value)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}
</label>
