<h1>Create Inbox Filter</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSaveAddInboxRule">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveAddInboxRulePanel">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="group_id" value="{$ticket->team_id}">

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
<br>

<b>Then:</b><br>
<table>
	<tr>
		{assign var=act_move value=$filter->actions.move}
		<td>
			<label><input type="checkbox" name="do[]" value="move" {if isset($act_move)}checked="checked"{/if}> Move to:</label>
		</td>
		<td>
			<select name="do_move">
				<option value="">&nbsp;</option>
	      		<optgroup label="Move to Group">
	      		{foreach from=$groups item=tm}
	      			{assign var=k value='t'|cat:$tm->id}
	      			<option value="{$k}" {if $tm->id==$act_move.group_id && 0==$act_move.bucket_id}selected="selected"{/if}>{$tm->name}</option>
	      		{/foreach}
	      		</optgroup>
	      		{foreach from=$team_categories item=categories key=teamId}
	      			{assign var=tm value=$groups.$teamId}
	      			<optgroup label="{$tm->name}">
	      			{foreach from=$categories item=category}
	      				{assign var=k value='c'|cat:$category->id}
	    				<option value="c{$category->id}" {if $category->id==$act_move.bucket_id}selected="selected"{/if}>{$category->name}</option>
	    			{/foreach}
	    			</optgroup>
	     		{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		{assign var=act_status value=$filter->actions.status}
		<td>
			<label><input type="checkbox" name="do[]" value="status" {if isset($act_status)}checked="checked"{/if}> Status:</label>
		</td>
		<td>
			<select name="do_status">
				<option value="">&nbsp;</option>
				<option value="0" {if isset($act_status) && !$act_status.is_closed && !$act_status.is_deleted}selected="selected"{/if}>{$translate->_('status.open')|capitalize}</option>
				<option value="1" {if isset($act_status) && $act_status.is_closed && !$act_status.is_deleted}selected="selected"{/if}>{$translate->_('status.closed')|capitalize}</option>
				{if $active_worker->hasPriv('core.ticket.actions.delete') || (isset($act_status) && $act_status.is_deleted)}
				<option value="2" {if isset($act_status) && $act_status.is_deleted}selected="selected"{/if}>Deleted</option>
				{/if}
			</select>
		</td>
	</tr>
	<tr>
		{assign var=act_spam value=$filter->actions.spam}
		<td>
			<label><input type="checkbox" name="do[]" value="spam" {if isset($act_spam)}checked="checked"{/if}> Spam:</label>
		</td>
		<td>
			<select name="do_spam">
				<option value="">&nbsp;</option>
				<option value="1" {if isset($act_spam) && $act_spam.is_spam}selected="selected"{/if}>{$translate->_('training.report_spam')|capitalize}</option>
				<option value="0" {if isset($act_spam) && !$act_spam.is_spam}selected="selected"{/if}>{$translate->_('training.not_spam')|capitalize}</option>
			</select>
		</td>
	</tr>
	<tr>
		{assign var=act_assign value=$filter->actions.assign}
		<td>
			<label><input type="checkbox" name="do[]" value="assign" {if isset($act_assign)}checked="checked"{/if}> Assign:</label>
		</td>
		<td>
			<select name="do_assign">
				<option value="">&nbsp;</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=next_worker_id_sel equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $act_assign.worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select> 
	      	{if !empty($next_worker_id_sel)}
	      		<button type="button" onclick="this.form.do_assign.selectedIndex = {$next_worker_id_sel};">me</button>
	      	{/if}
		</td>
	</tr>
</table>
<br>

<button type="button" onclick="ajax.postAndReloadView('frmSaveAddInboxRule','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>