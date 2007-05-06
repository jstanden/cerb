<table cellpadding="0" cellspacing="0">
<tr>
	<td style="padding-right:5px;"><h1>Dashboards</h1></td>
	<td>
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
</tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" valign="top">

      {include file="file:$path/tickets/dashboard_menu.tpl.php"}
      
      <br>
      
      {* [TODO] Only show if on a team dashboard *}
      {include file="file:$path/tickets/teamwork/categories.tpl.php"}
      
      </td>
      <td nowrap="nowrap" width="0%"><img src="{devblocks_url}images/c=resource&p=cerberusweb.core&f=spacer.gif{/devblocks_url}" width="5" height="1"></td>
      <td nowrap="nowrap" width="100%" valign="top">
      
      <div id="tourDashboardViews"></div>
      {foreach from=$views item=view name=views}
      	<div id="view{$view->id}">
	      	{include file="file:$path/tickets/ticket_view.tpl.php" first_view=$smarty.foreach.views.first}
	    </div>
      {/foreach}
      
      {include file="file:$path/tickets/whos_online.tpl.php"}
      	
      </td>
    </tr>
  </tbody>
</table>

