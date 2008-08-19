<html>
<head>
	<script>
		{literal}
		function copyToClipboard(copyText) {
		    var flashcopier = 'flashcopier';
		    if(!document.getElementById(flashcopier)) {
		      var divholder = document.createElement('div');
		      divholder.id = flashcopier;
		      document.body.appendChild(divholder);
		    }
		    document.getElementById(flashcopier).innerHTML = '';
		    var divinfo = '<embed src="{/literal}{devblocks_url}c=resource&p=cerberusweb.forums&f=flash/_clipboard.swf{/devblocks_url}{literal}?" FlashVars="clipboard='+encodeURIComponent(copyText)+'" width="0" height="0" type="application/x-shockwave-flash"></embed>';
		    document.getElementById(flashcopier).innerHTML = divinfo;
		}
		{/literal}
	</script>
	<style type="text/css">
	{literal}
		BODY {
			margin: 5px;
			background-color: rgb(220,220,220);
			font-family: Arial,sans-serif,Helvetica,Verdana;
		}
		TD {
			font-size:14px;
		}
		A {
			color:rgb(50,50,200);
		}
	{/literal}
	</style>

	<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/utilities/utilities.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
	<script language="javascript" type="text/javascript">{php}DevblocksPlatform::printJavascriptLibrary();{/php}</script>
</head>

<body>
	<table cellpadding="0" cellspacing="0" border="0" style="height:100%;width:100%;">
	<tr>
		<td valign="top">
			<form action="{devblocks_url}{/devblocks_url}" name="formForumThreadActions">
				<input type="hidden" name="c" value="forums">
				<input type="hidden" name="a" value="">
				<input type="hidden" name="id" value="{$current_post->id}">

				{if $current_post->is_closed}
					<button type="button" onclick="genericAjaxGet('','c=forums&a=ajaxReopen&id={$current_post->id}');this.innerHTML='Opened!';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_out.gif{/devblocks_url}" align="top"> Re-open</button>
				{else}				
					<button type="button" onclick="genericAjaxGet('','c=forums&a=ajaxClose&id={$current_post->id}');this.innerHTML='Closed!';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> Close</button>
				{/if}
				 &nbsp; 
				Assign: <select name="worker_id" onchange="genericAjaxGet('','c=forums&a=ajaxAssign&id={$current_post->id}&worker_id='+selectValue(this));">
					<option value="">-- assign --</option>
					{foreach from=$workers item=worker key=worker_id name=forums_workers}
						{if $worker_id==$active_worker->id}{assign var=active_worker_pos value=$smarty.foreach.forums_workers.iteration}{/if}
						<option value="{$worker_id}" {if $worker_id==$current_post->worker_id}selected{/if}>{$worker->getName()}</option>
					{/foreach}
					<option value="0">- unassign -</option>
				</select>{if !empty($active_worker_pos)}<button type="button" onclick="this.form.worker_id.selectedIndex={$active_worker_pos};genericAjaxGet('','c=forums&a=ajaxAssign&id={$current_post->id}&worker_id={$active_worker->id}');">me</button>{/if}
				<button type="button" onclick="copyToClipboard('{$current_post->link}');" title="Copy the original URL for this post to the clipboard">Clip URL</button>
			</form>
		</td>
		
		<td align="right" valign="top">
			{if !empty($prev_post)}
				<a href="{$prev_post.t_link}" onclick="document.location='{devblocks_url}c=forums&a=explorer&n=navigation&prev=prev{/devblocks_url}';" target="frameForums" title="{$prev_post.t_title|escape}">&laquo; {$prev_post.t_title|truncate:50:'...':true}</a> 
			{/if}
			{if !empty($prev_post) && !empty($next_post)}
				&nbsp;|&nbsp;  
			{/if}
			{if !empty($next_post)}
				<a href="{$next_post.t_link}" onclick="document.location='{devblocks_url}c=forums&a=explorer&n=navigation&next=next{/devblocks_url}';" target="frameForums" title="{$next_post.t_title|escape}">{$next_post.t_title|truncate:50:'...':true} &raquo;</a>
			{/if}
		</td>
	</tr>
	</table>
</body>

</html>