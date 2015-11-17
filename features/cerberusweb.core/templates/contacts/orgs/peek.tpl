{$div_id = "peek{uniqid()}"}

{capture name="mailing_address"}
{if $org->street}<div>{$org->street|escape:'html'|nl2br nofilter}</div>{/if}
<div>
{if $org->city}{$org->city}{/if}{if $org->city && $org->province}, {/if}
{if $org->province}{$org->province}{/if} {if $org->postal}{$org->postal}{/if}
</div>
{if $org->country}<div>{$org->country}</div>{/if}
{/capture}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;">
		<h1 style="color:inherit;">
			{$org->name}
		</h1>
		
		{if $smarty.capture.mailing_address}
		<div>
		{$smarty.capture.mailing_address nofilter}
		</div>
		{/if}
		
		<div style="margin-top:5px;">
			{if !empty($org->id)}
				{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_ORG, array($org->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_ORG context_id=$org->id full=true}
			{/if}
			
			<button type="button" class="cerb-peek-edit" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			{if $org}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

<div style="clear:both;padding-top:10px;"></div>

<fieldset class="peek">
	<legend>Contact Info</legend>
	
	{if $org->phone}
	<div style="float:left;width:200px;margin:0px 5px 5px 0px;">
		<b>{'common.phone'|devblocks_translate|capitalize}:</b><br>
		<a href="tel:{$org->phone}">{$org->phone}</a>
	</div>
	{/if}
	
	{if $org->website}
	<div style="float:left;width:200px;margin:0px 5px 5px 0px;">
		<b>{'common.website'|devblocks_translate|capitalize}:</b><br>
		<a href="{$org->website}" target="_blank">{$org->website}</a>
	</div>
	{/if}
	
	<div style="clear:both;"></div>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-query="org.id:{$org->id}"><div class="badge-count">{$activity_counts.contacts|default:0}</div> {'common.contacts'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="org.id:{$org->id}"><div class="badge-count">{$activity_counts.emails|default:0}</div> {'common.email_addresses'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.tickets'|devblocks_translate|capitalize}</legend>

	<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$org->id} status:o,w,c"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$org->id} status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$org->id} status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$org->id} status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/peek/peek_links.tpl" links=$links}


<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Edit button
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ORG}&context_id={$org->id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				genericAjaxPopupClose($layer);
			})
			;
		
		// Searches
		$popup.find('button.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=org&id={$org->id}-{$org->name|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=org&id={$org->id}-{$org->name|devblocks_permalink}{/devblocks_url}';
			}
		});
	});
});
</script>