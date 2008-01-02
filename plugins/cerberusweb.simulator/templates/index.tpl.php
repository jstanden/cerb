<H1>Simulator</H1>

{if !empty($error)}
<div class="error">{$error}</div>
{elseif !empty($output)}
<div class="success">{$output}</div>
{else}
<br>
{/if}

<div class="block">
<form method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="simulator">
<input type="hidden" name="a" value="generateTickets">

<H2>Sample Tickets</H2>

<table cellpadding="5" cellspacing="0" border="0">
	<tr>
		<td>
			<b>To Address:</b><br>
			<input type="text" name="address" value="{$address}" size="45">
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

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="top"> Generate</button>
	
</form>
</div>
