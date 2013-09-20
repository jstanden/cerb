<style type="text/css">
DIV.dashboard-widget {
	margin:5px 5px 10px 5px;
}

DIV.dashboard-widget DIV.dashboard-widget-title {
	background-color:rgb(220,220,220);
	padding:5px 10px;
	font-size:120%;
	font-weight:bold;
	cursor:move;
	border-radius:10px;
	-webkit-border-radius:10px;
	-moz-border-radius:10px;
	-o-border-radius:10px;
}

DIV.dashboard-widget DIV.updated {
	text-align:left;
	color:rgb(200,200,200);
	display:none;
}
</style>

<form id="frmAddWidget{$workspace_tab->id}" action="#">
<button type="button" class="add_widget"><span class="cerb-sprite2 sprite-plus-circle"></span> Add Widget</button>
</form>

<table cellpadding="0" cellspacing="0" border="0" width="100%" id="dashboard{$workspace_tab->id}">
	<tr>
		<td width="33%" valign="top" class="column">
			{foreach from=$columns.0 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
		<td width="34%" valign="top" class="column">
			{foreach from=$columns.1 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
		<td width="33%" valign="top" class="column">
			{foreach from=$columns.2 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
	</tr>
</table>

<script type="text/javascript">
	
	try {
		clearInterval(window.dashboardTimer{$workspace_tab->id});
		
		var tick = function() {
			var $dashboard = $('#dashboard{$workspace_tab->id}');
			
			if($dashboard.length == 0 || !$dashboard.is(':visible')) {
				clearInterval(window.dashboardTimer{$workspace_tab->id});
				delete window.dashboardTimer{$workspace_tab->id};
				return;
			}
			
			$dashboard.find('DIV.dashboard-widget').trigger('dashboard_heartbeat');
		};
		
		//tick();
		window.dashboardTimer{$workspace_tab->id} = setInterval(tick, 1000);
		
	} catch(e) {
	}

	$frm = $('#frmAddWidget{$workspace_tab->id} button.add_widget').click(function(e) {
		$popup = genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id=0&workspace_tab_id={$workspace_tab->id}',null,false,'500');
		$popup.one('new_widget', function(e) {
			if(null == e.widget_id)
				return;
			
			var widget_id = e.widget_id;
			
			// Create the widget DOM
			$new_widget = $('<div class="dashboard-widget" id="widget' + widget_id + '"></div>');
			
			// Append it to the first column
			$dashboard = $('#dashboard{$workspace_tab->id}');
			
			$dashboard.find('tr td:first').prepend($new_widget);
			
			// Redraw
			genericAjaxGet('widget' + widget_id,'c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id=' + widget_id);
			genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id=' + widget_id,null,false,'550');
			
			// Save new order
			$dashboard.trigger('reorder');
		});
	});
	
	var $dashboard = $('#dashboard{$workspace_tab->id}');
	
	// Reusable hover events
	$dashboard.on('mouseover mouseout', 'div.dashboard-widget', 
		function(e) {
			if(e.type=='mouseover') {
				$(this).find('div.dashboard-widget-title > div.toolbar, canvas.overlay').show();
				$(this).trigger('widget-hover');
			} else {
				$(this).find('div.dashboard-widget-title > div.toolbar, canvas.overlay').hide();
				$(this).trigger('widget-unhover');
			}
		}
	);
	
	$dashboard.bind('reorder', function(e) {
		$dashboard = $(this);
		
		// [TODO] Number of columns
		$col1 = $dashboard.find('TR > TD:nth(0)').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		$col2 = $dashboard.find('TR > TD:nth(1)').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		$col3 = $dashboard.find('TR > TD:nth(2)').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		
		widget_positions = '&column[]=' + $col1 + '&column[]=' + $col2 + '&column[]=' + $col3;

		genericAjaxGet('', 'c=internal&a=handleSectionAction&section=dashboards&action=setWidgetPositions&workspace_tab_id={$workspace_tab->id}' + widget_positions)
	});
	
	$dashboard.find('td.column').sortable({
		'items': 'div.dashboard-widget',
		'handle': 'div.dashboard-widget-title',
		'distance': 20,
		'placeholder': 'ui-state-highlight',
		'forcePlaceholderSize': true,
		'tolerance': 'pointer',
		'cursorAt': { 'top':0, 'left':0 },
		'connectWith': 'table#dashboard{$workspace_tab->id} td.column',
		'update':function(e) {
			$('table#dashboard{$workspace_tab->id}').trigger('reorder');
		}
	});
</script>