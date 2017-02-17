{$div_id = "peek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_CONTACT}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=contact&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;">
		<h1 style="color:inherit;">
			{$dict->_label}
			{if $dict->gender == 'M'}
			<span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);vertical-align:middle;"></span>
			{elseif $dict->gender == 'F'}
			<span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);vertical-align:middle;"></span>
			{/if}
		</h1>
		
		<div>
			{$dict->title}{if $dict->title && $dict->org_id} at {/if}
			<a href="javascript:;" style="font-weight:bold;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$dict->org_id}">{$dict->org_name}</a>
		</div>
		
		<div style="margin-top:5px;">
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
	
	<div style="clear:both;"></div>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="contact.id:{$dict->id}"><div class="badge-count">{$activity_counts.emails|default:0}</div> {if $activity_counts.emails == 1}{'common.email_address'|devblocks_translate|capitalize}{else}{'common.email_addresses'|devblocks_translate|capitalize}{/if}</button>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.activity'|devblocks_translate|capitalize}</legend>
	
	<div>
		<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:rgb(230,230,230);margin-right:5px;line-height:10px;"></div><b>{$dict->_label}</b>{if $dict->username} ({$dict->username}){/if} {if $dict->last_login_at}last logged in <abbr title="{$dict->last_login_at|devblocks_date}">{$dict->last_login_at|devblocks_prettytime}</abbr>{else}has never logged in{/if}
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.tickets'|devblocks_translate|capitalize}</legend>

	<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant:(contact.id:{$dict->id}) status:[o,w,c]"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant:(contact.id:{$dict->id}) status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant:(contact.id:{$dict->id}) status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="participant:(contact.id:{$dict->id}) status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="0" data-edit="to:{$dict->email_address}" data-width="75%">{'mail.send_mail'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties_links=$links peek=true page_context=$peek_context page_context_id=$dict->id}

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_pager.tpl"}

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$div_id}');
	var $layer = $popup.attr('data-layer');
	
	var $timeline = {$timeline_json|default:'{}' nofilter};
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Contact'|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Peek edit

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
		
		// Peek triggers
		$popup.find('.cerb-peek-trigger').
			cerbPeekTrigger()
			;
		
		// Searches
		$popup.find('button.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=contact&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=contact&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}';
			}
		});
		
		// Timeline
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
	});
});
</script>