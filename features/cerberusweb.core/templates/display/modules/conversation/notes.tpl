{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		{$owner_meta = $note->getOwnerMeta()}
		<div id="comment{$note->id}" class="message_note" style="margin:10px;margin-left:20px;">
			<span class="tag" style="color:rgb(238,88,31);">{'display.ui.sticky_note'|devblocks_translate|lower}</span>
			
			<b style="font-size:1.3em;">
			{if empty($owner_meta)}
				(system)
			{else}
				{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
				<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$comment->owner_context}&context_id={$comment->owner_context_id}', null, false, '500');">{$owner_meta.name}</a>
				{elseif !empty($owner_meta.permalink)} 
				<a href="{$owner_meta.permalink}" target="_blank">{$owner_meta.name}</a>
				{else}
				{$owner_meta.name}
				{/if}
			{/if}
			</b>
			
			<div class="toolbar" style="display:none;float:right;margin-right:20px;">
				{if $note->context == CerberusContexts::CONTEXT_MESSAGE}
					<a href="{devblocks_url}c=profiles&type=ticket&mask={$ticket->mask}&focus=comment&focus_id={$note->id}{/devblocks_url}">{'common.permalink'|devblocks_translate|lower}</a>
				{/if}
				
				{if !$readonly}
					<a href="javascript:;" style="margin-left:10px;" onclick="if(confirm('Are you sure you want to permanently delete this note?')) { genericAjaxGet('','c=internal&a=commentDelete&id={$note->id}');$(this).closest('div.message_note').remove(); } ">{'common.delete'|devblocks_translate|lower}</a><br>
				{/if}
			</div>
			
			<br>
			
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$note->created|devblocks_date} (<abbr title="{$note->created|devblocks_date}">{$note->created|devblocks_prettytime}</abbr>)<br>
			{if !empty($note->comment)}<pre class="emailbody" style="padding-top:10px;">{$note->comment|escape|devblocks_hyperlinks nofilter}</pre>{/if}
		</div>
	{/foreach}
{/if}

<script type="text/javascript">
$('#{$message_id}t div.message_note').hover(
	function() {
		$(this).find('div.toolbar').show();
	},
	function() {
		$(this).find('div.toolbar').hide();
	}
);
</script>