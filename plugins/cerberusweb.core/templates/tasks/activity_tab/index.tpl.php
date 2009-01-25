<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl.php" divName="taskSearchFilters"}
			<div id="taskSearchFilters" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>

</table>

{*
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
*}