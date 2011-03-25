<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

<label><input type="checkbox" name="{$namePrefix}[values][]" value="open" {if in_array('open',$params.values)}checked="checked"{/if}> {'status.open'|devblocks_translate}</label><br>
<label><input type="checkbox" name="{$namePrefix}[values][]" value="waiting" {if in_array('waiting',$params.values)}checked="checked"{/if}> {'status.waiting'|devblocks_translate}</label><br>
<label><input type="checkbox" name="{$namePrefix}[values][]" value="closed" {if in_array('closed',$params.values)}checked="checked"{/if}> {'status.closed'|devblocks_translate}</label><br>
<label><input type="checkbox" name="{$namePrefix}[values][]" value="deleted" {if in_array('deleted',$params.values)}checked="checked"{/if}> {'status.deleted'|devblocks_translate}</label><br>
