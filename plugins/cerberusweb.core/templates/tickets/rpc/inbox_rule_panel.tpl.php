<h1>Create Inbox Routing Rule</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSaveAddInboxRule">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveAddInboxRulePanel">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="team_id" value="{$ticket->team_id}">

<b>If incoming ticket:</b>
<!-- 
<label><input type="radio" name="match" value="all" checked> all of the following</label> 
<label><input type="radio" name="match" value="any"> any of the following</label>
 --> 
<br>

<table>
	<tr>
		<td valign="top">
			<select name="field" onchange="this.form.val.value=(selectValue(this)=='sender')?this.form.example_sender.value:this.form.example_subject.value;">
				<option value="sender">From</option>
				<option value="subject">Subject</option>
			</select>
		</td>
		<td valign="top">
			<input type="hidden" name="example_sender" value="{$first_address->email|escape}">
			<input type="hidden" name="example_subject" value="{$ticket->subject|escape}">
			<input type="text" name="val" size="45" style="width:98%;" value="{$first_address->email}"><br>
			<i>use asterisk (*) for wildcards</i><br>
		</td>
	</tr>
</table>

<b>Then:</b><br>
<table>
	<tr>
		<td>Move to:</td>
		<td>
			<select name="move">
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
			</select>
		</td>
	</tr>
	<tr>
		<td>Status:</td>
		<td>
			<select name="status">
				<option value=""></option>
				{foreach from=$statuses item=k key=v}
				<option value="{$v}">{$k}</option>
				{/foreach}
				{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}
				<option value="2">Deleted</option>
				{/if}
			</select>
		</td>
	</tr>
	<tr>
		<td>Spam:</td>
		<td>
			<select name="spam">
				<option value=""></option>
				{foreach from=$training item=k key=v}
				<option value="{$v}">{$k}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td>Assign:</td>
		<td>
			<select name="assign">
				<option value=""></option>
				<!-- <option value="0">Anybody</option>  -->
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=next_worker_id_sel equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}">{$worker->getName()}</option>
				{/foreach}
			</select> 
	      	{if !empty($next_worker_id_sel)}
	      		<button type="button" onclick="this.form.assign.selectedIndex = {$next_worker_id_sel};">me</button>
	      		<!-- <button type="button" onclick="this.form.assign.selectedIndex = 1;">anybody</button>  -->
	      	{/if}
		</td>
	</tr>
</table>
<br>

<button type="button" onclick="ajax.postAndReloadView('frmSaveAddInboxRule','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
</form>