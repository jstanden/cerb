{include file="file:$path/tickets/submenu.tpl.php"}

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr>
	<td colspan="2" valign="top">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td>
					<h1>{$ticket->subject}</h1>
					{assign var=ticket_team_id value=$ticket->team_id}
					{assign var=ticket_team value=$teams.$ticket_team_id}
					{assign var=ticket_category_id value=$ticket->category_id}
					{assign var=ticket_team_category_set value=$team_categories.$ticket_team_id}
					{assign var=ticket_category value=$ticket_team_category_set.$ticket_category_id}
					
					<b>{$translate->_('ticket.status')|capitalize}:</b> {if $ticket->is_deleted}{$translate->_('status.deleted')}{elseif $ticket->is_closed}{$translate->_('status.closed')}{elseif $ticket->is_waiting}{$translate->_('status.waiting')}{else}{$translate->_('status.open')}{/if} &nbsp; 
					<b>{$translate->_('common.group')|capitalize}:</b> {$teams.$ticket_team_id->name} &nbsp; 
					<b>{$translate->_('common.bucket')|capitalize}:</b> {if !empty($ticket_category_id)}{$ticket_category->name}{else}{$translate->_('common.inbox')|capitalize}{/if} &nbsp; 
					<b>{$translate->_('ticket.mask')|capitalize}:</b> {$ticket->mask} &nbsp; 
					<b>{$translate->_('ticket.id')}:</b> {$ticket->id} &nbsp; 
					<br>
					{if !empty($ticket->next_action) && !$ticket->is_closed}
						<b>{$translate->_('ticket.next_action')|capitalize}:</b> {$ticket->next_action}<br>
					{/if}
					{if !empty($ticket->next_worker_id)}
						{assign var=next_worker_id value=$ticket->next_worker_id}
						<b>{$translate->_('ticket.next_worker')|capitalize}:</b> <span {if $next_worker_id==$active_worker->id}style="font-weight:bold;color:rgb(255,50,50);background-color:rgb(255,213,213);"{/if}>{$workers.$next_worker_id->getName()}</span> 
						{if $ticket->unlock_date}(until {$ticket->unlock_date|devblocks_date}){/if} 
						<br>
					{/if}
					<!-- {if !empty($ticket->interesting_words)}<b>Interesting Words:</b> {$ticket->interesting_words}<br>{/if} -->
					<!-- <b>Next Action:</b> <input type="text" name="next_step" size="80" value="{$ticket->next_action}" maxlength="255"><br>  -->
				</td>
				<td align="right">
					{include file="file:$path/tickets/quick_search_box.tpl.php"}				
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
			<input type="hidden" name="next_worker_id" value="{$ticket->next_worker_id}">
			<input type="hidden" name="unlock_date" value="{$ticket->unlock_date}">
			
			
			{if !$ticket->is_deleted}
				{if $ticket->is_closed}
					<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_out.gif{/devblocks_url}" align="top"> {$translate->_('common.reopen')|capitalize}</button>
				{else}
					<button title="{$translate->_('display.shortcut.close')}" id="btnClose" type="button" onclick="this.form.closed.value=1;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
				{/if}
				
				{if empty($ticket->spam_training)}
					<button title="{$translate->_('display.shortcut.spam')}" id="btnSpam" type="button" onclick="this.form.spam.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top"> {$translate->_('common.spam')|capitalize}</button>
				{/if}
			{/if}
			
			{if $ticket->is_deleted}
				<button type="button" onclick="this.form.deleted.value='0';this.form.closed.value=0;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete_gray.gif{/devblocks_url}" align="top"> {$translate->_('common.undelete')|capitalize}</button>
			{else}
				{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}
				<button title="{$translate->_('display.shortcut.delete')}" id="btnDelete" type="button" onclick="this.form.deleted.value=1;this.form.closed.value=1;this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>
				{/if}
			{/if}
			
			{if empty($ticket->next_worker_id)}<button id="btnTake" title="{$translate->_('display.shortcut.take')}" type="button" onclick="this.form.next_worker_id.value='{$active_worker->id}';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/hand_paper.gif{/devblocks_url}" align="top"> {$translate->_('mail.take')|capitalize}</button>{/if}
			{if $ticket->next_worker_id == $active_worker->id}<button id="btnSurrender" title="{$translate->_('display.shortcut.surrender')}" type="button" onclick="this.form.next_worker_id.value='0';this.form.unlock_date.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/flag_white.gif{/devblocks_url}" align="top"> {$translate->_('mail.surrender')|capitalize}</button>{/if}
			
			{if !$expand_all}<button id="btnReadAll" title="{$translate->_('display.shortcut.read_all')}" type="button" onclick="document.location='{devblocks_url}c=display&id={$ticket->mask}&tab=conversation&opt=read_all{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="top"> {$translate->_('display.button.read_all')|capitalize}</button>{/if} 
			 
			{if !$ticket->is_deleted}
		   	<select name="bucket_id" onchange="this.form.submit();">
		   		<option value="">-- {$translate->_('common.move_to')|lower} --</option>
		   		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
		   		<optgroup label="{$translate->_('common.inboxes')|capitalize}">
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
		   	<button id="btnPrint" title="{$translate->_('display.shortcut.print')}" type="button" onclick="document.frmPrint.action='{devblocks_url}c=print&a=ticket&id={$ticket->mask}{/devblocks_url}';document.frmPrint.submit();">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/printer.gif{/devblocks_url}" align="top">&nbsp;</button>
		   	<button type="button" title="{$translate->_('display.shortcut.refresh')}" onclick="document.location='{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}';">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/replace2.gif{/devblocks_url}" align="top">&nbsp;</button>
			<br>
			
			{* Plugin Toolbar *}
			{if !empty($ticket_toolbaritems)}
				{foreach from=$ticket_toolbaritems item=renderer}
					{if !empty($renderer)}{$renderer->render($ticket)}{/if}
				{/foreach}
				<br>
			{/if}
			
			{$translate->_('common.keyboard')|lower}: 
			{if !$ticket->is_closed}(<b>c</b>) {$translate->_('common.close')|lower}, {/if}
			{if !$ticket->spam_trained}(<b>s</b>) {$translate->_('common.spam')|lower}, {/if}
			{if !$ticket->is_deleted && $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}(<b>x</b>) {$translate->_('common.delete')|lower}, {/if}
			{if empty($ticket->next_worker_id)}(<b>t</b>) {$translate->_('mail.take')|lower}, {/if}
			{if $ticket->next_worker_id == $active_worker->id}(<b>u</b>) {$translate->_('mail.surrender')|lower}, {/if}
			{if !$expand_all}(<b>a</b>) {$translate->_('display.button.read_all')|lower}, {/if} 
			{if !empty($series_stats.prev)}( <b>[</b> ) {$translate->_('common.previous')|lower}, {/if} 
			{if !empty($series_stats.next)}( <b>]</b> ) {$translate->_('common.next')|lower}, {/if} 
			(<b>r</b>) {$translate->_('display.ui.reply')|lower},  
			(<b>p</b>) {$translate->_('common.print')|lower} 
			<br>
			 
		</form>
		<form action="{devblocks_url}{/devblocks_url}" method="post" name="frmPrint" id="frmPrint" target="_blank" style="display:none;"></form>
	</td>
	<td valign="top" nowrap="nowrap" align="right" id="tourDisplayPaging">
		{if !empty($series_stats.next) || !empty($series_stats.prev)}
		<table cellpadding="0" cellspacing="0" border="0" style="margin:0px;">
			<tr>
				<td>	
				<div style="padding:10px;margin-top:0px;border:1px solid rgb(180,180,255);background-color:rgb(245,245,255);text-align:center;">
					{$translate->_('display.listnav.active_list')} <b>{$series_stats.title}</b><br>
					{if !empty($series_stats.prev)}<button style="display:none;visibility:hidden;" id="btnPagePrev" onclick="document.location='{devblocks_url}c=display&id={$series_stats.prev}{/devblocks_url}';">&laquo;{$translate->_('common.previous_short')|capitalize}</button><a href="{devblocks_url}c=display&id={$series_stats.prev}{/devblocks_url}">&laquo;{$translate->_('common.previous_short')|capitalize}</a>{/if}
					{'display.listnav.showing_of_total'|devblocks_translate:$series_stats.cur:$series_stats.count:$series_stats.total} 
					{if !empty($series_stats.next)}<button style="display:none;visibility:hidden;" id="btnPageNext" onclick="document.location='{devblocks_url}c=display&id={$series_stats.next}{/devblocks_url}';">{$translate->_('common.next')|capitalize}&raquo;</button><a href="{devblocks_url}c=display&id={$series_stats.next}{/devblocks_url}">{$translate->_('common.next')|capitalize}&raquo;</a>{/if}
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
    label: '{/literal}{$translate->_('display.tab.conversation')}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showConversation&ticket_id={$ticket->id}{if $expand_all}&expand_all=1{/if}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if empty($tab_selected) || 'conversation'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('display.tab.properties')}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showProperties&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'properties'==$tab_selected}true{else}false{/if}{literal}
}));

/*{/literal}{*
tabView.addTab( new YAHOO.widget.Tab({
    label: 'Organization',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showOrganization&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'org'==$tab_selected}true{else}false{/if}{literal}
}));
*}{literal}*/

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'display.tab.comments'|devblocks_translate:$comments_total}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showComments&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'comments'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'display.tab.tasks'|devblocks_translate:$tasks_total}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showTasks&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'tasks'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'display.tab.custom_fields'|devblocks_translate:$field_values_total}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showCustomFields&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'fields'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'display.tab.history'|devblocks_translate}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=display&a=showContactHistory&ticket_id={$ticket->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'history'==$tab_selected}true{else}false{/if}{literal}
}));

{/literal}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate}',
    dataSrc: '{devblocks_url}ajax.php?c=display&a=showTab&ext_id={$tab_manifest->id}&ticket_id={$ticket->id}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri}active: true,{/if}
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
		case "a":  // read all
		case "A":
			try {
				document.getElementById('btnReadAll').click();
			} catch(e){}
			break;
		case "c":  // close
		case "C":
			try {
				document.getElementById('btnClose').click();
			} catch(e){}
			break;
		case "p":  // print
		case "P":
			try {
				document.getElementById('btnPrint').click();
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
		case "t":  // take/assign
		case "T":
			try {
				document.getElementById('btnTake').click();
			} catch(e){}
			break;
		case "u":  // surrender/unassign
		case "U":
			try {
				document.getElementById('btnSurrender').click();
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