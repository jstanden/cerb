{$div_id = "peek{uniqid()}"}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=address&context_id={$dict->id}{/devblocks_url}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;">
		<h1 style="color:inherit;">{$dict->email}</h1>

		<div style="margin:5px 0px 10px 0px;">
			{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_ADDRESS, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_ADDRESS context_id=$dict->id full=true}
			
			<button type="button" class="cerb-peek-edit" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			
			{if $dict->id}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			
			{$email_parts = explode('@',$dict->email)}
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
</fieldset>

<fieldset class="peek">
	<legend>{'common.tickets'|devblocks_translate|capitalize}</legend>
	
	<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$dict->id} status:o,w,c"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$dict->id} status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$dict->id} status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant.id:{$dict->id} status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/peek/peek_links.tpl" links=$links}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	$popup.one('popup_open',function(event,ui) {
		// Title
		$popup.dialog('option','title', '{'common.email_address'|devblocks_translate|escape:'javascript' nofilter}');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Edit button
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
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
				window.open('{devblocks_url}c=profiles&type=address&id={$dict->id}-{$dict->email|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=address&id={$dict->id}-{$dict->email|devblocks_permalink}{/devblocks_url}';
			}
		});
	});
});
</script>