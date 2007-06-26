<table cellpadding="0" cellspacing="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td style="padding-right:5px;" width="0%" nowrap="nowrap"><h1>Dashboards</h1></td>
	<td width="0%" nowrap="nowrap">
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
	<td align="right" width="100%">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="mask">Ticket ID</option>
			<option value="req">Requester</option>
			<option value="subject">Subject</option>
			<option value="content">Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</td>
</tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" valign="top">

      {include file="file:$path/tickets/dashboard_menu.tpl.php"}
      
      {* Only show if on 'my' dashboard *}
      {if empty($active_dashboard_id)}
      {include file="file:$path/tickets/teamwork/getwork.tpl.php"}
      {/if}
      
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

