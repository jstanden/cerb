<select name="{$namePrefix}[oper]">
	<option value="is" {if $params.oper == 'is'}selected="selected"{/if}>is</option>
	<option value="!is" {if $params.oper == '!is'}selected="selected"{/if}>is not</option>
</select>
<br>

<table cellspacing="5" cellpadding="0">
	<tr>
		<td align="center">Jan</td>
		<td align="center">Feb</td>
		<td align="center">Mar</td>
		<td align="center">Apr</td>
		<td align="center">May</td>
		<td align="center">Jun</td>
		<td align="center">Jul</td>
		<td align="center">Aug</td>
		<td align="center">Sep</td>
		<td align="center">Oct</td>
		<td align="center">Nov</td>
		<td align="center">Dec</td>
	</tr>
	<tr>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="1" {if in_array(1,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="2" {if in_array(2,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="3" {if in_array(3,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="4" {if in_array(4,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="5" {if in_array(5,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="6" {if in_array(6,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="7" {if in_array(7,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="8" {if in_array(8,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="9" {if in_array(9,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="10" {if in_array(10,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="11" {if in_array(11,$params.month)}checked="checked"{/if}></td>
		<td align="center"><input type="checkbox" name="{$namePrefix}[month][]" value="12" {if in_array(12,$params.month)}checked="checked"{/if}></td>
	</tr>
</table>
