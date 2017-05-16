{$div_id = "peek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_MESSAGE}

<div id="{$div_id}">
	<div style="float:left;">
		<div>
			<h1>{$dict->ticket_subject}</h1>
		</div>

		<div style="margin:5px 0px 10px 0px;">
			{if $is_writeable}
			<button type="button" class="cerb-peek-edit" data-context="{CerberusContexts::CONTEXT_MESSAGE}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if $is_readable}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-link"></span> {'common.permalink'|devblocks_translate|capitalize}</button>{/if}
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
			{if $k == ''}
			{else}
				{include file="devblocks:cerberusweb.core::internal/peek/peek_property_grid_cell.tpl" dict=$dict k=$k labels=$labels types=$types}
			{/if}
			</div>
			{/if}
		{/foreach}
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties_links=$links peek=true page_context=$peek_context page_context_id=$dict->id links_label="{'common.links'|devblocks_translate|capitalize}"}

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
		// Title
		$popup.dialog('option','title', '{'common.message'|devblocks_translate|escape:'javascript' nofilter}');
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Edit button
		{if $is_writeable}
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_MESSAGE}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
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
		
		// Searches
		$popup.find('button.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// Peek triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=ticket&id={$dict->ticket_mask}&what=message&msgid={$dict->id}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=ticket&id={$dict->ticket_mask}&what=message&msgid={$dict->id}{/devblocks_url}';
			}
		});
		
		// Timeline
		{if $is_readable}
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
		{/if}
	});
});
</script>