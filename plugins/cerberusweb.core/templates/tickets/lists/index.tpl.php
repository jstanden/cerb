{include file="file:$core_tpl/tickets/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>{$translate->_('mail.workspaces.my')}</h1>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" valign="middle" nowrap="nowrap">
		{include file="file:$core_tpl/tickets/quick_search_box.tpl.php"}
	</td>
</tr>
</table>

{if !empty($workspaces)}
<div class="subtle2">
<form method="POST" action="{devblocks_url}{/devblocks_url}" id="dashboardMenuForm">
	<input type="hidden" name="c" value="tickets">
	<input type="hidden" name="a" value="changeMyWorkspace">
	<b>{$translate->_('mail.workspaces.workspace')}:</b> 
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
				<b>{$translate->_('mail.workspaces.none')}</b><br>
				<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showAddListPanel',this,false,'450px');">{$translate->_('mail.workspaces.create')}</a><br>
				</td>
			</tr>
		</table>
		</div>
		<br>
		{/if}
      {/if}
      
      {include file="file:$core_tpl/tickets/whos_online.tpl.php"}
      	
      </td>
    </tr>
  </tbody>
</table>

