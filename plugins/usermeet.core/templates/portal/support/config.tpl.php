<h2>Look &amp; Feel</h2>

<b>URL to Logo:</b> (link to image, default if blank)<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<!-- 
<b>Theme (CSS) URL:</b> (default if blank)<br>
<input type="text" size="45" name="logo_url" value=""><br>
<br>
 -->

<h2>Contact Us</h2>

<table cellpadding="2" cellspacing="0" width="0">
	<tr>
		<td><b>Reason for contacting:</b></td>
		<td>&nbsp; <b>Deliver to:</b> (helpdesk e-mail address)</td>
	</tr>

	<!--- Established Routing --->
	{foreach from=$dispatch item=to key=reason}
	<tr>
		<td><input type="text" name="reason[]" size="45" style="width:98%;" value="{$reason}"></td>
		<td>&raquo; <input type="text" name="to[]" size="45" value="{$to}"></td>
	</tr>
	{/foreach}
	
	<!--- Always give 5 new rows --->
	<tr>
		<td>Add... (e.g. "I'd like more info on your products")</td>
		<td>&nbsp; (leave blank for {$default_from})</td>
	</tr>
	{section name="dispatch" start=0 loop=5}
	<tr>
		<td><input type="text" name="reason[]" size="45" style="width:98%;"></td>
		<td>&raquo; <input type="text" name="to[]" size="45"></td>
	</tr>
	{/section}
</table>
<br>

<!-- 
<b>Categories:</b> (one per line)<br>
<textarea rows="5" cols="45"></textarea>
<br>
-->
 