<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveSearch">
<label><input type="radio" name="save_as" value="view"> Save as view:</label><br>
<select name="view_id" onfocus="this.form.save_as[0].checked=true;">
	{foreach from=$views item=view}
		<option value="{$view->id}">{$view->name} ({$view->dashboard_id})
	{/foreach}
</select>
<br>
<label><input type="radio" name="save_as" value="search" checked> Named search:</label><br>
<input type="text" name="name" size="24" onfocus="this.form.save_as[1].checked=true;">
<br>
<input type="button" onclick="ajax.saveSearch('{$divName}');" value="{$translate->say('common.save')|capitalize}">
<input type="button" onclick="clearDiv('{$divName}_control');" value="{$translate->say('common.cancel')|capitalize}">