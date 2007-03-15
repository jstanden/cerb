{include file="file:$path/tickets/menu.tpl.php"}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" valign="top">

      {include file="file:$path/tickets/teamwork_menu.tpl.php"}
      
      <br>
      
      {include file="file:$path/tickets/dashboard_menu.tpl.php"}
      
      </td>
      <td nowrap="nowrap" width="0%"><img src="{devblocks_url}images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
      <td nowrap="nowrap" width="100%" valign="top">
      
      {foreach from=$views item=view}
      	<div id="view{$view->id}">
	      	{include file="file:$path/tickets/ticket_view.tpl.php"}
	      </div>
      {/foreach}
      
      {include file="file:$path/tickets/whos_online.tpl.php"}
      	
      </td>
    </tr>
  </tbody>
</table>

