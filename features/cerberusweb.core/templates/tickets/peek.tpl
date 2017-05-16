{$div_id = "peek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_TICKET}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=org&context_id={$dict->org_id}{/devblocks_url}?v={$dict->org_updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;font-weight:bold;">
		<div>
			{$dict->mask}
		</div>
	
		<h1 style="color:inherit;">
			{$dict->subject}
		</h1>
		
		<div style="margin-top:5px;">
			{*<button type="button" class="" onclick="genericAjaxPopup('va','c=internal&a=startBotInteraction', null, false, '300');"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:22px;height:22px;margin:-3px 0px 0px 2px;"></button>*}
			
			{if $is_writeable}
				{if !empty($dict->id)}
					{$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full=true}
				{/if}
			{/if}
			
			{if $is_writeable}<button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>{/if}
			{if $is_readable}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			{if $is_writeable}<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
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
			{if $k == 'status'}
				<label>{$labels.$k}</label>
				{$dict->$k}
				{if in_array($dict->status_id,[Model_Ticket::STATUS_WAITING, Model_Ticket::STATUS_CLOSED])}
					{if $dict->reopen_date} (<abbr title="{$dict->reopen_date|devblocks_date}">{$dict->reopen_date|devblocks_prettytime}</abbr>){/if}
				{/if}
			{elseif $k == 'importance'}
				<label>{$labels.$k}</label>
				<div style="display:inline-block;margin-top:5px;width:75px;height:8px;background-color:rgb(220,220,220);border-radius:8px;">
					<div style="position:relative;top:-1px;margin-left:-5px;left:{$dict->importance}%;width:10px;height:10px;border-radius:10px;background-color:{if $dict->importance < 50}rgb(0,200,0);{elseif $dict->importance > 50}rgb(230,70,70);{else}rgb(175,175,175);{/if}"></div>
				</div>
			{elseif $k == 'spam_training'}
				<label>{$labels.$k}</label>
				{if $dict->$k == 'N'}
					{'common.notspam'|devblocks_translate|capitalize}
				{elseif $dict->$k == 'S'}
					{'common.spam'|devblocks_translate|capitalize}
				{/if}
			{else}
				{include file="devblocks:cerberusweb.core::internal/peek/peek_property_grid_cell.tpl" dict=$dict k=$k labels=$labels types=$types}
			{/if}
		</div>
		{/if}
		{/foreach}
	</div>
	
	<div style="clear:both;"></div>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="ticket.id:{$dict->id}"><div class="badge-count">{$activity_counts.participants|default:0}</div> {'common.participants'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_MESSAGE}" data-query="ticket.id:{$dict->id}"><div class="badge-count">{$activity_counts.messages|default:0}</div> {'common.messages'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-query="on.ticket:(id:{$dict->id})"><div class="badge-count">{$activity_counts.comments|default:0}</div> {'common.comments'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties_links=$links peek=true page_context=$peek_context page_context_id=$dict->id}

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

{if $is_readable}
{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_pager.tpl"}
{/if}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	{if $is_readable}var $timeline = {$timeline_json|default:'{}' nofilter};{/if}
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.ticket'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Edit button
		{if $is_writeable}
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				genericAjaxPopupClose($layer);
			})
			;
		{/if}
		
		// Comments
		{if $is_readable}
		$popup.find('button.cerb-peek-comments-add')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function() {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			;
		{/if}
		
		// Peek triggers
		$popup.find('a.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Searches
		$popup.find('button.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=ticket&id={$dict->id}-{$dict->subject|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=ticket&id={$dict->id}-{$dict->subject|devblocks_permalink}{/devblocks_url}';
			}
		});
		
		// Timeline
		{if $is_readable}
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
		{/if}
	});
});
</script>