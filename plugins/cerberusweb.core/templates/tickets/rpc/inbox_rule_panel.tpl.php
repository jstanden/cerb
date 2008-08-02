<h1>Create Inbox Filter</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSaveAddInboxRule">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveAddInboxRulePanel">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="team_id" value="{$ticket->team_id}">

<div id="" style="height:50px;padding:5px;overflow:auto;border:1px solid rgb(180,180,180);background-color:rgb(255,255,255);">
	{foreach from=$message_headers item=v key=k}
		<b>{$k|capitalize}</b>: {$v|escape|nl2br}<br>
	{/foreach}
</div>
<br>

<b>Filter Name:</b><br>
<input type="text" name="name" size="45"> (e.g. Spam Bounces)<br>
<br>

<b>If incoming ticket:</b> (use * for wildcards)
<!-- 
<label><input type="radio" name="match" value="all" checked> all of the following</label> 
<label><input type="radio" name="match" value="any"> any of the following</label>
 --> 
<br>

<table>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="subject"> Subject:</label>
		</td>
		<td>
			<input type="text" name="value_subject" size="45" value="{$ticket->subject|escape}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			<label><input type="checkbox" name="rules[]" value="tocc"> To/Cc:</label>
		</td>
		<td>
			<input type="text" name="value_tocc" size="45" value="{$tocc_list|escape}" style="width:98%;"><br>
			(comma-delimited addresses, only one e-mail must match)<br>
		</td>
	</tr>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="from"> From:</label>
		</td>
		<td>
			<input type="text" name="value_from" size="45" value="{$first_address->email|escape}" style="width:98%;">
		</td>
	</tr>
	<!-- 
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="to"> To:</label>
		</td>
		<td>
			{*<input type="text" name="value_to" size="45">*}
			<select name="value_to">
				{foreach from=$groups item=group key=group_id}
					<option value="{$group_id}">{$group->name|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	 -->
	 
	{section name=headers start=0 loop=5}
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="header{$smarty.section.headers.iteration}"> Header:</label>
		</td>
		<td>
			<input type="text" name="header{$smarty.section.headers.iteration}" value="" size="16">
			 =  
			<input type="text" name="value_header{$smarty.section.headers.iteration}" size="45">
		</td>
	</tr>
	{/section}
	{*
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="body"> Content:</label>
		</td>
		<td>
			<input type="text" name="value_body" size="45">
		</td>
	</tr>
	*}
	{*
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="attachment"> Attachment Name:</label>
		</td>
		<td>
			<input type="text" name="value_attachment" size="45">
		</td>
	</tr>
	*}
</table>

<!-- 
<table>
	<tr>
		<td>
			<select name="field">
				<option value="subject">Subject</option>
				<option value="from">From</option>
			</select>
		</td>
		<td>
			matches 
		</td>
		<td>
			<input type="text" name="value" size="45">
		</td>
	</tr>
</table>
-->
<br>

<b>Then:</b><br>
<table>
	<tr>
		<td>Move to:</td>
		<td>
			<select name="move">
				<option value="">&nbsp;</option>
	      		<optgroup label="Move to Group">
	      		{foreach from=$groups item=tm}
	      			<option value="t{$tm->id}">{$tm->name}</option>
	      		{/foreach}
	      		</optgroup>
	      		{foreach from=$team_categories item=categories key=teamId}
	      			{assign var=tm value=$groups.$teamId}
	      			<optgroup label="{$tm->name}">
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
				<option value="">&nbsp;</option>
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
				<option value="">&nbsp;</option>
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
				<option value="">&nbsp;</option>
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


{*

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
*}

<button type="button" onclick="ajax.postAndReloadView('frmSaveAddInboxRule','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>