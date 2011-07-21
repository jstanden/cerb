{include file="devblocks:cerberusweb.calls::calls/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h2>{'calls.common.call'|devblocks_translate|capitalize}</h2> 

		<fieldset class="properties">
			<legend>{$call->subject|truncate:128}</legend>
			
			<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">
		
				{foreach from=$properties item=v key=k name=props}
					<div class="property">
						{if $k == '...'}
							<b>{$translate->_('...')|capitalize}:</b>
							...
						{else}
							{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
						{/if}
					</div>
					{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
						<br clear="all">
					{/if}
				{/foreach}
				<br clear="all">
				
				<!-- Toolbar -->
				
				<span>
				{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CALL, array($call->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_CALL context_id=$call->id full=true}
				</span>		
				
				<button type="button" id="btnDisplayCallEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
				
				{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.calls.call.toolbaritem', true)}
				{foreach from=$toolbar_exts item=ext}
					{$ext->render($opp)}
				{/foreach}
				
			</form>
			
			{if $pref_keyboard_shortcuts}
			<small>
				{$translate->_('common.keyboard')|lower}:
				(<b>e</b>) {'common.edit'|devblocks_translate|lower}
			</small> 
			{/if}
		</fieldset>
	</td>
</tr>
</table>

<div id="callTabs">
	<ul>
		{$tabs = [activity,notes,links]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_CALL}&context_id={$call->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.call&id={$call->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.call&id={$call->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=calls&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#callTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplayCallEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=calls&a=showEntry&id={$call->id}',null,false,'550');
			$popup.one('call_save', function(event) {
				event.stopPropagation();
				document.location.reload();
			});
		})
	});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	hotkey_activated = true;
	
	switch(event.which) {
		case 101:  // (E) edit
			try {
				$('#btnDisplayCallEdit').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			hotkey_activated = false;
			break;
	}
	
	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>
