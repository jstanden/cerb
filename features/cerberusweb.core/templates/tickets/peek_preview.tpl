{$preview_id = "preview{uniqid()}"}
<div id="{$preview_id}">
{if $message && $message instanceof Model_Message}
	{$message_sender = $message->getSender()}
	{$message_contact = $message_sender->getContact()}
	{$message_worker = $message->getWorker()}
	{$message_headers = $message->getHeaders()}
	
	<span class="tag" style="{if !$message->is_outgoing}color:rgb(185,50,40);{else}color:rgb(100,140,25);{/if}">{if $message->is_outgoing}{if $is_not_sent}{'mail.saved'|devblocks_translate|lower}{else}{'mail.sent'|devblocks_translate|lower}{/if}{else}{'mail.received'|devblocks_translate|lower}{/if}</span>
	
	{if $message->was_encrypted}
		<span class="tag" style="background-color:rgb(250,220,74);color:rgb(165,100,33);" title="{'common.encrypted'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-lock"></span></span>
	{/if}
	
	{if $message_worker}
		{if $message->was_signed}<span class="glyphicons glyphicons-circle-ok" style="color:rgb(66,131,73);" title="{'common.signed'|devblocks_translate|capitalize}"></span>{/if}
		<a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$message_worker->id}">{if 0 != strlen($message_worker->getName())}{$message_worker->getName()}{else}&lt;{$message_worker->getEmailString()}&gt;{/if}</a>
	{else}
		{if $message_contact}
			{if $message->was_signed}<span class="glyphicons glyphicons-circle-ok" style="color:rgb(66,131,73);" title="{'common.signed'|devblocks_translate|capitalize}"></span>{/if}
			{$message_contact_org = $message_contact->getOrg()}
			<a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$message_contact->id}">{$message_contact->getName()}</a>
			&nbsp;
			{if $message_contact->title}
				{$message_contact->title}
			{/if}
			{if $message_contact->title && $message_contact_org} at {/if}
			{if $message_contact_org}
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$message_contact_org->id}"><b>{$message_contact_org->name}</b></a>
			{/if}
		{else}
			{if $message->was_signed}<span class="glyphicons glyphicons-circle-ok" style="color:rgb(66,131,73);" title="{'common.signed'|devblocks_translate|capitalize}"></span>{/if}
			<a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$message_sender->id}">&lt;{$message_sender->email}&gt;</a>
		{/if}
	{/if}
	
	<div style="float:left;margin:0px 5px 5px 0px;">
		{if $message_worker}
			<img src="{devblocks_url}c=avatars&context=worker&context_id={$message_worker->id}{/devblocks_url}?v={$message_worker->updated}" style="height:64px;width:64px;border-radius:64px;">
		{else}
			{if $message_contact}
			<img src="{devblocks_url}c=avatars&context=contact&context_id={$message_contact->id}{/devblocks_url}?v={$message_contact->updated_at}" style="height:64px;width:64px;border-radius:64px;">
			{else}
			<img src="{devblocks_url}c=avatars&context=address&context_id={$message_sender->id}{/devblocks_url}?v={$message_sender->updated}" style="height:64px;width:64px;border-radius:64px;">
			{/if}
		{/if}
	</div>
	
	<div>
		<div>
			<b>{'message.header.from'|devblocks_translate|capitalize}:</b> {$message_headers.from}
		</div>
		<div>
			<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$message_headers.to}
		</div>
		<div>
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$message->created_date|devblocks_date} ({$message->created_date|devblocks_prettytime})
		</div>
	</div>
	
	<div style="clear:both;"></div>
	
	<div style="margin:2px;padding:5px;">
		<pre class="emailbody">{$message->getContent()|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
	</div>
	
	{* Attachments *}
	{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_MESSAGE}" context_id=$message->id}
	
	{* Custom Fields *}
	{$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $message->id))|default:[]}
	{$message_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_MESSAGE, $message->id, $values)}
	<div style="margin-top:10px;">
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$message_custom_fieldsets}
	</div>
	
{elseif $comment && $comment instanceof Model_Comment}
	{$owner_meta = $comment->getOwnerMeta()}
	
	{if $comment->context == CerberusContexts::CONTEXT_MESSAGE}
	<span class="tag" style="background-color:rgb(238,88,31);color:white;margin-right:5px;">{'display.ui.sticky_note'|devblocks_translate|lower}</span>
	{else}
	<span class="tag" style="background-color:rgb(71,133,210);color:white;margin-right:5px;">{'common.comment'|devblocks_translate|lower}</span>
	{/if}
	
	<b>
		{if empty($owner_meta)}
			(system)
		{else}
			{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
			<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$comment->owner_context}&context_id={$comment->owner_context_id}', null, false, '50%');">{$owner_meta.name}</a>
			{elseif !empty($owner_meta.permalink)} 
			<a href="{$owner_meta.permalink}" target="_blank">{$owner_meta.name}</a>
			{else}
			{$owner_meta.name}
			{/if}
		{/if}
	</b>
	
	({$owner_meta.context_ext->manifest->name|lower})
	
	{if isset($owner_meta.context_ext->manifest->params.alias)}
	<div style="float:left;margin:0px 5px 5px 0px;">
		<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:32px;width:32px;border-radius:32px;">
	</div>
	{/if}
	
	<br>
	
	{if isset($comment->created)}<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$comment->created|devblocks_date} ({$comment->created|devblocks_prettytime})<br>{/if}
	
	<div style="clear:both;"></div>
	
	<div style="margin:2px;padding:5px;">
		<pre class="emailbody">{$comment->comment|truncate:5000|trim|escape|devblocks_hyperlinks nofilter}</pre>
	</div>
	
	{* Attachments *}
	{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id}
{/if}
</div>

<script type="text/javascript">
$(function() {
	var $preview = $('#{$preview_id}');
	$preview.find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>