{$div_id = "peek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$is_writeable = Context_CalendarEvent::isWriteableByActor($dict, $active_worker)}

<div id="{$div_id}">
	
	<div style="float:left;">
		<h1>
			{$dict->_label}
		</h1>
		
		<div style="margin-top:5px;">
			{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
		
			{if $dict->id}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			
			{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
			<button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if !empty($dict->id)}
				{$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full_label=true}
			{/if}
			
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
			{elseif $k == 'importance'}
				<label>{$labels.$k}</label>
				<div style="display:inline-block;margin-top:5px;width:75px;height:8px;background-color:rgb(220,220,220);border-radius:8px;">
					<div style="position:relative;top:-1px;margin-left:-5px;left:{$dict->importance}%;width:10px;height:10px;border-radius:10px;background-color:{if $dict->importance < 50}rgb(0,200,0);{elseif $dict->importance > 50}rgb(230,70,70);{else}rgb(175,175,175);{/if}"></div>
				</div>
			{else}
				{include file="devblocks:cerberusweb.core::internal/peek/peek_property_grid_cell.tpl" dict=$dict k=$k labels=$labels types=$types}
			{/if}
			</div>
			{/if}
		{/foreach}
	</div>
	
	<div style="clear:both;"></div>
	
	{include file="devblocks:cerberusweb.core::internal/peek/peek_search_buttons.tpl"}
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
		$popup.dialog('option','title', "{'common.calendar.event'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		$popup.find('#btnProfileCard').cerbPeekTrigger();
		
		// Edit button
		{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				e.type = 'peek_saved';
				$popup.trigger(e);
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				e.type = 'peek_deleted';
				$popup.trigger(e);
				genericAjaxPopupClose($layer);
			})
			;
		{/if}
		
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
		
		// Menus
		$popup.find('ul.cerb-menu').menu();
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=calendar_event&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}', '_blank', 'noopener');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=calendar_event&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}';
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