{$divid_notes = "notes_{uniqid()}"}
<div id="{$divid_notes}" style="background-color:rgb(255,255,255);">
{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		{$owner_meta = $note->getOwnerMeta()}
		<div id="comment{$note->id}" class="message_note" style="margin:10px;margin-left:20px;">
			<span class="tag" style="background-color:rgb(238,88,31);color:white;margin-right:5px;">{'display.ui.sticky_note'|devblocks_translate|lower}</span>
			
			<b style="font-size:1.3em;">
			{if empty($owner_meta)}
				(system)
			{else}
				{if $owner_meta.context && $owner_meta.context_ext instanceof IDevblocksContextPeek}
				<a href="javascript:;" class="cerb-peek-trigger" data-context="{$owner_meta.context}" data-context-id="{$owner_meta.id}">{$owner_meta.name}</a>
				{elseif !empty($owner_meta.permalink)} 
				<a href="{$owner_meta.permalink}" target="_blank">{$owner_meta.name}</a>
				{else}
				{$owner_meta.name}
				{/if}
			{/if}
			</b>
			
			{if $owner_meta.context_ext->manifest->name}
			({$owner_meta.context_ext->manifest->name|lower})
			{/if}
			
			{if isset($owner_meta.context_ext->manifest->params.alias)}
			<div style="float:left;margin:0px 5px 5px 0px;">
				<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:45px;width:45px;border-radius:45px;">
			</div>
			{/if}
			
			<div class="toolbar" style="display:none;float:right;margin-right:20px;">
				{if $note->context == CerberusContexts::CONTEXT_MESSAGE}
					{$permalink_url = "{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}&focus=comment&focus_id={$note->id}{/devblocks_url}"}
					<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
				{/if}
				
				{if !$readonly}
					<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this note?')) { genericAjaxGet('','c=internal&a=commentDelete&id={$note->id}');$(this).closest('div.message_note').remove(); } "><span class="glyphicons glyphicons-circle-remove" title="{'common.delete'|devblocks_translate|lower}"></span></button><br>
				{/if}
			</div>
			
			<br>
			
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$note->created|devblocks_date} (<abbr title="{$note->created|devblocks_date}">{$note->created|devblocks_prettytime}</abbr>)<br>
			{if !empty($note->comment)}<pre class="emailbody" style="padding-top:10px;">{$note->comment|escape|devblocks_hyperlinks nofilter}</pre>{/if}
		</div>
	{/foreach}
{/if}
</div>

<script type="text/javascript">
$(function() {
	var $notes = $('#{$divid_notes}');
	
	$notes.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	$notes.find('div.message_note').hover(
		function() {
			$(this).find('div.toolbar').show();
		},
		function() {
			$(this).find('div.toolbar').hide();
		}
	);
});
</script>