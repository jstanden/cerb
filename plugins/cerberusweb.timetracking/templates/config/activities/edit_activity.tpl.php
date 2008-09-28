<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="timetracking.config.tab.activities">
<input type="hidden" name="plugin_id" value="{$plugin->id}">
<input type="hidden" name="id" value="{if !empty($activity->id)}{$activity->id}{else}0{/if}">
<input type="hidden" name="do_delete" value="0">

<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($activity->id)}
			<h2>Add Activity</h2>
			{else}
			<h2>Modify '{$activity->name}'</h2>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Name:</b></td>
		<td width="100%"><input type="text" id="activityForm_name" name="name" value="{$activity->name}" size="45"> (e.g. "Troubleshooting")</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap"><b>Rate:</b></td>
		<td width="100%">US$ <input type="text" id="activityForm_rate" name="rate" value="{$activity->rate}" size="10"> per hour (e.g. 65.00)</td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td colspan="2">
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($activity)}<button type="button" onclick="if(confirm('Are you sure you want to delete this activity?')){literal}{{/literal}this.form.do_delete.value=1;this.form.submit();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>