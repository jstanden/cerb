<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap" valign="top">
      	<div id="viewSidebar{$views.0->id}">
      		{include file="file:$core_tpl/tickets/workflow/sidebar.tpl"}
		</div>			
      </td>
      <td nowrap="nowrap" width="0%" style="padding-right:5px;"></td>
      <td width="100%" valign="top">
	      {foreach from=$views item=view name=views}
	      	<div id="view{$view->id}">
		      	{$view->render()}
		    </div>
	      {/foreach}
	      
	      {include file="file:$core_tpl/tickets/whos_online.tpl"}
      </td>
      
    </tr>
  </tbody>
</table>
