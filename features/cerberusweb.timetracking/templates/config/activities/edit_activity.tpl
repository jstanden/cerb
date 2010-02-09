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
			<h2>{$translate->_('timetracking.ui.cfg.add_activity')}</h2>
			{else}
			<h2>{'timetracking.ui.cfg.modify'|devblocks_translate:$activity->name}</h2>
			
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>{$translate->_('timetracking.ui.cfg.name')}</b></td>
		<td width="100%"><input type="text" id="activityForm_name" name="name" value="{$activity->name}" size="45"> {$translate->_('timetracking.ui.cfg.name.hint')}</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap"><b>{$translate->_('timetracking.ui.cfg.rate')}</b></td>
		<td width="100%">{$translate->_('timetracking.ui.cfg.currency')} <input type="text" id="activityForm_rate" name="rate" value="{$activity->rate}" size="10"> {$translate->_('timetracking.ui.cfg.per_hour')} {$translate->_('timetracking.ui.cfg.rate.hint')}</td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td colspan="2">
			<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($activity)}<button type="button" onclick="if(confirm('Are you sure you want to delete this activity?')){literal}{{/literal}this.form.do_delete.value=1;this.form.submit();{literal}}{/literal}"><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>