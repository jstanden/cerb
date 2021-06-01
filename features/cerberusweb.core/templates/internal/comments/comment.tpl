{$owner_meta = $comment->getOwnerMeta()}
{$target_context = $comment->getTargetContext(false)}
{$is_writeable = Context_Comment::isWriteableByActor($comment, $active_worker)}

<div class="block" style="position:relative;margin-bottom:10px;padding-left:10px;">
	<span class="tag" style="background-color:rgb(71,133,210);color:white;margin-right:5px;">{'common.comment'|devblocks_translate|lower}</span>

	<b>
		{if empty($owner_meta)}
			(system)
		{else}
			{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
			<a href="javascript:;" class="cerb-peek-trigger" style="font-size:1.2em;" data-context="{$comment->owner_context}" data-context-id="{$comment->owner_context_id}">{$owner_meta.name}</a>
			{elseif !empty($owner_meta.permalink)} 
			<a href="{$owner_meta.permalink}" target="_blank" rel="noopener">{$owner_meta.name}</a>
			{else}
			{$owner_meta.name}
			{/if}
		{/if}
	</b>
	
	{if $comment->owner_context == CerberusContexts::CONTEXT_WORKER}
		{$actor = $comment->getActorDictionary()}
		&nbsp;
		{$actor->title}
	{else}
		({$owner_meta.context_ext->manifest->name|lower})
	{/if}

	&nbsp; <abbr title="{$comment->created|devblocks_date}">{$comment->created|devblocks_prettytime}</abbr>

	{if !$embed}
	<div class="toolbar">
		{if $is_writeable}<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="{$comment->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel" title="{'common.edit'|devblocks_translate|lower}"></span></button>{/if}

		{$permalink_url = "{devblocks_url full=true}c=profiles&type={$target_context->params.alias}&id={$comment->context_id}{/devblocks_url}/#comment{$comment->id}"}
		<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
	</div>
	{/if}
	
	{if isset($owner_meta.context_ext->manifest->params.alias)}
	<div style="float:left;margin:0 10px 10px 0;">
		<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:48px;width:48px;border-radius:48px;">
	</div>
	{/if}
	
	<div class="cerb-comment--content">
		{if $comment->is_markdown}
			<div class="commentBodyHtml" dir="auto">{$comment->getContent() nofilter}</div>
		{else}
			<pre class="emailbody" dir="auto" style="padding-top:10px;">{$comment->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
		{/if}

		<div style="margin-bottom:10px;"></div>

		{* Attachments *}
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id attachments=[]}

		{* Custom Fields *}
		{$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_COMMENT, $comment->id))|default:[]}
		{if $values}
			{$comment_custom_fields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_COMMENT, $values)}
			{$comment_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_COMMENT, $comment->id, $values)}
			<div style="margin-top:10px;">
				{if $message_custom_fields}
					<fieldset class="properties" style="padding:5px 0;border:0;">
						<legend>{'common.properties'|devblocks_translate|capitalize}</legend>

						<div style="padding:0px 5px;display:flex;flex-flow:row wrap;">
							{foreach from=$comment_custom_fields item=v key=k name=comment_custom_fields}
								<div style="flex:0 0 200px;text-overflow:ellipsis;">
									{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
								</div>
							{/foreach}
						</div>
					</fieldset>
				{/if}

				{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$comment_custom_fieldsets}
			</div>
		{/if}

		{if !$embed}
			<button type="button" class="cerb-sticky-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{CerberusContexts::CONTEXT_COMMENT} context.id:{$comment->id}"><span class="glyphicons glyphicons-comments"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		{/if}

		<div id="comment{$comment->id}_notes" class="cerb-comments-thread">
			{if is_array($comment_notes) && array_key_exists($comment->id, $comment_notes)}
				{include file="devblocks:cerberusweb.core::display/modules/conversation/notes.tpl" message_notes=$comment_notes message_id=$comment->id readonly=false}
			{/if}
		</div>
	</div>
</div>

{if !$embed}
<script type="text/javascript">
$(function() {
	var $comment = $('#comment{$comment->id}');
	var $notes = $('#comment{$comment->id}_notes');

	$comment.find('.cerb-sticky-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();

			if(e.id && e.comment_html) {
				var $new_note = $('<div id="comment' + e.id + '"/>')
					.addClass('cerb-comments-thread--comment')
					.hide()
					;
				$new_note.html(e.comment_html).prependTo($notes).fadeIn();
			}
		})
	;

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
				.on('cerb-peek-saved', function(e) {
					if(e.id && e.hasOwnProperty('comment_html'))
						$('#comment' + e.id).html(e.comment_html);
				})
				.on('cerb-peek-deleted', function(e) {
					$('#comment' + e.id).remove();
				})
		;
});
</script>
{/if}