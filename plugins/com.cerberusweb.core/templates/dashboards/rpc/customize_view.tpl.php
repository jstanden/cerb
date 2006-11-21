<div style="background-color: #EEEEEE;padding:5px;">
<h1>{$translate->say('common.customize')|capitalize}</h1>
<b>{$translate->say('common.name')|capitalize}:</b> <br>
<input type="text" name="name" value="My Tickets" size="45"><br>
<br>
<b>{$translate->say('dashboard.columns')|capitalize}:</b><br>
1: 
<select name="">
	<option value="">-- {$translate->say('dashboard.choose_column')|lower} --
</select>
<br>
2:
<select name="">
	<option value="">-- {$translate->say('dashboard.choose_column')|lower} --
</select>
<br>
3:
<select name="">
	<option value="">-- {$translate->say('dashboard.choose_column')|lower} --
</select>
<br>
4: 
<select name="">
	<option value="">-- {$translate->say('dashboard.choose_column')|lower} --
</select>
<br>
...<br>
<br>
<input type="button" value="{$translate->say('common.save_changes')|capitalize}">
<input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="ajax.getCustomize('{$id}');">
<br>
<br>
</div>