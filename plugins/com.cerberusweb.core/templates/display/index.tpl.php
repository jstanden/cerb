<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td style="vertical-align: top;" width="200" nowrap="nowrap">
      	<form method="post" action="index.php" id="displayTicketProperties">
      	{include file="file:$path/display/properties.tpl.php"}
      	</form>
      	<br>
      	<form id="displayTicketRequesters">
      	{include file="file:$path/display/requesters.tpl.php"}
      	</form>
      </td>
      <td style="vertical-align: top;">
      	<h1>{$translate->say('common.ticket')|capitalize} #{$ticket->mask}: {$ticket->subject}</h1>
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