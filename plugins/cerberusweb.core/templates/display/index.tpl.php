<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	{*
	<div style="padding:5px;">
	<a href="{devblocks_url}c=tickets&a=overview{/devblocks_url}">overview</a>
	*} 
	</div>
</div> 

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr>
	<td colspan="2" valign="top">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td>
					<h1>{$ticket->subject|escape:"htmlall"}</h1>
					{assign var=ticket_team_id value=$ticket->team_id}
					{assign var=ticket_team value=$teams.$ticket_team_id}
					{assign var=ticket_category_id value=$ticket->category_id}
					{assign var=ticket_team_category_set value=$team_categories.$ticket_team_id}
					{assign var=ticket_category value=$ticket_team_category_set.$ticket_category_id}
					
					<b>Status:</b> {if $ticket->is_deleted}{$translate->_('status.deleted')}{elseif $ticket->is_closed}{$translate->_('status.closed')}{elseif $ticket->is_waiting}{$translate->_('status.waiting')}{else}{$translate->_('status.open')}{/if} &nbsp; 
					<b>Team:</b> {$teams.$ticket_team_id->name} &nbsp; 
					<b>Bucket:</b> {if !empty($ticket_category_id)}{$ticket_category->name}{else}Inbox{/if} &nbsp; 
					<b>Mask:</b> {$ticket->mask} &nbsp; 
					<b>Internal ID:</b> {$ticket->id} &nbsp; 
					<br>
					{if !empty($ticket->next_action) && !$ticket->is_closed}
						<b>Next Action:</b> {$ticket->next_action}<br>
					{/if}
					{if !empty($ticket->next_worker_id)}
						{assign var=next_worker_id value=$ticket->next_worker_id}
						<b>Next Worker:</b> {$workers.$next_worker_id->getName()}<br>
					{/if}
					<!-- {if !empty($ticket->interesting_words)}<b>Interesting Words:</b> {$ticket->interesting_words}<br>{/if} -->
					<!-- <b>Next Action:</b> <input type="text" name="next_step" size="80" value="{$ticket->next_action}" maxlength="255"><br>  -->
				</td>
				<td align="right">
					<form action="{devblocks_url}{/devblocks_url}" method="post">
					<input type="hidden" name="c" value="tickets">
					<input type="hidden" name="a" value="doQuickSearch">
					<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
						<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>Sender</option>
						<option value="requester"{if $quick_search_type eq 'requester'}selected{/if}>Requester</option>
						<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>Ticket ID</option>
						<option value="org"{if $quick_search_type eq 'org'}selected{/if}>Organization</option>
						<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>Subject</option>
						<option value="content"{if $quick_search_type eq 'content'}selected{/if}>Content</option>
					</select><input type="text" name="query" size="16"><input type="submit" value="go!">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td valign="top">
		<div id="tourDisplayProperties"></div>
		<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:10px;margin-top:5px;">
			<input type="hidden" name="c" value="display">
			<input type="hidden" name="a" value="updateProperties">
			<input type="hidden" name="id" value="{$ticket->id}">
			<input type="hidden" name="closed" value="{if $ticket->is_closed}1{else}0{/if}">
			<input type="hidden" name="deleted" value="{if $ticket->is_deleted}1{else}0{/if}">
			<input type="hidden" name="spam" value="0">
			
			{if !$ticket->is_deleted}
				{if $ticket->is_closed}
					<button type="button" onclick="this.form.closed.value=0;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_out.gif{/devblocks_url}" align="top"> Re-open</button>
				{else}
					<button id="btnClose" type="button" onclick="this.form.closed.value=1;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> Close</button>
				{/if}
				
				{if empty($ticket->spam_training)}
					<button id="btnSpam" type="button" onclick="this.form.spam.value=1;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top"> Report Spam</button>
				{/if}
			{/if}
			
			{if $ticket->is_deleted}
				<button type="button" onclick="this.form.deleted.value=0;this.form.closed.value=0;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete_gray.gif{/devblocks_url}" align="top"> Undelete</button>
			{else}
				{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}
				<button id="btnDelete" type="button" onclick="this.form.deleted.value=1;this.form.closed.value=1;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete</button>
				{/if}
			{/if}
			
			{if !$ticket->is_deleted}
		   	<select name="bucket_id" onchange="this.form.submit();">
		   		<option value="">-- move to --</option>
		   		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
		   		<optgroup label="Inboxes">
		   		{foreach from=$teams item=team}
		   			<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} (*){/if}</option>
		   		{/foreach}
		   		</optgroup>
		   		{foreach from=$team_categories item=categories key=teamId}
		   			{assign var=team value=$teams.$teamId}
		   			<optgroup label="-- {$team->name} --">
		   			{foreach from=$categories item=category}
		 				<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} (current bucket){/if}</option>
		 			{/foreach}
		 			</optgroup>
		  		{/foreach}
		   	</select>
		   	{/if}
			
			<button type="button" onclick="document.location='{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/replace2.gif{/devblocks_url}" align="top"> Refresh</button>
		</form>
	</td>
	<td valign="top" nowrap="nowrap" align="right">
		{if !empty($series_stats.next) || !empty($series_stats.prev)}
		<table cellpadding="0" cellspacing="0" border="0" style="margin:0px;">
			<tr>
				<td>	
				<div style="padding:10px;margin-top:0px;border:1px solid rgb(180,180,255);background-color:rgb(245,245,255);text-align:center;">
					Active list: <b>{$series_stats.title}</b><br>
					{if !empty($series_stats.prev)}<button style="display:none;visibility:hidden;" id="btnPagePrev" onclick="document.location='{devblocks_url}c=display&id={$series_stats.prev}{/devblocks_url}';">&laquo;Prev</button><a href="{devblocks_url}c=display&id={$series_stats.prev}{/devblocks_url}">&laquo;Prev</a>{/if} 
					 ({$series_stats.cur}-{$series_stats.count} of {$series_stats.total}) 
					{if !empty($series_stats.next)}<button style="display:none;visibility:hidden;" id="btnPageNext" onclick="document.location='{devblocks_url}c=display&id={$series_stats.next}{/devblocks_url}';">Next&raquo;</button><a href="{devblocks_url}c=display&id={$series_stats.next}{/devblocks_url}">Next&raquo;</a>{/if}
				</div>
				</td>
			</tr>
		</table>
		{/if}
	</td>
</tr>
</table>

<div id="displayOptions"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Conversation',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showConversation&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if empty($tab_selected)}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Comments ({/literal}{$comments_total}{literal})',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showComments&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'comments'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Tasks ({/literal}{$tasks_total}{literal})',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showTasks&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'tasks'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Properties',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showProperties&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'properties'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Custom Fields ({/literal}{$field_values_total}{literal})',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showCustomFields&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'fields'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Sender History',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showContactHistory&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'history'==$tab_selected}true{else}false{/if}{literal}
}));

{/literal}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title}',
    dataSrc: '{devblocks_url}ajax.php?c=display&a=showTab&ext_id={$tab_manifest->id}&ticket_id={$ticket->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('displayOptions');
</script>

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	switch(mykey) {
		case "c":  // close
		case "C":
			try {
				document.getElementById('btnClose').click();
			} catch(e){}
			break;
		case "r":  // reply to first message
		case "R":
			try {
				document.getElementById('btnReplyFirst').click();
			} catch(e){}
			break;
		case "s":  // spam
		case "S":
			try {
				document.getElementById('btnSpam').click();
			} catch(e){}
			break;
		case "x":  // delete
		case "X":
			try {
				document.getElementById('btnDelete').click();
			} catch(e){}
			break;
		default:
			// We didn't find any obvious keys, try other codes
			var mycode = getKeyboardKey(e,true);

			switch(mycode) {
				case 219:  // [ - prev page
					try {
						document.getElementById('btnPagePrev').click();
					} catch(e){}
					break;
				case 221:  // ] - next page
					try {
						document.getElementById('btnPageNext').click();
					} catch(e){}
					break;
			}
			break;
	}
});
{/literal}
</script>

<script type="text/javascript">
	var displayAjax = new cDisplayTicketAjax('{$ticket->id}');
</script>