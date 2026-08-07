{$owner_meta = $comment->getOwnerMeta()}
{$target_context = $comment->getTargetContext(false)}
{$is_writeable = CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_COMMENT, $comment, $active_worker)}

{* Reply: a new comment on this comment, seeded with an @mention of its author (skip self-mentions) *}
{$reply_mention = ''}
{if $comment->owner_context == CerberusContexts::CONTEXT_WORKER}
	{$reply_worker = DAO_Worker::get($comment->owner_context_id)}
	{if $reply_worker && $reply_worker->at_mention_name && $reply_worker->id != $active_worker->id}
		{$reply_mention = '@'|cat:$reply_worker->at_mention_name}
	{/if}
{/if}
{capture assign=reply_edit}context:{CerberusContexts::CONTEXT_COMMENT} context.id:{$comment->id}{if $reply_mention} comment:{$reply_mention}{/if}{/capture}

<div class="block" style="position:relative;margin-bottom:10px;padding-left:10px;">
	{* When there's no avatar to badge, keep the comment pill inline as a fallback *}
	{if !isset($owner_meta.context_ext->manifest->params.alias)}
	<span class="cerb-ui-pill cerb-ui-pill--blue" style="margin-right:5px;" title="{'common.comment'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-comments"></span></span>
	{/if}

	<span style="display:inline-flex;align-items:baseline;gap:0.4em;flex-wrap:wrap;">
	<b class="cerb-u-mr-1">
		{if empty($owner_meta)}
			(system)
		{else}
			{if $owner_meta.context_ext instanceof IDevblocksContextPeek}
			<a class="cerb-peek-trigger cerb-u-underline-hover" style="font-size:1.2em;" data-context="{$comment->owner_context}" data-context-id="{$comment->owner_context_id}">{$owner_meta.name}</a>
			{elseif !empty($owner_meta.permalink)}
			<a href="{$owner_meta.permalink}" target="_blank" rel="noopener" class="cerb-u-underline-hover">{$owner_meta.name}</a>
			{else}
			{$owner_meta.name}
			{/if}
		{/if}
	</b>

	{if $comment->owner_context == CerberusContexts::CONTEXT_WORKER}
		{$actor = $comment->getActorDictionary()}
		{if $actor->title}<span class="cerb-u-text-muted">{$actor->title}</span>{/if}
	{elseif $comment->owner_context == CerberusContexts::CONTEXT_CONTACT}
		{$comment_contact = DAO_Contact::get($comment->owner_context_id)}
		{if $comment_contact && $comment_contact->title}<span class="cerb-u-text-muted">{$comment_contact->title}</span>{/if}
		{if $comment_contact}{$comment_org = $comment_contact->getOrg()}{/if}
		{if !empty($comment_org)}
			<a class="cerb-ui-pill cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$comment_org->id}"><img src="{devblocks_url}c=avatars&context=org&context_id={$comment_org->id}{/devblocks_url}?v={$comment_org->updated}" style="height:16px;width:16px;border-radius:16px;">{$comment_org->name}</a>
		{/if}
	{else}
		<span class="cerb-u-text-muted">({$owner_meta.context_ext->manifest->name|lower})</span>
	{/if}
	</span>

	{if !$embed}
	<div class="toolbar toolbar-minmax">
		<button type="button" class="cerb-edit-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="{$comment->id}" title="Open card popup (Shift+Click to edit)"><span class="cerb-icons cerb-icon-new-window"></span></button>
		
		{if $is_writeable}
			{if $comment->is_pinned}
			<button type="button" class="cerb-button-enabled" data-cerb-comment-id="{$comment->id}" data-cerb-comment-pin="on" title="Un-pin this comment from the top of the conversation"><span class="cerb-icons cerb-icon-pushpin"></span></button>
			{else}
			<button type="button" data-cerb-comment-id="{$comment->id}" data-cerb-comment-pin="off" title="Pin this comment to the top of the conversation"><span class="cerb-icons cerb-icon-pushpin"></span></button>
			{/if}
		{/if}

		<button data-cerb-button-comment-permalink="{devblocks_url full=true}c=profiles&type={$target_context->params.alias}&id={$comment->context_id}{/devblocks_url}/#comment{$comment->id}" type="button" title="{'common.permalink'|devblocks_translate|lower}"><span class="cerb-icons cerb-icon-link"></span></button>
	</div>
	{/if}
	
	{if isset($owner_meta.context_ext->manifest->params.alias)}
	<div style="float:left;margin:0 10px 10px 0;">
		<span class="cerb-avatar-badged">
			<span class="cerb-ui-avatar" style="width:48px;height:48px;">
				<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}">
			</span>
			<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--blue" title="{'common.comment'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-comments"></span></span>
		</span>
	</div>
	{/if}
	
	<div class="cerb-comment--content">
		{$header_label_class = 'cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1'}
		{$header_label_style = 'text-align:right;white-space:nowrap;'}
		<div style="display:grid;grid-template-columns:auto 1fr;gap:0.25em 0.6em;line-height:1.4em;align-items:baseline;">
			<span class="{$header_label_class}" style="{$header_label_style}">{'message.header.date'|devblocks_translate|capitalize}:</span>
			<span>{$comment->created|devblocks_date} (<abbr title="{$comment->created|devblocks_date}">{$comment->created|devblocks_prettytime}</abbr>)</span>
		</div>

		{if $comment->is_markdown}
			<div class="commentBodyHtml" dir="auto">{$comment->getContent() nofilter}</div>
		{else}
			<pre class="emailbody" dir="auto" style="padding-top:10px;">{$comment->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
		{/if}

		<div style="margin-bottom:10px;"></div>

		{* Attachments *}
		{$comment_attachments = $comment->getAttachments()}
		{if $comment_attachments}
			{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id attachments=$comment_attachments}
		{/if}

		{* Custom Fields *}
		{$values = $comment->getCustomFieldValues()}
		{if is_array($values)}
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

		{if !$embed && $active_worker->hasPriv('contexts.cerberusweb.contexts.comment.comment')}
			<div style="display:flex;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap;">
				<div class="cerb-ui-toolbar-rail">
					<button type="button" class="cerb-sticky-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="{$reply_edit}"><span class="cerb-icons cerb-icon-comments"></span> {'common.comment'|devblocks_translate|capitalize}</button>
				</div>
				{if DevblocksPlatform::isPluginEnabled('cerb.comment.reactions')}
					{include file="devblocks:cerb.comment.reactions::internal/comments/reactions.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id reactions_class="cerb-u-mt-0"}
				{/if}
			</div>
		{else}
			{if DevblocksPlatform::isPluginEnabled('cerb.comment.reactions')}
				{include file="devblocks:cerb.comment.reactions::internal/comments/reactions.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id}
			{/if}
		{/if}

		<div id="comment{$comment->id}_notes" class="cerb-comments-thread">
			{if is_array($comment_notes) && array_key_exists($comment->id, $comment_notes)}
				{include file="devblocks:cerberusweb.core::display/modules/conversation/notes.tpl" message_notes=$comment_notes message_id=$comment->id readonly=false}
			{/if}
		</div>
	</div>
</div>

{if !$embed}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
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

	$comment.find('[data-cerb-button-comment-permalink]').on('click', function(e) {
		e.stopPropagation();
		let permalink_url = $(this).attr('data-cerb-button-comment-permalink');
		genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url=' + encodeURIComponent(permalink_url));
	});

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
	
	$comment
		.find('.toolbar .cerb-edit-trigger')
			.cerbPeekTrigger()
				.on('cerb-peek-saved', function(e) {
					if(e.id && e.hasOwnProperty('comment_html'))
						$('#comment' + e.id).html(e.comment_html);
				})
				.on('cerb-peek-deleted', function(e) {
					$('#comment' + e.id).remove();
				})
		;
	
	{if $is_writeable}
	$comment
		.find('.toolbar')
		.find('[data-cerb-comment-pin]')
		.on('click', function(e) {
			var $button = $(this);
			var comment_id = $button.attr('data-cerb-comment-id');
			var was_pin_mode = $button.attr('data-cerb-comment-pin');
			var is_pinned;
			
			if('off' === was_pin_mode) {
				$button.addClass('cerb-button-enabled');
				$button.attr('data-cerb-comment-pin', 'on');
				is_pinned = '1';
			} else {
				$button.removeClass('cerb-button-enabled');
				$button.attr('data-cerb-comment-pin', 'off');
				is_pinned = '0';
			}

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'comment');
			formData.set('action', 'togglePin');
			formData.set('id', comment_id);
			formData.set('pin', is_pinned);
			
			genericAjaxPost(formData, null, null, function() {
			});
		})
	;
	{/if}
});
</script>
{/if}