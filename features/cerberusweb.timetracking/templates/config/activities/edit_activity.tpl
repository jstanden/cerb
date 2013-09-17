<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="timetracking">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="{if !empty($activity->id)}{$activity->id}{else}0{/if}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>
		{if empty($activity->id)}
		{'timetracking.ui.cfg.add_activity'|devblocks_translate}
		{else}
		{'timetracking.ui.cfg.modify'|devblocks_translate:$activity->name}
		{/if}
	</legend>
	
	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td width="0%" nowrap="nowrap"><b>{'timetracking.ui.cfg.name'|devblocks_translate}</b></td>
			<td width="100%"><input type="text" id="activityForm_name" name="name" value="{$activity->name}" size="45"> {'timetracking.ui.cfg.name.hint'|devblocks_translate}</td>
		</tr>
		
		<tr>
			<td colspan="2">
				<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if !empty($activity)}<button type="button" onclick="if(confirm('Are you sure you want to delete this activity?')){literal}{{/literal}this.form.do_delete.value=1;this.form.submit();{literal}}{/literal}"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
			</td>
		</tr>
	</table>
</fieldset>
