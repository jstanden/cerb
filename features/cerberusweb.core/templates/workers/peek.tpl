{$div_id = "peek{uniqid()}"}

<div id="{$div_id}">
	<div style="float:left;margin-right:10px;">
		<img src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
	</div>
	
	<div style="float:left;">
		<h1 style="color:inherit;">
			{$worker->getName()}
			
			{if $worker->gender == 'M'}
			<span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);vertical-align:middle;"></span>
			{elseif $worker->gender == 'F'}
			<span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);vertical-align:middle;"></span>
			{/if}
		</h1>
		
		{if $worker->title}
		<div>
			{$worker->title}
		</div>
		{/if}
		
		<div style="margin-top:5px;">
			{if $active_worker->is_superuser}<button type="button" class="cerb-peek-edit" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>{/if}
			{if $worker}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

<div style="clear:both;padding-top:10px;"></div>

<fieldset class="peek">
	<legend>{'common.activity'|devblocks_translate|capitalize}</legend>
	
	<div style="margin-bottom:5px;">
		<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:{if $worker->last_activity_date > time() - 900}rgb(0,180,0){else}rgb(230,230,230){/if};margin-right:5px;line-height:10px;"></div><b>{$worker->getName()}</b> {if $worker->last_activity_date}was last active <abbr title="{$worker->last_activity_date|devblocks_date}">{$worker->last_activity_date|devblocks_prettytime}</abbr>{else}has never logged in{/if}
	</div>
	
	<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-query="member:{$worker->id}"><div class="badge-count">{$activity_counts.groups|default:0}</div> {'common.groups'|devblocks_translate|capitalize}</button>
		{*<button type="button"><div class="badge-count">{$activity_counts.comments|default:0}</div> {'common.comments'|devblocks_translate|capitalize}</button>*}
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Tickets Owned</legend>
		<div>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:{$worker->id} status:o,w,c"><div class="badge-count">{$activity_counts.tickets.total|default:0}</div> {'common.all'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:{$worker->id} status:o"><div class="badge-count">{$activity_counts.tickets.open|default:0}</div> {'status.open'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:{$worker->id} status:w"><div class="badge-count">{$activity_counts.tickets.waiting|default:0}</div> {'status.waiting'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-query="owner:{$worker->id} status:c"><div class="badge-count">{$activity_counts.tickets.closed|default:0}</div> {'status.closed'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/peek/peek_links.tpl" links=$links links_label="{'common.watching'|devblocks_translate|capitalize}"}

{* [TODO] Custom fields and fieldsets *}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.worker'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Edit button
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_WORKER}&context_id={$worker->id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				genericAjaxPopupClose($layer);
			})
			;
		
		// Searches
		$popup.find('.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// Menus
		$popup.find('ul.cerb-menu').menu();
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}';
			}
		});
		
	});
});
</script>