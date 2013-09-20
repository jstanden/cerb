<fieldset class="peek">
	<legend>{'reports.ui.org.shared_sender_domains'|devblocks_translate}</legend>

	<table cellpadding="5" cellspacing="0">
		<tr>
			<td align="center"><b>{'reports.ui.org.shared_sender_domains.num_orgs'|devblocks_translate}</b></td>
			<td><b>{'reports.ui.org.shared_sender_domains.domain'|devblocks_translate}</b></td>
		</tr>
	
		{foreach from=$top_domains key=domain item=count}
		<tr>
			<td align="center">{$count}</td>
			<td>{$domain}</td>
		</tr>
		{/foreach}
	</table>
</fieldset>