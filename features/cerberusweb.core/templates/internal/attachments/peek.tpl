{$div_id = uniqid()}
{$peek_context = CerberusContexts::CONTEXT_ATTACHMENT}
{$is_downloadable = Context_Attachment::isDownloadableByActor($dict, $active_worker)}
{$is_writeable = Context_Attachment::isWriteableByActor($dict, $active_worker)}

<div id="{$div_id}">
	
	<div style="float:left;">
		<h1>
			{$dict->_label}
		</h1>
		
		<div style="margin-top:5px;">
			{if $is_downloadable}
			<button type="button" class="cerb-peek-download"><span class="glyphicons glyphicons-cloud-download"></span> {'common.download'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
			<button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if $dict->id}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			{if $active_worker->hasPriv("contexts.{$peek_context}.comment")}<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

<div style="clear:both;padding-top:10px;"></div>

{if $is_downloadable}
<div style="margin:10px;">
	{if !$dict->mime_type}
		{* ... do nothing ... *}
	{elseif in_array($dict->mime_type, [ 'audio/ogg', 'audio/mpeg', 'audio/wav', 'audio/x-wav' ])}
		<audio controls width="100%">
			<source src="{devblocks_url full=true}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" type="{$dict->mime_type}">
			Your browser does not support HTML5 audio.
		</audio>
	{elseif in_array($dict->mime_type, [ 'video/mp4', 'video/mpeg', 'video/quicktime' ])}
		<video controls width="100%">
			<source src="{devblocks_url full=true}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" type="{$dict->mime_type}">
			Your browser does not support HTML5 video.
		</video>
	{elseif in_array($dict->mime_type, [ 'image/png', 'image/jpg', 'image/jpeg', 'image/gif' ])}
		<img src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="max-width:100%;border:1px solid rgb(200,200,200);">
	{elseif in_array($dict->mime_type, [ 'application/json', 'message/rfc822', 'text/css', 'text/csv', 'text/javascript', 'text/plain', 'text/xml' ])}
		{if $dict->size < 1000000}
		<iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px;border:1px solid rgb(200,200,200);"></iframe>
		{/if}
	{elseif in_array($dict->mime_type, [ 'application/pgp-signature', 'multipart/encrypted', 'multipart/signed' ])}
		{if $dict->size < 1000000}
		<iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px;border:1px solid rgb(200,200,200);"></iframe>
		{/if}
	{elseif in_array($dict->mime_type, [ 'application/xhtml+xml', 'text/html' ])}
		{if $dict->size < 1000000}
		<iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px;border:1px solid rgb(200,200,200);"></iframe>
		{/if}
	{elseif in_array($dict->mime_type, [ 'application/pdf' ])}
		{if $dict->size < 5000000}
		<object data="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" width="100%" height="350"></object>
		{/if}
	{/if}
</div>
{/if}

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
	
	{if $context_counts}
	<div style="margin-top:5px;">
		{foreach from=$context_counts item=count key=context_ext_id}
			{$context = $contexts.$context_ext_id}
			{if $context}
				<button type="button" class="cerb-search-trigger" data-context="{$context_ext_id}" data-query="attachments:(id:{$dict->id})"><div class="badge-count">{$count|default:0}</div> {$context->name}</button>
			{/if}
		{/foreach}
	</div>
	{/if}
	
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
		$popup.dialog('option','title', "{'common.attachment'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Download button
		{if $is_downloadable}
		$popup.find('button.cerb-peek-download')
			.on('click', function(e) {
				window.open('{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}?download=');
			});
		{/if}
		
		// Edit button
		{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
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
				window.open('{devblocks_url}c=profiles&type=attachment&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}', '_blank', 'noopener');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=attachment&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}';
			}
		});
		
		// Timeline
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
	});
});
</script>
