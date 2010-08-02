<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap" valign="top">
      	<div id="viewSidebar{$view->id}">
      		{include file="file:$core_tpl/tickets/workflow/sidebar.tpl"}
		</div>			
      </td>
      <td nowrap="nowrap" width="0%" style="padding-right:5px;"></td>
      <td width="100%" valign="top">
		<div id="view{$view->id}">
      		{$view->render()}
      	</div>

		{include file="file:$core_tpl/tickets/whos_online.tpl"}
      </td>
    </tr>
  </tbody>
</table>