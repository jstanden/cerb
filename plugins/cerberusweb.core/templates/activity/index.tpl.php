<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h1>{$translate->_('core.menu.activity')|capitalize}</h1>

<div id="activityOptions"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();
{/literal}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate}',
    dataSrc: '{devblocks_url}ajax.php?c=activity&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('activityOptions');
</script>




