{include file="devblocks:cerberusweb.calls::calls/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{$call->subject}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">

		<b>{'common.updated'|devblocks_translate|capitalize}:</b> <abbr title="{$call->updated_date|devblocks_date}">{$call->updated_date|devblocks_prettytime}</abbr> &nbsp;
		<b>{'call_entry.model.phone'|devblocks_translate}:</b> {$call->phone} &nbsp; 
		<b>{'call_entry.model.is_closed'|devblocks_translate}:</b> {if $call->is_closed}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if} &nbsp; 
		<b>{'call_entry.model.is_outgoing'|devblocks_translate}:</b> {if $call->is_outgoing}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if} &nbsp; 
		<br>
			
		{*
		{assign var=opp_worker_id value=$opp->worker_id}
		<b>{'common.status'|devblocks_translate|capitalize}:</b> {if $opp->is_closed}{if $opp->is_won}{'crm.opp.status.closed.won'|devblocks_translate|capitalize}{else}{'crm.opp.status.closed.lost'|devblocks_translate|capitalize}{/if}{else}{'crm.opp.status.open'|devblocks_translate|capitalize}{/if} &nbsp;
		<b>{'common.email'|devblocks_translate|capitalize}:</b> {$address->first_name} {$address->last_name} &lt;<a href="javascript:;" onclick="$('#btnOppAddyPeek').click();">{$address->email}</a>&gt; &nbsp;
		<b>{'crm.opportunity.created_date'|devblocks_translate|capitalize}:</b> <abbr title="{$opp->created_date|devblocks_date}">{$opp->created_date|devblocks_prettytime}</abbr> &nbsp;
		{if !empty($opp_worker_id) && isset($workers.$opp_worker_id)}
			<b>{'common.worker'|devblocks_translate|capitalize}:</b> {$workers.$opp_worker_id->getName()} &nbsp;
		{/if}
		<br>
		*}
		
		<!-- Toolbar -->
		<button type="button" id="btnDisplayCallEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.calls.call.toolbaritem', true)}
		{foreach from=$toolbar_exts item=ext}
			{$ext->render($opp)}
		{/foreach}
		
		</form>
		<br>
	</td>
</tr>
</table>

<div id="callTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.call&id={$call->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.call&id={$call->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		

		{$tabs = [notes,links]}

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

	switch(event.which) {
//		case 97:  // (A) E-mail Peek
//			try {
//				$('#btnOppAddyPeek').click();
//			} catch(e) { } 
//			break;
		default:
			// We didn't find any obvious keys, try other codes
			break;
	}
});
{/if}
</script>
