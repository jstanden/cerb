{$div_id = "peek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_ORG}

{capture name="mailing_address"}
{if $dict->street}<div>{$dict->street|escape:'html'|nl2br nofilter}</div>{/if}
<div>
{if $dict->city}{$dict->city}{/if}{if $dict->city && $dict->province}, {/if}
{if $dict->province}{$dict->province}{/if} {if $dict->postal}{$dict->postal}{/if}
</div>
{if $dict->country}<div>{$dict->country}</div>{/if}
{/capture}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=org&context_id={$dict->id}{/devblocks_url}?v={$dict->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;">
		<h1>
			{$dict->name}
		</h1>
		
		{if $smarty.capture.mailing_address}
		<div>
		{$smarty.capture.mailing_address nofilter}
		</div>
		{/if}
		
		<div style="margin-top:5px;">
			{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
		
			{if !empty($dict->id)}
				{$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full=true}
			{/if}
			
			<button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			{if $dict->id}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</div>

<div style="clear:both;padding-top:10px;"></div>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div class="cerb-properties-grid" data-column-width="100">
	
		{$labels = $dict->_labels}
		{$types = $dict->_types}
		{foreach from=$properties item=k name=props}
			{if $dict->$k}
			<div>
			{if $k == ''}
			{else}
				{include file="devblocks:cerberusweb.core::internal/peek/peek_property_grid_cell.tpl" dict=$dict k=$k labels=$labels types=$types}
			{/if}
			</div>
			{/if}
		{/foreach}
		
	</div>
	
	<div style="clear:both;"></div>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-query="org.id:{$dict->id}"><div class="badge-count">{$activity_counts.contacts|default:0}</div> {'common.contacts'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="org.id:{$dict->id}"><div class="badge-count">{$activity_counts.emails|default:0}</div> {'common.email_addresses'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.tickets'|devblocks_translate|capitalize}</legend>

	<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$dict->id} status:[o,w,c]"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$dict->id} status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$dict->id} status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="org.id:{$dict->id} status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="0" data-edit="org.id:{$dict->id}" data-width="75%">{'mail.send_mail'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties_links=$links peek=true page_context=$peek_context page_context_id=$dict->id}

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_pager.tpl"}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');

	var $timeline = {$timeline_json|default:'{}' nofilter};
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Edit button
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				genericAjaxPopupClose($layer);
			})
			;
		
		// Comments
		$popup.find('button.cerb-peek-comments-add')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function() {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			;
		
		// Searches
		$popup.find('button.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// Peeks
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=org&id={$dict->id}-{$dict->name|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=org&id={$dict->id}-{$dict->name|devblocks_permalink}{/devblocks_url}';
			}
		});
		
		// Interactions
		var $interaction_container = $popup;
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
		
		// Timeline
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
	});
});
</script>