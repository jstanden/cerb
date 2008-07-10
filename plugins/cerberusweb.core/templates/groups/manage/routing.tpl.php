<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabInboxAdd">
<input type="hidden" name="team_id" value="{$team->id}">
<h2>Add Inbox Filter</h2>

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
			<label><input type="checkbox" name="rules[]" value="type"> Is a:</label>
		</td>
		<td>
			<select name="value_type">
				<option value="new">new message</option>
				<option value="reply">reply</option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="subject"> Subject:</label>
		</td>
		<td>
			<input type="text" name="value_subject" size="45">
		</td>
	</tr>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="from"> From:</label>
		</td>
		<td>
			<input type="text" name="value_from" size="45">
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			<label><input type="checkbox" name="rules[]" value="tocc"> To/Cc:</label>
		</td>
		<td>
			<input type="text" name="value_tocc" size="45" value="{$tocc_list}" style="width:98%;"><br>
			(comma-delimited addresses, only one e-mail must match)<br>
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

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>

</form>
</div>
<br>


<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabInbox">
<input type="hidden" name="team_id" value="{$team->id}">

{if !empty($team_rules)}
<div class="block">
<h2>Inbox Filters</h2>
<table cellspacing="2" cellpadding="0">
	{counter start=0 print=false}
	{foreach from=$team_rules item=rule key=rule_id name=rules}
		<tr>
			<!--
			<td valign="top">
				<input type="text" name="priorities[]" value="{counter}" size="4"><br>
			</td>
			 -->
			<td>
				<label><input type="checkbox" name="deletes[]" value="{$rule_id}">
				<input type="hidden" name="ids[]" value="{$rule_id}">
				<b style='color:rgb(0,120,0);'>{$rule->name}</b>
				<br>
				
				<blockquote style="margin:2px;margin-left:20px;">
					{foreach from=$rule->criteria item=crit key=crit_key}
						{if $crit_key=='type'}
							Is a <b>{$crit.value}</b> message<br>
						{elseif $crit_key=='subject'}
							Subject = <b>{$crit.value}</b><br>
						{elseif $crit_key=='from'}
							From = <b>{$crit.value}</b><br>
						{elseif $crit_key=='to'}
							{assign var=to_group_id value=$crit.value}
							To = <b>{$groups.$to_group_id->name}</b><br>
						{elseif $crit_key=='tocc'}
							To/Cc = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header1'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header2'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header3'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header4'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header5'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body'}
							Body = <b>{$crit.value}</b><br>
						{elseif $crit_key=='attachment'}
							Attachment = <b>{$crit.value}</b><br>
						{/if}
					{/foreach}
					
					<!-- <input type="text" name="patterns[]" value="{$rule->pattern}" size="45">  -->
					<blockquote style="margin:2px;margin-left:30px;font-size:90%;color:rgb(130,130,130);">
						{if $rule->do_status != ''}
							{if $rule->do_status==1}Close Ticket{elseif $rule->do_status==0}Open Ticket{elseif $rule->do_status==2}Delete Ticket{/if}<br>
						{/if}
						{if $rule->do_spam != ''}
							{if $rule->do_spam=='N'}Mark Not Spam{else}Report Spam{/if}<br>
						{/if}
						{if $rule->do_move != ''}
							{assign var=move_code value=$rule->do_move}
							Move to '{$category_name_hash.$move_code}'<br>
						{/if}
						{if $rule->do_assign != ''}
							{assign var=assign_id value=$rule->do_assign}
							{if $assign_id && isset($workers.$assign_id)}
								Assign to '{$workers.$assign_id->getName()}'<br>
							{/if}
						{/if}
					<span>(Matched {$rule->pos} new messages)</span><br>
					</blockquote>
				</blockquote>
			</td>
		</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected Filters</button>
</div>
{else}
	<div class="block">
	<h2>No training data available</h2>
	<br>
	Use the Pile Sorter or Bulk Update in ticket worklists to teach the system how to sort your group's incoming mail.<br>
	</div>
{/if}
	
</form>