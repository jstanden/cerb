<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

<label><input type="checkbox" name="{$namePrefix}[values][]" value="" {if in_array('',$params.values)}checked="checked"{/if}> Undecided</label><br>
<label><input type="checkbox" name="{$namePrefix}[values][]" value="N" {if in_array('N',$params.values)}checked="checked"{/if}> {'common.notspam'|devblocks_translate|capitalize}</label><br>
<label><input type="checkbox" name="{$namePrefix}[values][]" value="S" {if in_array('S',$params.values)}checked="checked"{/if}> {'common.spam'|devblocks_translate|capitalize}</label><br>
