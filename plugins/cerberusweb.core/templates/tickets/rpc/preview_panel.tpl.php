<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%"><h1>{$ticket->subject}</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

{assign var=headers value=$message->getHeaders()}
<b>To:</b> {$headers.to|escape:"htmlall"}<br>
<b>From:</b> {$headers.from|escape:"htmlall"}<br>
<div style="width:98%;height:300px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);" ondblclick="if(null != genericPanel) genericPanel.hide();">
{$content|escape:"htmlall"|nl2br}
</div>
