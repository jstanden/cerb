{include file="file:$path/tickets/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>My Workspaces</h1>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" valign="middle" nowrap="nowrap">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>Sender</option>
			<option value="requester"{if $quick_search_type eq 'requester'}selected{/if}>Requester</option>
			<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>Ticket ID</option>
			<option value="org"{if $quick_search_type eq 'org'}selected{/if}>Organization</option>
			<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>Subject</option>
			<option value="content"{if $quick_search_type eq 'content'}selected{/if}>Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</td>
</tr>
</table>

{if !empty($workspaces)}
<div class="subtle2">
<form method="POST" action="{devblocks_url}{/devblocks_url}" id="dashboardMenuForm">
	<input type="hidden" name="c" value="tickets">
	<input type="hidden" name="a" value="changeMyWorkspace">
	<b>Workspace:</b> 
	<select name="workspace" onchange="this.form.submit();">
		{foreach from=$workspaces item=workspace}
		<option value="{$workspace|escape}" {if $current_workspace==$workspace}selected{/if}>{$workspace}</option>
		{/foreach}
	</select>
	&nbsp;
	<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showAddListPanel',this,false,'450px');">{$translate->_('dashboard.add_view')|lower}</a>
	 | 
	<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showReorderWorkspacePanel&workspace={$current_workspace|escape:'url'}',this,false,'450px');">{$translate->_('dashboard.reorder')|lower}</a>
</form>
</div>
{/if}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="100%" valign="top">
      
      <div id="tourDashboardViews"></div>
      {if !empty($views)}
		{foreach from=$views item=view name=views}
			<div id="view{$view->id}">
			{$view->render()}
			</div>
		{/foreach}
      {else}
		{if empty($workspaces)}
		<div class="subtle" style="margin:0px;">
		<table cellpadding="0" cellspacing="0" border="0" width="98%">
			<tr>
				<td>
				<b>You haven't created any custom worklists.</b><br>
				<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showAddListPanel',this,false,'450px');">Click here</a> to create your first worklist.<br>
				</td>
			</tr>
		</table>
		</div>
		<br>
		{/if}
      {/if}
      
      {include file="file:$path/tickets/whos_online.tpl.php"}
      	
      </td>
    </tr>
  </tbody>
</table>

