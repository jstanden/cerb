{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		<div class="note">
			{assign var=note_worker_id value=$note->worker_id}
			<h2 style="display:inline;">{$workers.$note_worker_id->getName()} notes:</h2>&nbsp;
			<i>{$note->created|date_format}</i>
			&nbsp;
			<a href="javascript:;" onclick="genericAjaxGet('{$message_id}notes','c=display&a=deleteNote&id={$note_id}');">delete note</a>
			<br>
			{if !empty($note->content)}{$note->content|escape:"htmlall"|makehrefs|nl2br}<br>{/if}
		</div>
	{/foreach}
{/if}
