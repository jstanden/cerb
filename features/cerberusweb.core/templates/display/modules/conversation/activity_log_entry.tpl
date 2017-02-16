<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td width="1%" valign="top" nowrap="nowrap" style="min-width:100px;">
				{$entry_timestamp = $entry->created|devblocks_prettytime}
				{if $entry_timestamp != $last_entry_timestamp}
					<span title="{$entry->created|devblocks_date}" style="margin-left:10px;">{$entry_timestamp}</span>
				{/if}
			</td>
			<td valign="top" width="99%">
				{$json = json_decode($entry->entry_json, true)}
				{CerberusContexts::formatActivityLogEntry($json, 'html-cards', ['target']) nofilter}
			</td>
		</tr>
	</tbody>
</table>