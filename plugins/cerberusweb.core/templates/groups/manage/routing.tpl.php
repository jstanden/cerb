<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabInboxAdd">
<input type="hidden" name="team_id" value="{$team->id}">
<h2>Add Inbox Filter</h2>

<b>If incoming ticket:</b>
<!-- 
<label><input type="radio" name="match" value="all" checked> all of the following</label> 
<label><input type="radio" name="match" value="any"> any of the following</label>
 --> 
<br>

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

<br>

<b>Then:</b><br>
<table>
	<tr>
		<td>Move to:</td>
		<td>
			<select name="move">
				<option value="">&nbsp;</option>
	      		<optgroup label="Move to Group">
	      		{foreach from=$teams item=tm}
	      			<option value="t{$tm->id}">{$tm->name}</option>
	      		{/foreach}
	      		</optgroup>
	      		{foreach from=$team_categories item=categories key=teamId}
	      			{assign var=tm value=$teams.$teamId}
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
				{$rule->header|capitalize}: <b style='color:rgb(0,120,0);'>{$rule->pattern}</b></label><br>
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
			</td>
		</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected Rules</button>
</div>
{else}
	<div class="block">
	<h2>No training data available</h2>
	<br>
	Use the Pile Sorter or Bulk Update in ticket worklists to teach the system how to sort your group's incoming mail.<br>
	</div>
{/if}
	
</form>