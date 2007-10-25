<div id="tourDashboardActions"></div>
<div id="dashboardPanel">
<div class="block">
<table cellpadding="0" cellspacing="0" border="0" width="220">
	<tr>
		<td nowrap="nowrap"><h2>Group</h2></td>
	</tr>
	<tr>
		<td>
			<table cellpadding="0" cellspacing="1" border="0" width="100%">
				<tr>
					<td width="100%">
						<form method="POST" action="{devblocks_url}{/devblocks_url}" id="dashboardMenuForm">
						<input type="hidden" name="c" value="tickets">
						<input type="hidden" name="a" value="changeDashboard">
				      	<select name="dashboard_id" onchange="this.form.submit();">
				      		<option value="" {if empty($active_dashboard_id)}selected{/if}>My Conversations</option>
				      		<!-- <optgroup label="Teamwork">  -->
				      			{foreach from=$teams item=team key=team_id}
				      			{if isset($active_worker_memberships.$team_id)}
				      			{assign var=team_count value=$team_counts.$team_id}
				      			<option value="t{$team->id}" {if substr($active_dashboard_id,1)==$team->id}selected{/if}>{$team->name} ({if $team_count.tickets}{$team_count.tickets}{else}0{/if} new)</option>
				      			{/if}
				      			{/foreach}
				      		<!-- </optgroup>  -->
				      		<!-- 
				      		<optgroup label="Custom Dashboards">
				      		{foreach from=$dashboards item=dashboard}
				      			<option value="{$dashboard->id}" {if $active_dashboard_id==$dashboard->id}selected{/if}>{$dashboard->name}</option>
				      		{/foreach}
				      		<option value="add"> -- {$translate->_('dashboard.add_dashboard')|lower} -- </option>
				      		</optgroup>
				      		 -->
				      	</select>
				      	</form>
					</td>
				</tr>
				{assign var=team_member value=$active_worker_memberships.$dashboard_team_id}
				{if substr($active_dashboard_id,0,1) == 't' && (1 == $active_worker->is_superuser || 1 == $team_member->is_manager)}
					<tr>
						<td width="100%" style="padding:2px;">
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> <a href="{devblocks_url}c=tickets&team=team&id={$dashboard_team_id}{/devblocks_url}">{$translate->_('teamwork.group_management')|capitalize}</a><br>
						</td>
					</tr>
				{/if}

				<tr>
					<td width="100%" style="padding:2px;">
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/mail2.gif{/devblocks_url}" align="top"> <a href="{devblocks_url}c=tickets&a=compose{/devblocks_url}">Send Mail</a><br>
					</td>
				</tr>
				<!-- 
				<tr>
					<td width="100%" style="padding:2px;">
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_into.gif{/devblocks_url}" align="top"> <a href="{devblocks_url}c=tickets&a=create{/devblocks_url}">Log Ticket</a><br>
					</td>
				</tr>
				 -->

				{if !empty($active_dashboard_id) && is_numeric($active_dashboard_id)}
				<tr>
					<td width="100%" style="padding:2px;">
						 <a href="{devblocks_url}c=tickets&a=addView{/devblocks_url}">{$translate->_('dashboard.add_view')|lower}</a>
					</td>
				</tr>
				{/if}
				{if !empty($active_dashboard_id) && is_numeric($active_dashboard_id)}
				<tr>
					<td width="100%" style="padding:2px;">
						 <a href="#">{$translate->_('dashboard.modify')|lower}</a>
					</td>
				</tr>
				{/if}
			</table>
		</td>
	</tr>
</table>
</div>
<br>

{if empty($active_worker_memberships) && !empty($teams)}
<div class="subtle" style="margin:0px;">
<table cellpadding="0" cellspacing="0" border="0" width="220">
	<tr>
		<td>
		<b>You aren't a member of any groups!</b><br>
		You should ask someone to invite you to a group.<br>
		
		{if $active_worker->is_superuser}
			<br>
			It looks like you have admin privileges, 
			<a href="{devblocks_url}c=config&a=workflow{/devblocks_url}">you can add yourself to a group here</a>.
		{/if}
		</td>
	</tr>
</table>
</div>
<br>
{/if}

{if !empty($dashboard_team_id)}
<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamFilters">
<input type="hidden" name="team_id" value="{$dashboard_team_id}">
<table cellpadding="0" cellspacing="0" border="0" width="220">
	<tr>
		<td nowrap="nowrap"> <h2>{$translate->_('dashboard.group_filters')|capitalize}</h2></td>
	</tr>
	<tr>
		<td width="100%">
			<label><input type="radio" name="categorized" value="0" {if !$team_filters.categorized}checked{/if} onclick="toggleDiv('teamCategories','none');"> <b>All active</b></label><br>
			<label><input type="radio" name="categorized" value="1" {if $team_filters.categorized}checked{/if} onclick="toggleDiv('teamCategories','block');"> <b>In these buckets:</b></label>
			<a href="javascript:;" onclick="checkAll('teamCategories',false);">clear</a> 
			<br>
			
			<div id="teamCategories" style="display:{if !$team_filters.categorized}none{else}block{/if};">
			<label><input type="checkbox" name="categories[]" value="0" {if isset($team_filters.categories.0)}checked{/if} onclick="this.form.categorized[1].checked=true;"> Inbox <span style="font-size:80%;color:rgb(150,150,150);">({if isset($category_counts.0)}{$category_counts.0}{else}0{/if})</span></label><br>
			<blockquote style="margin:0px;margin-left:5px;margin-bottom:10px;">
			{if !empty($buckets)}
			<table cellpadding="0" cellspacing="0" border="0" width="98%">
				{foreach from=$buckets item=category key=category_id}
					{if $category_counts.total && $category_counts.$category_id}
						{math assign=percent equation="(x/y)*25" x=$category_counts.$category_id y=$category_counts.total format="%0.0f"}
					{else}
						{assign var=percent value=0}
					{/if}
				<tr>
					<td width="100%"><label><input type="checkbox" name="categories[]" value="{$category->id}" {if isset($team_filters.categories.$category_id)}checked{/if} onclick="this.form.categorized[1].checked=true;"> {$category->name}</label> <span style="font-size:80%;color:rgb(150,150,150);">({if isset($category_counts.$category_id)}{$category_counts.$category_id}{else}0{/if})</td>
					<td width="0%" nowrap="nowrap" align="right" valign="middle">&nbsp;</span></td>
					<td width="0%" nowrap="nowrap" style="width:26px;" valign="middle"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
				</tr>
				{/foreach}
			</table>
			{/if}
			</blockquote>
			</div>
			
			<div id="teamAddBuckets" style="display:none;">
			<b>Add Buckets:</b> (one per line)<br>
			<textarea name="add_buckets" rows="3" cols="24" style="width:98%;" wrap="off"></textarea><br>
			<button type="button" onclick="this.form.submit();">Add Buckets</button>
			<button type="button" onclick="this.form.add_buckets.value='';toggleDiv('teamAddBuckets','none');">Cancel</button>
			</div>
			
			<!-- <label><input type="checkbox" name="show_waiting" value="1" {if $team_filters.show_waiting}checked{/if}> Show Waiting Tickets</label><br>  -->
			<!-- <label><input type="checkbox" name="hide_assigned" value="1" {if $team_filters.hide_assigned}checked{/if}> Hide with Active Tasks</label><br>  -->
			
			<div align="right">
				<a href="javascript:;" onclick="toggleDiv('teamAddBuckets','block');">add buckets</a>&nbsp;
				<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/replace2.gif{/devblocks_url}" align="top"> {$translate->_('common.refresh')|capitalize}</button>
			</div>
		</td>
	</tr>
</table>
</form>
</div>
{/if}

</div>