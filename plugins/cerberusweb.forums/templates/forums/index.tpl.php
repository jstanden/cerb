{include file="$path/forums/submenu.tpl.php"}

<h1>Forums</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="forums"> 
	<input type="hidden" name="a" value="import">
	
	<button id="btnSynchronize" type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.forums&f=images/replace2.gif{/devblocks_url}" align="top"> Synchronize</button><br>
</form>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style="padding-right:10px;" valign="top" width="1%" nowrap="nowrap">
			{if !empty($source_unassigned_totals)}
			<div class="block" style="width:200px;">
				<h2>Available</h2>
				<a href="{devblocks_url}c=forums&a=overview&m=all{/devblocks_url}">-All-</a><br>
				
				{foreach from=$sources item=source key=source_id}
					{assign var=source_total value=$source_unassigned_totals.$source_id}
					{if !empty($source_total)}
					<a href="{devblocks_url}c=forums&a=overview&m=forum&id={$source_id}{/devblocks_url}">{$source->name}</a> ({$source_total})<br>
					{/if}
				{/foreach}
			</div>
			<br>
			{/if}
			
			{if !empty($source_assigned_totals)}
			<div class="block" style="width:200px;">
				<h2>Assigned</h2>
				{foreach from=$workers item=worker key=worker_id}
					{assign var=worker_total value=$source_assigned_totals.$worker_id}
					{if !empty($worker_total)}
					<a href="{devblocks_url}c=forums&a=overview&m=worker&id={$worker_id}{/devblocks_url}">{$worker->getName()}</a> ({$worker_total})<br>
					{/if}
				{/foreach}
			</div>
			<br>
			{/if}
		</td>
		
		<td valign="top" width="99%">
			<div id="view{$forums_overview->id}">
				{$forums_overview->render()}
			</div>
		</td>
	</tr>
</table>

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	switch(mykey) {
		case "c":  // close thread(s)
		case "C":
			try {
				document.getElementById('btnForumThreadClose').click();
			} catch(e){}
			break;
		case "s":  // synchronize
		case "S":
			try {
				document.getElementById('btnSynchronize').click();
			} catch(e){}
			break;
	}
});
{/literal}
</script>

