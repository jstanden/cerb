{include file="devblocks:cerberusweb.feed_reader::feeds/item/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{$item->title}</h1> 
		<a href="{$item->url}" target="_blank">{$item->url}</a>
		
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-top:5px;">
		<b>Feed:</b> ... &nbsp; 
		<b>{'common.updated'|devblocks_translate|capitalize}:</b> <abbr title="{$item->created_date|devblocks_date}">{$item->created_date|devblocks_prettytime}</abbr> &nbsp; 
		<b>{'dao.feed_item.is_closed'|devblocks_translate}:</b> {if $item->is_closed}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if} &nbsp; 
		<br>
			
		<!-- Toolbar -->
		<button type="button" id="btnDisplayFeedItemEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.feed_reader.item.toolbaritem', true)}
		{foreach from=$toolbar_exts item=ext}
			{$ext->render($opp)}
		{/foreach}
		
		</form>
		<br>
	</td>
</tr>
</table>

<div id="feedItemTabs">
	<ul>
		{$tabs = [activity,notes,links]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context=cerberusweb.contexts.feed.item&context_id={$item->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.feed.item&id={$item->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.feed.item&id={$item->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=feeds&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
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
		var tabs = $("#feedItemTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplayFeedItemEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=feeds&a=showFeedItemPopup&id={$item->id}',null,false,'550');
			$popup.one('feeditem_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=feeds&i=item&id={$item->id}{/devblocks_url}';
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