<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%"><h1>Categorize</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formCategorize" name="formCategorize">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doSaveCategoryFilterPanel">

<div style="border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);">

<h2>Auto categorize the entire list:</h2>

<label><input type="radio" name="filter" value="" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');" checked> No thanks</label> 
<label><input type="radio" name="filter" value="sender" onclick="toggleDiv('categoryFilterPanelSender','block');toggleDiv('categoryFilterPanelSubject','none');"> By sender</label>
<label><input type="radio" name="filter" value="subject" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','block');"> By subject</label>
<br>
<br>

<div style='display:none;' id='categoryFilterPanelSender'>
<label><b>When sender matches:</b> (one per line, use * for wildcards)</label><br>
<textarea rows='5' cols='45' style='width:95%' name='senders' wrap="off">{foreach from=$unique_senders key=sender item=total name=senders}{$sender}{if !$smarty.foreach.senders.last}{"\n"}{/if}{/foreach}</textarea><br>
<br>
</div>

<div style='display:none;' id='categoryFilterPanelSubject'>
<label><b>When subject matches:</b> (one per line, use * for wildcards)</label><br>
<textarea rows='5' cols='45' style='width:95%' name='subjects' wrap="off">{foreach from=$unique_subjects key=subject item=total name=subjects}{$subject}{if !$smarty.foreach.subjects.last}{"\n"}{/if}{/foreach}</textarea><br>
<br>
</div>

<!-- 
<div style='display:none;' id='categoryFilterPanelFuture'>
<label><input type="checkbox"> Apply this filter to all tickets in the current list</label><br>
<label><input type="checkbox"> Always apply this filter in the future</label><br>
<br>
</div>
-->

<h2>Move selected tickets to:</h2>

<select name="team">
	<optgroup label="Team (No Category)">
		{foreach from=$teams item=team}
			<option value="t{$team->id}">{$team->name}</option>
		{/foreach}
		</optgroup>
		{foreach from=$team_categories item=categories key=teamId}
			{assign var=team value=$teams.$teamId}
			<optgroup label="{$team->name}">
			{foreach from=$categories item=category}
		<option value="c{$category->id}">{$category->name}</option>
	{/foreach}
	</optgroup>
	{/foreach}
</select><br>
<br>

</div>

<button type="button" onclick="ajax.saveCategorizePanel('{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>