<h1>{$translate->_('common.ticket')|capitalize} #{$ticket->mask}: {$ticket->subject}</h1>
<form action="">
	<button type="button">Close</button>
	<button type="button">Report Spam</button>
	<button type="button">Delete</button>
	
	<a href="#latest">jump to latest message</a>	
</form>
<br>

<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td style="vertical-align: top;">
			{foreach from=$display_modules item=display_module}
				{$display_module->render($ticket)}
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