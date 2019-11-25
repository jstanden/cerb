{$owner_meta = $note->getOwnerMeta()}
<div class="message_note" style="margin:10px;margin-left:20px;">
	<span class="tag" style="background-color:rgb(238,88,31);color:white;margin-right:5px;">{'display.ui.sticky_note'|devblocks_translate|lower}</span>
	
	<b style="font-size:1.3em;">
	{if empty($owner_meta)}
		(system)
	{else}
		{if $owner_meta.context && $owner_meta.context_ext instanceof IDevblocksContextPeek}
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{$owner_meta.context}" data-context-id="{$owner_meta.id}">{$owner_meta.name}</a>
		{elseif !empty($owner_meta.permalink)} 
		<a href="{$owner_meta.permalink}" target="_blank" rel="noopener">{$owner_meta.name}</a>
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
		{if !$readonly}
			<button type="button" class="cerb-edit-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="{$note->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel" title="{'common.edit'|devblocks_translate|lower}"></span></button>
		{/if}
		
		{if $note->context == CerberusContexts::CONTEXT_MESSAGE}
			{$permalink_url = "{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}{/devblocks_url}/#comment{$note->id}"}
			<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
		{/if}
	</div>
	
	<br>
	
	<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$note->created|devblocks_date} (<abbr title="{$note->created|devblocks_date}">{$note->created|devblocks_prettytime}</abbr>)<br>
	{if $note->is_markdown}
		<div class="commentBodyHtml">{$note->getContent() nofilter}</div>
	{else}
		<pre class="emailbody" style="padding-top:10px;">{$note->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
	{/if}
	<br clear="all">
	
	{* Attachments *}
	{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$note->id attachments=[]}

	{* Custom Fields *}
	{$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_COMMENT, $note->id))|default:[]}
	{if $values}
	{$note_custom_fields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_COMMENT, $values)}
	{$note_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_COMMENT, $note->id, $values)}
	<div style="margin-top:10px;">
		{if $message_custom_fields}
			<fieldset class="properties" style="padding:5px 0;border:0;">
				<legend>{'common.properties'|devblocks_translate|capitalize}</legend>

				<div style="padding:0px 5px;display:flex;flex-flow:row wrap;">
					{foreach from=$note_custom_fields item=v key=k name=note_custom_fields}
						<div style="flex:0 0 200px;text-overflow:ellipsis;">
							{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
						</div>
					{/foreach}
				</div>
			</fieldset>
		{/if}

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$note_custom_fieldsets}
	</div>
	{/if}

</div>

<script type="text/javascript">
$(function() {
	var $comment = $('#comment{$note->id}');
	
	$comment
		.hover(
			function() {
				$(this).find('div.toolbar').show();
			},
			function() {
				$(this).find('div.toolbar').hide();
			}
		)
		.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
		;
	
	$comment.find('.cerb-edit-trigger')
		.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				if(e.id && e.comment_html)
					$('#comment' + e.id).html(e.comment_html);
			})
			.on('cerb-peek-deleted', function(e) {
				$('#comment' + e.id).remove();
			})
		;

});
</script>