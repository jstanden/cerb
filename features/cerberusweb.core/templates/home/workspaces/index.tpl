<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doDeleteWorkspace">
<input type="hidden" name="workspace" value="{$current_workspace|escape}">
<table width="100%" style="margin-bottom:5px;">
	<tr>
		<td>
		</td>
		<td align="right">
			<a href="javascript:;" onclick="genericAjaxPanel('c=home&a=showEditWorkspacePanel&workspace={$current_workspace|escape:'url'}',null,false,'450');">{$translate->_('dashboard.edit')|lower}</a>
			| <a href="javascript:;" onclick="if(confirm('{$translate->_('dashboard.delete.confirm')|escape}'))$(this).closest('form').submit();">{$translate->_('dashboard.delete')|lower}</a>
		</td>
	</tr>
</table>
</form>

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

