<div id="tourMyAcctWatchers"></div>
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=preferences&a=handleTabAction&tab=core.pref.notifications&action=showWatcherPanel&id=0&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite sprite-funnel"></span> Add Watcher Filter</button>
</form>

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}