{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		<div class="note">
			{if 1 == $note->type}
				<h2 style="display:inline;color:rgb(255,153,0);">Warning:</h2>&nbsp;
			{elseif 2 == $note->type}
				<h2 style="display:inline;color:rgb(255,50,50);">Error:</h2>&nbsp;
			{else}
				{assign var=note_worker_id value=$note->worker_id}
				{if $workers.$note_worker_id}
					<h2 style="display:inline;">{$workers.$note_worker_id->getName()} notes:</h2>&nbsp;
				{else}
					<h2 style="display:inline;">[Deleted Worker] notes:</h2>&nbsp;
				{/if}
			{/if}
			<i>{$note->created|date_format:"%b %e, %Y %I:%M %p"}</i>
			&nbsp;
			<a href="javascript:;" onclick="genericAjaxGet('{$message_id}notes','c=display&a=deleteNote&id={$note_id}');">delete note</a>
			<br>
			{if !empty($note->content)}{$note->content|escape:"htmlall"|makehrefs|nl2br}<br>{/if}
		</div>
	{/foreach}
{/if}
