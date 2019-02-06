<div style="margin-top:5px;"></div>

<form action="#" onsubmit="return false;">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Let's add some pages to your menu</h1>
	
	<p>
		Pages allow you to build a completely personalized interface based on your needs.
		
		Your most frequently used pages can be added to the menu above by clicking on the <button type="button"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(150,150,150);"></span></button> button.
	</p>

	{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_PAGE}.create")}
	<p>
		New pages can be added by clicking on the <span class="help callout-worklist" style="cursor:pointer;" onclick="$('#viewpages a[title=Add]').click();"><span class="glyphicons glyphicons-circle-plus"></span></span> icon in the <b>Pages</b> list below.
	</p>
	{/if}
</div>
</form>

<div>
	<h2>Pages</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}