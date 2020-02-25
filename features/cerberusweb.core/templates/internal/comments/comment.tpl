{$owner_meta = $comment->getOwnerMeta()}
{$target_context = $comment->getTargetContext(false)}
{$is_writeable = Context_Comment::isWriteableByActor($comment, $active_worker)}

<div class="block" style="overflow:auto;margin-bottom:10px;">
	<span class="tag" style="background-color:rgb(71,133,210);color:white;margin-right:5px;">{'common.comment'|devblocks_translate|lower}</span>
	
	<b style="font-size:1.3em;">
		{if empty($owner_meta)}
			(system)
		{else}
			{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
			<a href="javascript:;" class="cerb-peek-trigger" data-context="{$comment->owner_context}" data-context-id="{$comment->owner_context_id}">{$owner_meta.name}</a>
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

	{if !$embed}
	<div class="toolbar" style="display:none;float:right;margin-right:20px;">
		{if $is_writeable}<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="{$comment->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel" title="{'common.edit'|devblocks_translate|lower}"></span></button>{/if}

		{$permalink_url = "{devblocks_url full=true}c=profiles&type={$target_context->params.alias}&id={$comment->context_id}{/devblocks_url}/#comment{$comment->id}"}
		<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
	</div>
	{/if}
	
	{if isset($owner_meta.context_ext->manifest->params.alias)}
	<div style="float:left;margin:0px 5px 5px 0px;">
		<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:64px;width:64px;border-radius:64px;">
	</div>
	{/if}
	
	<br>
	
	{if isset($comment->created)}<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$comment->created|devblocks_date} ({$comment->created|devblocks_prettytime})<br>{/if}

	{if $comment->is_markdown}
		<div class="commentBodyHtml">{$comment->getContent() nofilter}</div>
	{else}
		<pre class="emailbody" style="padding-top:10px;">{$comment->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
	{/if}
	<br clear="all">
	
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

</div>

{if !$embed}
<script type="text/javascript">
$(function() {
	$('#comment{$comment->id}')
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
					if(e.id && e.comment_html)
						$('#comment' + e.id).html(e.comment_html);
				})
				.on('cerb-peek-deleted', function(e) {
					$('#comment' + e.id).remove();
				})
		;
});
</script>
{/if}