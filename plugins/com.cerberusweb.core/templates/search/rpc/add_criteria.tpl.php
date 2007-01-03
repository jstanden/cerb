<form action="index.php" method="POST">
<input type="hidden" name="c" value="core.module.search">
<input type="hidden" name="a" value="addCriteria">
<div class="tableThGreen">Add Search Criteria</div>
<div class="bd">

<table cellpadding="0" cellspacing="2" border="0" width="100%">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap" bgcolor="red">
			<select size="10" name='field' id="{$divName}_field" onchange="ajax.getSearchCriteria('{$divName}',this.options[this.selectedIndex].value)" onkeydown="ajax.getSearchCriteria('{$divName}',this.options[this.selectedIndex].value)">
				<option value=''>-- select criteria --
				<option value='t_mask'>{$translate->say('ticket.mask')}
				<option value='t_status'>{$translate->say('ticket.status')}
				<option value='t_priority'>{$translate->say('ticket.priority')}
				<option value='t_subject'>{$translate->say('ticket.subject')}
				<option value='att_agent_id'>{$translate->say('workflow.assigned')}
				<option value='stt_agent_id'>{$translate->say('workflow.suggested')}
			</select>
		</td>
		
		<td valign="top" width="100%">
			<div id='{$divName}_render'></div>
		</td>
	</tr>
</table>

<br>
<input type="submit" value="{$translate->say('common.save_changes')|capitalize}"><input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="searchDialogs['{$divName}'].hide();">

</div>
</form>