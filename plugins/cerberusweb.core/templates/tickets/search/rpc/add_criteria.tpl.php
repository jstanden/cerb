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
				<option value='t_mask'>{$translate->_('ticket.mask')|capitalize}
				<option value='t_is_closed'>{$translate->_('ticket.status')|capitalize}
				<option value='t_subject'>{$translate->_('ticket.subject')|capitalize}
				<option value='tm_id'>{$translate->_('common.team')|capitalize}
				<option value='t_category_id'>{$translate->_('common.bucket')|capitalize}
				<option value='ra_email'>{$translate->_('requester')|capitalize}
				<option value='mc_content'>{$translate->_('message.content')|capitalize}
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