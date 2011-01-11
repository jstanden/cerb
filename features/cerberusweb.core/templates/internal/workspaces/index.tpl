
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="100%" valign="top">
		<form action="{devblocks_url}{/devblocks_url}" method="POST">
			<input type="hidden" name="c" value="internal">
			<input type="hidden" name="a" value="">
			<input type="hidden" name="id" value="{$workspace->id}">
			<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showEditWorkspacePanel&id={$workspace->id|escape:'url'}',null,true,'600');"><span class="cerb-sprite sprite-gear"></span> {$translate->_('dashboard.edit')|capitalize}</button>
		</form>
      
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

