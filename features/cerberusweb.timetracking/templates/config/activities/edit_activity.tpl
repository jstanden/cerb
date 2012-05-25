<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="timetracking">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="{if !empty($activity->id)}{$activity->id}{else}0{/if}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>
		{if empty($activity->id)}
		{$translate->_('timetracking.ui.cfg.add_activity')}
		{else}
		{'timetracking.ui.cfg.modify'|devblocks_translate:$activity->name}
		{/if}
	</legend>
	
	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td width="0%" nowrap="nowrap"><b>{$translate->_('timetracking.ui.cfg.name')}</b></td>
			<td width="100%"><input type="text" id="activityForm_name" name="name" value="{$activity->name}" size="45"> {$translate->_('timetracking.ui.cfg.name.hint')}</td>
		</tr>
		
		<tr>
			<td colspan="2">
				<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
				{if !empty($activity)}<button type="button" onclick="if(confirm('Are you sure you want to delete this activity?')){literal}{{/literal}this.form.do_delete.value=1;this.form.submit();{literal}}{/literal}"><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
			</td>
		</tr>
	</table>
</fieldset>
