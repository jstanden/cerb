{$owner_meta = $note->getOwnerMeta()}
<div>
	<b>
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

	&nbsp; <abbr title="{$note->created|devblocks_date}">{$note->created|devblocks_prettytime}</abbr>

	<span class="glyphicons glyphicons-option-vertical" style="vertical-align:baseline;cursor:pointer;color:rgb(180,180,180);"></span>

	<ul class="cerb-float" style="display:none;">
		{if !$readonly}
			<li data-cerb-action="edit"><span class="glyphicons glyphicons-cogwheel"></span> <b>{'common.edit'|devblocks_translate|capitalize}</b></li>
		{/if}

		{if in_array($note->context, [CerberusContexts::CONTEXT_COMMENT, CerberusContexts::CONTEXT_DRAFT, CerberusContexts::CONTEXT_MESSAGE])}
			<li data-cerb-action="permalink" data-cerb-permalink="{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}{/devblocks_url}/#comment{$note->id}"><span class="glyphicons glyphicons-link"></span> <b>{'common.permalink'|devblocks_translate|capitalize}</b></li>
		{/if}
	</ul>

	{if isset($owner_meta.context_ext->manifest->params.alias)}
	<div class="cerb-comments-thread--avatar">
		<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:32px;width:32px;border-radius:32px;">
	</div>
	{/if}

	<div style="display:block;overflow:hidden;">
		{if $note->is_markdown}
			<div class="commentBodyHtml" dir="auto">{$note->getContent() nofilter}</div>
		{else}
			<pre class="emailbody" dir="auto" style="padding-top:10px;">{$note->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
		{/if}

		<div style="clear:both;"></div>

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
</div>

<script type="text/javascript">
$(function() {
	var $comment = $('#comment{$note->id}');

	$comment.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;

	$comment.find('.glyphicons-option-vertical')
		.next('ul.cerb-float')
		.menu({
			select: function(event, ui) {
				var action = ui.item.attr('data-cerb-action');

				if('permalink' === action) {
					var permalink_url = ui.item.attr('data-cerb-permalink');
					genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url=' + encodeURIComponent(permalink_url));

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

				$(this).hide();
			}
		})
		.hoverIntent({
			sensitivity:10,
			interval:0,
			timeout:50,
			over:function() {
			},
			out:function() {
				$(this).hide();
			}
		})
	;

	$comment.find('.glyphicons-option-vertical')
		.on('click', function() {
			var $this = $(this);
			var $menu = $this.next('ul.cerb-float').toggle();

			$menu.position({
				my: 'left top',
				at: 'left bottom',
				of: $this,
				collision: 'fit'
			});
		})
	;
});
</script>