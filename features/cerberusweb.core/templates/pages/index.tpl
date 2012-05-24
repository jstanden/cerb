<div style="margin-top:5px;"></div>

{$menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json','')}
{$menu = json_decode($menu_json, true)}

{if empty($menu) || $tour_enabled}
<form action="#" onsubmit="return false;">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Configuring your menu shortcuts</h1>
	
	<p>
		Pages allow you to build a completely personalized interface based on your needs.
		
		Your most frequently used pages can be added to the menu above by clicking on the <button type="button"><span class="cerb-sprite2 sprite-plus-circle"></span></button> button.
	</p>
	
	<p>
		New pages can be added by clicking on the <span class="cerb-sprite2 sprite-plus-circle-frame" style="vertical-align:middle;"></span> icon in the <span class="help callout-worklist">Pages</span> list below.
	</p>
	
	<p style="margin-top:10px;">
		<a href="javascript:;" onclick="genericAjaxPopup('peek','c=pages&a=showPageWizardPopup&view_id={$view->id}',null,true,'500');" style="font-weight:bold;">Help me create a page!</a>
	</p>
</div>
</form>
{/if}

<div style="float:left;">
	<h2>Pages</h2>
</div>

<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}