{$owner_meta = $note->getOwnerMeta()}
{$can_permalink = in_array($note->context, [CerberusContexts::CONTEXT_COMMENT, CerberusContexts::CONTEXT_DRAFT, CerberusContexts::CONTEXT_MESSAGE])}

{* Reply: a new comment on this note's *target* (the top-level comment/message/draft — we don't nest
   deeper), seeded with an @mention of this note's author. *}
{$can_reply = $active_worker->hasPriv('contexts.cerberusweb.contexts.comment.comment')}
{$reply_mention = ''}
{if $note->owner_context == CerberusContexts::CONTEXT_WORKER}
	{$reply_worker = DAO_Worker::get($note->owner_context_id)}
	{if $reply_worker && $reply_worker->at_mention_name && $reply_worker->id != $active_worker->id}
		{$reply_mention = '@'|cat:$reply_worker->at_mention_name}
	{/if}
{/if}
{capture assign=reply_edit}context:{$note->context} context.id:{$note->context_id}{if $reply_mention} comment:{$reply_mention}{/if}{/capture}
<div class="cerb-sticky-note">
	<div class="cerb-sticky-note--avatar">
		{if !empty($owner_meta) && isset($owner_meta.context_ext->manifest->params.alias)}
			<span class="cerb-ui-avatar" style="width:32px;height:32px;">
				<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}">
			</span>
		{else}
			<span class="cerb-ui-avatar" style="width:32px;height:32px;background:var(--cerb-color-background-contrast-200);">
				<span class="cerb-icons cerb-icon-comments"></span>
			</span>
		{/if}
	</div>

	<div class="cerb-sticky-note--main">
		{if $can_reply || !$readonly || $can_permalink}
		<div class="cerb-sticky-note--toolbar cerb-ui-toolbar-strip cerb-u-bg-none">
			{if $can_reply}
				<button type="button" data-cerb-action="reply" data-cerb-reply-edit="{$reply_edit}" class="cerb-ui-toolbar-button" title="{'common.reply'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-send"></span></button>
			{/if}

			{if !$readonly}
				<button type="button" data-cerb-action="edit" class="cerb-ui-toolbar-button" title="{'common.edit'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-gear"></span></button>
			{/if}

			{if $can_permalink}
				<button type="button" data-cerb-action="permalink" data-cerb-permalink="{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}{/devblocks_url}/#comment{$note->id}" class="cerb-ui-toolbar-button" title="{'common.permalink'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-link"></span></button>
			{/if}
		</div>
		{/if}

		<div class="cerb-sticky-note--header">
			{if empty($owner_meta)}
				<span class="cerb-sticky-note--author cerb-u-fs-4 cerb-u-bold">(system)</span>
			{else}
				{if $owner_meta.context && $owner_meta.context_ext instanceof IDevblocksContextPeek}
				<a class="cerb-sticky-note--author cerb-u-fs-4 cerb-u-bold cerb-peek-trigger" data-context="{$owner_meta.context}" data-context-id="{$owner_meta.id}">{$owner_meta.name}</a>
				{elseif !empty($owner_meta.permalink)}
				<a class="cerb-sticky-note--author cerb-u-fs-4 cerb-u-bold" href="{$owner_meta.permalink}" target="_blank" rel="noopener">{$owner_meta.name}</a>
				{else}
				<span class="cerb-sticky-note--author cerb-u-fs-4 cerb-u-bold">{$owner_meta.name}</span>
				{/if}
			{/if}

			<span class="cerb-u-text-muted cerb-u-fs-n1 cerb-u-ml-2" title="{$note->created|devblocks_date}">{$note->created|devblocks_prettytime}</span>
		</div>

		<div class="cerb-sticky-note--body cerb-u-mt-2 cerb-u-pl-2">
			{if $note->is_markdown}
				<div class="commentBodyHtml" dir="auto">{$note->getContent() nofilter}</div>
			{else}
				<pre class="emailbody" dir="auto">{$note->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
			{/if}

			{* Attachments *}
			{$comment_attachments = $note->getAttachments()}
			{if $comment_attachments}
				<div style="margin-top:8px;">
					{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$note->id attachments=$comment_attachments}
				</div>
			{/if}

			{* Custom Fields *}
			{$values = $note->getCustomFieldValues()}
			{if is_array($values)}
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

		{if DevblocksPlatform::isPluginEnabled('cerb.comment.reactions')}
			{include file="devblocks:cerb.comment.reactions::internal/comments/reactions.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$note->id}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $comment = $('#comment{$note->id}');

	$comment.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;

	$comment.find('button[data-cerb-action]').on('click', function(e) {
		e.stopPropagation();

		var action = this.getAttribute('data-cerb-action');

		if('permalink' === action) {
			var permalink_url = this.getAttribute('data-cerb-permalink');
			genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url=' + encodeURIComponent(permalink_url));

		} else if('reply' === action) {
			var $thread = $comment.closest('.cerb-comments-thread');

			$("<div/>")
				.attr('data-context', '{CerberusContexts::CONTEXT_COMMENT}')
				.attr('data-context-id', '0')
				.attr('data-edit', this.getAttribute('data-cerb-reply-edit'))
				.cerbPeekTrigger()
					.on('cerb-peek-saved', function(e) {
						e.stopPropagation();

						if(e.id && e.comment_html) {
							var $new_note = $('<div id="comment' + e.id + '" class="cerb-comments-thread--comment"/>').hide();
							$new_note.html(e.comment_html).appendTo($thread).fadeIn();
						}

						$(this).remove();
					})
				.click()
			;

		} else if('edit' === action) {
			$("<div/>")
				.attr('data-context', '{CerberusContexts::CONTEXT_COMMENT}')
				.attr('data-context-id', '{$note->id}')
				.attr('data-edit', 'true')
				.cerbPeekTrigger()
					.on('cerb-peek-saved', function(e) {
						if(e.id && e.comment_html)
							$('#comment' + e.id).html(e.comment_html);
						$(this).remove();
					})
					.on('cerb-peek-deleted', function(e) {
						$('#comment' + e.id).remove();
						$(this).remove();
					})
				.click()
			;
		}
	});
});
</script>