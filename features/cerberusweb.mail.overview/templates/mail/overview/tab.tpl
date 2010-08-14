<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap" valign="top">
      	<div id="viewSidebar{$view->id}">
      		{include file="devblocks:cerberusweb.mail.overview::mail/overview/sidebar.tpl"}
		</div>			
      </td>
      <td nowrap="nowrap" width="0%" style="padding-right:5px;"></td>
      <td width="100%" valign="top">
		<div id="view{$view->id}">
			{if method_exists($view, 'render')}{$view->render()}{/if}
		</div>
      </td>
    </tr>
  </tbody>
</table>