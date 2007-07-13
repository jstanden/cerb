<table cellpadding="0" cellspacing="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td style="padding-right:5px;" width="0%" nowrap="nowrap"><h1>Organize</h1></td>
	<td width="0%" nowrap="nowrap">
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
	<td align="right" width="100%">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>Sender</option>
			<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>Ticket ID</option>
			<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>Subject</option>
			<option value="content"{if $quick_search_type eq 'content'}selected{/if}>Content</option>
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

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	// [JAS]: Header Shortcuts		
	switch(mykey) {
		case "n":  // next
		case "N":
			var url = new DevblocksUrl();
			url.addVar('tickets');
			url.addVar('nextGroup');
			document.location = url.getUrl();
			break; // next
		case "r": // refresh
		case "R":
			var url = new DevblocksUrl();
			url.addVar('tickets');
			document.location = url.getUrl();
			break;
	}
});
{/literal}
</script>
