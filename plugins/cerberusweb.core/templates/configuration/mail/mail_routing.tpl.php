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
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveRouting">
			
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td nowrap="nowrap"><span style="font-weight:bold;">Priority</span></td>
					<td nowrap="nowrap"><span style="font-weight:bold;">Sent to</span></td>
					<td></td>
					<td nowrap="nowrap"><span style="font-weight:bold;">Deliver to</span></td>
					<td></td>
				</tr>

 				{counter name="routing" start=0 print=false}

				{foreach from=$routing item=route key=route_id}
				{assign var=mailbox_id value=$route->mailbox_id}
				{assign var=mailbox value=$mailboxes.$mailbox_id}
				<tr>
					<td width="0%" nowrap="nowrap">
						<input type="hidden" name="route_ids[]" value="{$route_id}">
						<input type="text" name="positions[]" value="{counter}" size="3">
					</td>
					<td width="0%" nowrap="nowrap">
						<label>{$route->pattern}</label></td>
					<td width="0%" nowrap="nowrap"> &#187; </td>
					<td width="0%" nowrap="nowrap"><span style="color:rgb(80,80,230);" id="mbox_routing_{$route->id}">{$mailbox->name}</span></td>
					<td width="100%" nowrap="nowrap">
						<a href="javascript:;" onclick="configAjax.showMailboxRouting('{$route->id}',this);">modify</a>
					</td>
				</tr>
				{/foreach}

			</table>
			<br>
			
			<b>Default Mailbox:</b> 
			<select name="default_mailbox_id">
				<option value="0">-- None (Bounce) --
			{if !empty($mailboxes)}
			{foreach from=$mailboxes item=mailbox key=mailbox_id}
				<option value="{$mailbox_id}" {if $settings->get('default_mailbox_id')==$mailbox_id}selected{/if}>{$mailbox->name}
			{/foreach}
			{/if}
			</select><br>
			<br>
			
			<input type="submit" value="Save Changes">
			</form>
		</td>
	</tr>
</table>
