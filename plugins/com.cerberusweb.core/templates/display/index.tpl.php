<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td style="vertical-align: top;" width="200" nowrap="nowrap">
      	{include file="file:$path/display/properties.tpl.php"}
      	<br>
      	{include file="file:$path/display/requesters.tpl.php"}
      </td>
      <td style="vertical-align: top;">
      	<h1>{$translate->say('common.ticket')|capitalize} #{$ticket->mask}: {$ticket->subject}</h1>
			<b>Status:</b> open &nbsp;
			<b>Priority:</b> <img src="images/star_alpha.gif" width="16" height="16" align="absmiddle"> None &nbsp;
			<b>Due:</b> Wed Nov 29 2006 01:46PM &nbsp;
			<br>
			<a href="#" style="font-size:90%;">jump to latest message</a> | <a href="#" style="font-size:90%;">customize page layout</a><br>
			<br>
			{foreach from=$display_modules item=display_module}
				{$display_module->render($ticket)}
				<br>
			{foreachelse}
				No modules.
			{/foreach}
		</td>
    </tr>
  </tbody>
</table>