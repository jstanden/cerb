<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="addCriteria">
<div class="tableThGreen">Add Search Criteria</div>
<div class="bd">

<table cellpadding="0" cellspacing="2" border="0" width="100%">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap" bgcolor="red">
			<select size="10" name='field' id="{$divName}_field" onchange="ajax.getSearchCriteria('{$divName}',this.options[this.selectedIndex].value)" onkeydown="ajax.getSearchCriteria('{$divName}',this.options[this.selectedIndex].value)">
				<option value=''>-- select criteria --
				<option value='t_mask'>{$translate->_('ticket.mask')}
				<option value='t_status'>{$translate->_('ticket.status')}
				<option value='t_priority'>{$translate->_('ticket.priority')}
				<option value='t_subject'>{$translate->_('ticket.subject')}
				<option value='tm_id'>{$translate->_('common.team')}
				<option value='ra_email'>{$translate->_('requester')}
				<option value='msg_content'>{$translate->_('message.content')}
			</select>
		</td>
		
		<td valign="top" width="100%">
			<div id='{$divName}_render'></div>
		</td>
	</tr>
</table>

<br>
<input type="submit" value="{$translate->_('common.save_changes')|capitalize}"><input type="button" value="{$translate->_('common.cancel')|capitalize}" onclick="searchDialogs['{$divName}'].hide();">

</div>
</form>