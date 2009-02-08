<div style="margin-bottom:5px;width:100%;" align="right">
	<form action="{devblocks_url}{/devblocks_url}" method="POST">
		<input type="hidden" name="c" value="home">
		<input type="hidden" name="a" value="doDeleteWorkspace">
		<input type="hidden" name="workspace" value="{$current_workspace|escape}">
		<button type="submit" value="" id="btnDeleteWorkspace" style="visibility:hidden;display:none;">
	</form>
	<a href="javascript:;" onclick="genericAjaxPanel('c=home&a=showReorderWorkspacePanel&workspace={$current_workspace|escape:'url'}',this,false,'450px');">{$translate->_('dashboard.reorder')|lower}</a>
	| <a href="javascript:;" onclick="if(confirm('{$translate->_('dashboard.delete.confirm')|escape}'))document.getElementById('btnDeleteWorkspace').click();">{$translate->_('dashboard.delete')|lower}</a>
</div>

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
      {/if}
      
      </td>
    </tr>
  </tbody>
</table>

