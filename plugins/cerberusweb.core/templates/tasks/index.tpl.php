{include file="file:$path/tasks/submenu.tpl.php"}

<h1>Tasks</h1>
{*
<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
	<button id="btnAddTask" type="button" onclick="genericAjaxPanel('c=tasks&a=showTaskPeek&id=0&view_id={$tasks_view->id}',null,false,'550px',{literal}function(o){document.getElementById('formTaskPeek').title.focus();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear_add.gif{/devblocks_url}" align="top"> Add Task</button><br>
</form>
*}

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style="padding-right:10px;" valign="top" width="1%" nowrap="nowrap">
			<div class="block" style="width:200px;">
				<h2>Open</h2>
				<a href="{devblocks_url}c=tasks&a=overview&m=all{/devblocks_url}">-All-</a><br>
				<a href="{devblocks_url}c=tasks&a=overview&m=available{/devblocks_url}">Available</a><br>
				<a href="{devblocks_url}c=tasks&a=overview&m=today{/devblocks_url}">Due Today</a><br>
			</div>
			<br>

			{if !empty($unassigned_totals)}
			<div class="block" style="width:200px;">
				<h2>Sources</h2>
				{foreach from=$source_renderers item=source key=source_ext_id}
					{assign var=source_total value=$unassigned_totals.$source_ext_id}
					{if !empty($source_total)}
					<a href="{devblocks_url}c=tasks&a=overview&m=source&id={$source_ext_id}{/devblocks_url}">{$source->getSourceName()}</a> ({$source_total})<br>
					{/if}
				{/foreach}
			</div>
			<br>
			{/if}
			
			{if !empty($assigned_totals)}
			<div class="block" style="width:200px;">
				<h2>Assigned</h2>
				{foreach from=$workers item=worker key=worker_id}
					{assign var=worker_total value=$assigned_totals.$worker_id}
					{if !empty($worker_total)}
					<a href="{devblocks_url}c=tasks&a=overview&m=worker&id={$worker_id}{/devblocks_url}">{$worker->getName()}</a> ({$worker_total})<br>
					{/if}
				{/foreach}
			</div>
			<br>
			{/if}
		</td>
		
		<td valign="top" width="99%">
			<div id="view{$tasks_view->id}">
				{$tasks_view->render()}
			</div>
		</td>
	</tr>
</table>

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	switch(mykey) {
		case "1":  // High priority
			try {
				document.getElementById('btnPriorityHigh').click();
			} catch(e){}
			break;
		case "2":  // Normal priority
			try {
				document.getElementById('btnPriorityNormal').click();
			} catch(e){}
			break;
		case "3":  // Low priority
			try {
				document.getElementById('btnPriorityLow').click();
			} catch(e){}
			break;
		case "4":  // no priority
			try {
				document.getElementById('btnPriorityNone').click();
			} catch(e){}
			break;
		case "a":  // assign to me (take)
		case "A":
			try {
				document.getElementById('btnTake').click();
			} catch(e){}
			break;
		case "c":  // complete
		case "C":
			try {
				document.getElementById('btnComplete').click();
			} catch(e){}
			break;
		case "d":  // due today
		case "D":
			try {
				document.getElementById('btnDueToday').click();
			} catch(e){}
			break;
		case "p":  // postpone
		case "P":
			try {
				document.getElementById('btnPostpone').click();
			} catch(e){}
			break;
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
		case "s":  // surrender
		case "S":
			try {
				document.getElementById('btnSurrender').click();
			} catch(e){}
			break;
//		case "t":
//		case "T":
//			try {
//				document.getElementById('btnAddTask').click();
//			} catch(e){}
//			break;
		case "x":  // delete
		case "X":
			try {
				document.getElementById('btnDelete').click();
			} catch(e){}
			break;
	}
});
{/literal}
</script>

