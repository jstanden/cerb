<div id="tourMyAcctWatchers"></div>
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=preferences&a=handleTabAction&tab=core.pref.notifications&action=showWatcherPanel&id=0',null,false,'550px',function(o){literal}{{/literal} genericAjaxPostAfterSubmitEvent.subscribe(function(type,args){literal}{{/literal} genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');{literal}}{/literal}); {literal}}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> Add Watcher Filter</button>
</form>

<div id="view{$view->id}">{$view->render()}</div>