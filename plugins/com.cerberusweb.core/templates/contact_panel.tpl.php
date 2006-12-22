<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="images/businessman2.gif" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Contact</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="ajax.contactPanel.hide();"></form></td>
	</tr>
</table>
<div style="height:200px;overflow:auto;background-color:rgb(255,255,255);border:1px solid rgb(230,230,230);margin:2px;padding:3px;">
<b>Contact Information:</b><br>
[[name]] (ID: {$address->id})<br>
[[company]]<br>
[[address]]<br>
[[city]] [[state/prov]] [[zip/postal]]<br>
[[country]]<br>
[[phone]]<br>
{$address->email}<br>
Personal: {$address->personal}<br>
<br>

<b>Ticket History:</b><br>
<a href="javascript:;">find open tickets</a><br>
<a href="javascript:;">find all tickets</a><br>
<br>
</div>