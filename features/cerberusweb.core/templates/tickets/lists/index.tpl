<div style="margin-bottom:5px;width:100%;" align="right">
	<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showReorderWorkspacePanel&workspace={$current_workspace|escape:'url'}',null,false,'450');">{$translate->_('dashboard.reorder')|lower}</a>
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

