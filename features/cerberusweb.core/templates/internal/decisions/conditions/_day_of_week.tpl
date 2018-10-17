<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper == 'is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper == '!is'}selected="selected"{/if}>is not</option>
</select>
<br>

<table cellspacing="5" cellpadding="0">
	<tr>
		<td align="center">{'common.day.monday.abbr'|devblocks_translate|capitalize}</td>
		<td align="center">{'common.day.tuesday.abbr'|devblocks_translate|capitalize}</td>
		<td align="center">{'common.day.wednesday.abbr'|devblocks_translate|capitalize}</td>
		<td align="center">{'common.day.thursday.abbr'|devblocks_translate|capitalize}</td>
		<td align="center">{'common.day.friday.abbr'|devblocks_translate|capitalize}</td>
		<td align="center">{'common.day.saturday.abbr'|devblocks_translate|capitalize}</td>
		<td align="center">{'common.day.sunday.abbr'|devblocks_translate|capitalize}</td>
	</tr>
	<tr>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="1" {if is_array($params.day) && in_array(1,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="2" {if is_array($params.day) && in_array(2,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="3" {if is_array($params.day) && in_array(3,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="4" {if is_array($params.day) && in_array(4,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="5" {if is_array($params.day) && in_array(5,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="6" {if is_array($params.day) && in_array(6,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="7" {if is_array($params.day) && in_array(7,$params.day)}checked="checked"{/if}></td>
	</tr>
</table>
