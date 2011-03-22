<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper == 'is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper == '!is'}selected="selected"{/if}>is not</option>
</select>
<br>

<table cellspacing="5" cellpadding="0">
	<tr>
		<td align="center">Sun</td>
		<td align="center">Mon</td>
		<td align="center">Tue</td>
		<td align="center">Wed</td>
		<td align="center">Thu</td>
		<td align="center">Fri</td>
		<td align="center">Sat</td>
	</tr>
	<tr>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="7" {if in_array(7,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="1" {if in_array(1,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="2" {if in_array(2,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="3" {if in_array(3,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="4" {if in_array(4,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="5" {if in_array(5,$params.day)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[day][]" value="6" {if in_array(6,$params.day)}checked="checked"{/if}></td>
	</tr>
</table>
