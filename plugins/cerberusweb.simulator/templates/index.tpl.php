<H1>Simulator</H1>
<br>

<form method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="simulator">
<input type="hidden" name="a" value="generateTickets">

<H2>Sample Tickets</H2>

<table cellpadding="5" cellspacing="0" border="0">
	<tr>
		<td>
			<b>To Address:</b><br>
			<input type="text" name="address" value="{$address}">
		</td>
		<td>
			<b>Sample Data Flavor:</b><br>
			<select name="dataset">
				{foreach from=$flavors item=flavor key=flavor_id}
					<option value="{$flavor_id}">{$flavor}
				{/foreach}
			</select>
		</td>
		<td>
			<b>How Many?</b><br>
			<select name="how_many">
				{foreach from=$how_many_opts item=num}
					<option value="{$num}">{$num}
				{/foreach}
			</select>
		</td>
	</tr>
</table>
<br>

<input type="submit" value="Generate">
	
</form>