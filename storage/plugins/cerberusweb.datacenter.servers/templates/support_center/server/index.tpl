<div id="server">
	{if !empty($view)}
		<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td nowrap="nowrap"><h2>{$view->name}</h2></td>
			</tr>
		</table>
		{if !empty($contact)}
			<form action="{devblocks_url}c=server&a=newjournalentry{/devblocks_url}" method="post">
				<button type="button" onclick="this.form.submit();" style="margin:10px 0;">
					<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/add.png{/devblocks_url}" align="top" alt="new" />
					{$translate->_('common.new_journal_entry')}
				</button>
			</form>
			<form action="#" method="POST" id="filters_{$view->id}">
				{include file="devblocks:cerberusweb.datacenter.servers:portal_{$portal_code}:support_center/internal/view/view_filters.tpl" view=$view}
			</form>
		{/if}
		<div id="view{$view->id}">
			{$view->render()}
		</div>
	{else}
		<div class="message">{'portal.sc.public.server.empty'|devblocks_translate}</div>
	{/if}
</div>