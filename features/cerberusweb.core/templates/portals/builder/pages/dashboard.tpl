{$uniqid = uniqid()}
<article id="dashboard{$uniqid}" class="cerb-portal-dashboard">
	<div class="cerb-portal-wrapper">
		{if 'sidebar_left' == $layout}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--sidebar-left" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="sidebar" class="cerb-portal-dashboard-layout-zone" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.sidebar item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="content" class="cerb-portal-dashboard-layout-zone" style="flex:2 2 66%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.content item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
			</div>
		{elseif 'sidebar_right' == $layout}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--sidebar-right" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="content" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--content" style="flex:2 2 66%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.content item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="sidebar" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--sidebar" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.sidebar item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
			</div>
		{elseif 'thirds' == $layout}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--thirds" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="left" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--left" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.left item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="center" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--center" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.center item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="right" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--right" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.right item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
			</div>
		{else}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="content" class="cerb-portal-dashboard-layout-zone" style="flex:1 1 100%;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.content item=widget name=widgets}
						{include file="devblocks:cerberusweb.core::portals/builder/pages/dashboard/widget.tpl" widget=$widget dict=$dict}
					{/foreach}
					</div>
				</div>
			</div>
		{/if}
	</div>
</article>

<script type="text/javascript">
$$.ready(function() {
	var $dashboard = document.querySelector('#dashboard{$uniqid}');
	
	$dashboard.addEventListener('cerb-portal-widget-refresh', function(e) {
		var widget_id = e.widget_id;
		var $widget = $dashboard.querySelector('#portalWidget' + widget_id);
		
		$widget.style.opacity = 0.2;
		
		var formData = (e.form_data && "object" == typeof e.form_data ? e.form_data : new FormData());
		formData.append('widget', widget_id);
		
		if(null == $widget)
			return;
		
		var xhr = new XMLHttpRequest();
		
		xhr.open('POST', '{devblocks_url}{$page_query}&action=updateWidget{/devblocks_url}');
		
		xhr.onreadystatechange = function() {
			if(xhr.readyState === 4, xhr.status === 200) {
				var $widget_content = $widget.querySelector('.cerb-portal-widget--content');
				$$.html($widget_content, xhr.responseText);
				
				$widget.style.opacity = 1.0;
			}
		}
		
		xhr.send(formData);
	});
});
</script>