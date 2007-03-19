<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td class="configTableTh">Mailbox Routing</td>
	</tr>
	<tr>
		<td style="background-color:rgb(240, 240, 240);border-bottom:1px solid rgb(130, 130, 130);">
			[ <a href="javascript:;" onclick="configAjax.showMailboxRouting('0',this);">add routing rule</a> ] 
			[ <a href="javascript:;" onclick="configAjax.showMailboxRoutingTest('0',this);">test routing</a> ]
		</td>
	</tr>
	<tr>
		<td>
			
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td nowrap="nowrap"><span style="font-weight:bold;">Destination Matches</span></td>
					<td></td>
					<td nowrap="nowrap"><span style="font-weight:bold;">Deliver to Mailbox</span></td>
					<td></td>
				</tr>

				{foreach from=$routing item=route_mailbox_id key=route_address_id}
				{assign var=route_address value=$routing_addresses.$route_address_id}
				{assign var=route_mailbox value=$mailboxes.$route_mailbox_id}
				<tr>
					<td width="0%" nowrap="nowrap">
					<label>{$route_address->email}</label></td>
					<td width="0%" nowrap="nowrap"> &#187; </td>
					<td width="0%" nowrap="nowrap"><span style="color:rgb(80,80,230);" id="mbox_routing_{$route_address_id}">{$route_mailbox->name}</span></td>
					<td width="100%" nowrap="nowrap">
						<a href="javascript:;" onclick="configAjax.showMailboxRouting('{$route_address_id}',this);">modify</a>
					</td>
				</tr>
				{/foreach}

			</table>
			
		</td>
	</tr>
</table>
