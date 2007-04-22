<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td style="vertical-align: top;" width="200" nowrap="nowrap">
      	<form method="post" action="{devblocks_url}{/devblocks_url}" id="displayTicketProperties">
      	{include file="file:$path/display/properties.tpl.php"}
      	</form>
      	<br>
      	<form id="displayTicketRequesters">
      	{include file="file:$path/display/requesters.tpl.php"}
      	</form>
      </td>
      <td style="vertical-align: top;">
      	<h1>{$translate->_('common.ticket')|capitalize} #{$ticket->mask}: {$ticket->subject}</h1>
			<a href="#latest" style="font-size:90%;">jump to latest message</a> | <a href="#" style="font-size:90%;">customize page layout</a><br>
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
<script>
	var displayAjax = new cDisplayTicketAjax('{$ticket->id}');
	//ajax.addAddressAutoComplete("addRequesterEntry","addRequesterContainer", true);
</script>