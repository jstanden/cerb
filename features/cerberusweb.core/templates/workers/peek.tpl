{$div_id = "peek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_WORKER}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=worker&context_id={$dict->id}{/devblocks_url}?v={$dict->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;">
		<h1>
			{$dict->full_name}
			
			{if $dict->gender == 'M'}
			<span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);vertical-align:middle;"></span>
			{elseif $dict->gender == 'F'}
			<span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);vertical-align:middle;"></span>
			{/if}
		</h1>
		
		{if $dict->title}
		<div>
			{$dict->title}
		</div>
		{/if}
		
		<div style="margin-top:5px;">
			{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
			{if $dict->id}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			{if $active_worker->is_superuser}<button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>{/if}
			{if $active_worker->hasPriv("contexts.{$peek_context}.comment")}<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
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
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-query="member:(id:{$dict->id})"><div class="badge-count">{$activity_counts.groups|default:0}</div> {'common.groups'|devblocks_translate|capitalize}</button>
		{*<button type="button"><div class="badge-count">{$activity_counts.comments|default:0}</div> {'common.comments'|devblocks_translate|capitalize}</button>*}
	</div>
	
</fieldset>

<fieldset class="peek">
	<legend>{'common.activity'|devblocks_translate|capitalize}</legend>
	
	{if $latest_session}
		{$latest_activity_date = $latest_session->updated}
	{elseif $latest_activity}
		{$latest_activity_date = $latest_activity->created}
	{else}
		{$latest_activity_date = 0}
	{/if}
	
	<div style="margin-bottom:5px;">
		<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:{if $latest_session && $latest_session->updated > time() - 900}rgb(0,180,0){else}rgb(230,230,230){/if};margin-right:5px;line-height:10px;"></div><b>{$dict->full_name}</b> {if $latest_activity_date}{if $latest_activity_date > time() - 900}is currently active{else}was last active <abbr title="{$latest_activity_date|devblocks_date}">{$latest_activity_date|devblocks_prettytime}</abbr>{/if} {if $latest_session}from {$latest_session->user_ip}{/if}{else}has never logged in{/if}
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Tickets Owned</legend>
		<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:(id:{$dict->id}) status:[o,w,c]"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:(id:{$dict->id}) status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:(id:{$dict->id}) status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:(id:{$dict->id}) status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties_links=$links peek=true page_context=$peek_context page_context_id=$dict->id links_label="{'common.watching'|devblocks_translate|capitalize}"}

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_pager.tpl"}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	var $timeline = {$timeline_json|default:'{}' nofilter};

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.worker'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
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
		
		// Peeks
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Searches
		$popup.find('.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// Interactions
		var $interaction_container = $popup;
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
		
		// Menus
		$popup.find('ul.cerb-menu').menu();
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=worker&id={$dict->id}-{$dict->full_name|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=worker&id={$dict->id}-{$dict->full_name|devblocks_permalink}{/devblocks_url}';
			}
		});
		
		// Timeline
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
	});
});
</script>