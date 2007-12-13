<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Bulk Update</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ticket_ids" value="">
<div style="height:400px;overflow:auto;">

<h2>With:</h2>

<label><input type="radio" name="filter" value="" onclick="toggleDiv('bulkUpdateLearn','none');toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');" {if empty($ticket_ids)}checked{/if}> Whole list</label> 
<label><input type="radio" name="filter" value="checks" onclick="toggleDiv('bulkUpdateLearn','none');toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');"> Only checked</label> 
<label><input type="radio" name="filter" value="sender" onclick="toggleDiv('bulkUpdateLearn','block');toggleDiv('categoryFilterPanelSender','block');toggleDiv('categoryFilterPanelSubject','none');" {if !empty($ticket_ids)}checked{/if}> Similar senders</label>
<label><input type="radio" name="filter" value="subject" onclick="toggleDiv('bulkUpdateLearn','block');toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','block');"> Similar subjects</label>
<br>
<br>

<div style='display:{if empty($ticket_ids)}none{else}block{/if};' id='categoryFilterPanelSender'>
<label><b>When sender matches:</b> (one per line, use * for wildcards)</label><br>
<textarea rows='3' cols='45' style='width:95%' name='senders' wrap="off">{foreach from=$unique_senders key=sender item=total name=senders}{$sender}{if !$smarty.foreach.senders.last}{"\n"}{/if}{/foreach}</textarea><br>
<br>
</div>

<div style='display:none;' id='categoryFilterPanelSubject'>
<label><b>When subject matches:</b> (one per line, use * for wildcards)</label><br>
<textarea rows='3' cols='45' style='width:95%' name='subjects' wrap="off">{foreach from=$unique_subjects key=subject item=total name=subjects}{$subject}{if !$smarty.foreach.subjects.last}{"\n"}{/if}{/foreach}</textarea><br>
<br>
</div>

<div id="bulkUpdateCustom" style="display:block;">
<H2>Do:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap">Move to:</td>
		<td width="100%"><select name="team">
			<option value=""></option>
      		<optgroup label="Move to Group">
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
      	</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Status:</td>
		<td width="100%"><select name="closed">
			<option value=""></option>
			{foreach from=$statuses item=k key=v}
			<option value="{$v}">{$k}</option>
			{/foreach}
			{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}
			<option value="2">Deleted</option>
			{/if}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Spam:</td>
		<td width="100%"><select name="spam">
			<option value=""></option>
			{foreach from=$training item=k key=v}
			<option value="{$v}">{$k}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Next Worker:</td>
		<td width="100%"><select name="next_worker">
			<option value=""></option>
			<option value="0">Anybody</option>
			{foreach from=$workers item=worker key=worker_id name=workers}
				{if $worker_id==$active_worker->id}{math assign=next_worker_id_sel equation="x+1" x=$smarty.foreach.workers.iteration}{/if}
				<option value="{$worker_id}">{$worker->getName()}</option>
			{/foreach}
		</select>
      	{if !empty($next_worker_id_sel)}
      		<button type="button" onclick="this.form.next_worker.selectedIndex = {$next_worker_id_sel};">me</button>
      		<button type="button" onclick="this.form.next_worker.selectedIndex = 1;">anybody</button>
      	{/if}
		</td>
	</tr>
</table>

<br>
</div>

<div id="bulkUpdateLearn" style="display:{if empty($ticket_ids)}none{else}block{/if};">
{*
<H2>And in the future:</H2>
<label><input type="checkbox" name="always" value="1"> Do this with mail in selected groups</label><br>
<br>
*}
</div>

<button type="button" onclick="ajax.saveBatchPanel('{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>