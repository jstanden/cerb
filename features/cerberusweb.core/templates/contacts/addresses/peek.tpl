{$div_id = "peek{uniqid()}"}

{$contact = DAO_Contact::get($address.a_contact_id)}
{$org = DAO_ContactOrg::get($address.a_contact_org_id)}

<div id="{$div_id}">
	{if $contact}
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=contact&context_id={$contact->id}{/devblocks_url}?v={$contact->updated_at}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	{elseif $org}
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	{else}
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=address&context_id=0{/devblocks_url}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	{/if}
	
	<div style="float:left;">
		<h1 style="color:inherit;">{$address.a_email}</h1>

		<div style="margin:5px 0px 10px 0px;">
			{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_ADDRESS, array($address.a_id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_ADDRESS context_id=$address.a_id full=true}
			
			<button type="button" class="cerb-peek-edit" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$address.a_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			
			{if $address}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			
			{$email_parts = explode('@',$address.a_email)}
			{if is_array($email_parts) && 2==count($email_parts)}
				{$domain = $email_parts.1}
				<button type="button" onclick="window.open('http://www.{$domain|escape:'url'}');"><span class="glyphicons glyphicons-link"></span> {'common.website'|devblocks_translate|capitalize}</button>
				<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="email:(*@{$domain})"><span class="glyphicons glyphicons-search"></span> Similar</button>
			{/if}
		</div>
	</div>
</div>

<div style="clear:both;padding-top:10px;"></div>

<fieldset class="peek">
	<legend>Contact Info</legend>
	
	<div style="float:left;width:200px;margin:0px 5px 5px 0px;">
		<b>{'common.contact'|devblocks_translate|capitalize}:</b><br>
		{if $contact}
			<img src="{devblocks_url}c=avatars&context=contact&context_id={$contact->id}{/devblocks_url}?v={$contact->updated_at}" style="height:16px;width:16px;border-radius:1px;vertical-align:middle;">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$contact->id}">{$contact->getName()}</a>
		{else}
			({'common.none'|devblocks_translate|lower})
		{/if}
	</div>
	
	<div style="float:left;width:200px;margin:0px 5px 5px 0px;">
		<b>{'common.organization'|devblocks_translate|capitalize}:</b><br>
		{if $org}
			<img src="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}" style="height:16px;width:16px;border-radius:1px;vertical-align:middle;">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}">{$org->name}</a>
		{else}
			({'common.none'|devblocks_translate|lower})
		{/if}
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.tickets'|devblocks_translate|capitalize}</legend>
	
	<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$address.a_id} status:o,w,c"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$address.a_id} status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$address.a_id} status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$address.a_id} status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
	</div>

{include file="devblocks:cerberusweb.core::internal/peek/peek_links.tpl" links=$links}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	$popup.one('popup_open',function(event,ui) {
		// Title
		$popup.dialog('option','title', '{'common.email_address'|devblocks_translate|escape:'javascript' nofilter}');
		
		// Edit button
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$address.a_id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				genericAjaxPopupClose($layer);
			})
			;
		
		// Searches
		$popup.find('button.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// Peek triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=address&id={$address.a_id}-{$address.a_email|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=address&id={$address.a_id}-{$address.a_email|devblocks_permalink}{/devblocks_url}';
			}
		});
	});
});
</script>