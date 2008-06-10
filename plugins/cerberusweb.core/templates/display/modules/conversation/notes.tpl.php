{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		<div class="message_note" style="margin:10px;">
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_plain_yellow.png{/devblocks_url}" align="top">
			{if 1 == $note->type}
				<b style="color:rgb(255,153,0);">[warning]:</b>&nbsp;
			{elseif 2 == $note->type}
				<b style="color:rgb(255,50,50);">[error]:</b>&nbsp;
			{else}
				{assign var=note_worker_id value=$note->worker_id}
				{if $workers.$note_worker_id}
					<b style="color:rgb(222,73,0);">[sticky note] From: {$workers.$note_worker_id->getName()}</b>&nbsp;
				{else}
					<b style="color:rgb(222,73,0);">[sticky note] From: (Deleted Worker)</b>&nbsp;
				{/if}
			{/if}
			<a href="javascript:;" onclick="genericAjaxGet('{$message_id}notes','c=display&a=deleteNote&id={$note_id}');">delete note</a><br>
			<b>Date:</b> {$note->created|date_format:'%a, %d %b %Y %H:%M:%S'} -0000<br>
			<br>
			{if !empty($note->content)}{$note->content|escape:"htmlall"|makehrefs|nl2br}{/if}
		</div>
	{/foreach}
{/if}
