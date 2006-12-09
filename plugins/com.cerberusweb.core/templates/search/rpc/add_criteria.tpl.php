<form action="index.php" method="POST">
<input type="hidden" name="c" value="core.module.search">
<input type="hidden" name="a" value="addCriteria">
<div class="tableThGreen">Add Search Criteria</div>
<div class="bd">
	<b>Add Criteria:</b>
		<select name='field' id="{$divName}_field" onchange="ajax.getSearchCriteria('{$divName}',this.options[this.selectedIndex].value)" onkeydown="ajax.getSearchCriteria('{$divName}',this.options[this.selectedIndex].value)">
			<option value=''>-- select criteria --
			<option value='t.mask'>{$translate->say('ticket.mask')}
			<option value='t.status'>{$translate->say('ticket.status')}
			<option value='t.priority'>{$translate->say('ticket.priority')}
			<option value='t.subject'>{$translate->say('ticket.subject')}
		</select>
		<br>
		<div id='{$divName}_render'></div>
		<input type="submit" value="{$translate->say('common.save_changes')|capitalize}"><input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="searchDialogs['{$divName}'].hide();">
</div>
</form>