<fieldset class="peek">
	<legend>{$translate->_('reports.ui.org.shared_sender_domains')}</legend>

	<table cellpadding="5" cellspacing="0">
		<tr>
			<td align="center"><b>{$translate->_('reports.ui.org.shared_sender_domains.num_orgs')}</b></td>
			<td><b>{$translate->_('reports.ui.org.shared_sender_domains.domain')}</b></td>
		</tr>
	
		{foreach from=$top_domains key=domain item=count}
		<tr>
			<td align="center">{$count}</td>
			<td>{$domain}</td>
		</tr>
		{/foreach}
	</table>
</fieldset>