{if empty($columns)}
	{if $page->isWriteableByWorker($active_worker)}
	<form action="#" onsubmit="return false;">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	<div class="help-box" style="padding:5px;border:0;">
		<h1 style="margin-bottom:5px;text-align:left;">Let's put this dashboard to good use</h1>
		
		<p>
			You now have a new dashboard tab. You can click the 
			<button type="button" onclick="$btn=$('#frmWorkspacePage{$page->id} button.config-page.split-left'); $(this).effect('transfer', { to:$btn, className:'effects-transfer' }, 500, function() { $btn.effect('pulsate', {  times: 3 }, function(e) { $(this).click(); } ); } );"><span class="glyphicons glyphicons-cogwheel"></span></button> 
			button in the top right and select <b>Edit Tab</b> from the menu to configure how many columns of widgets this tab can display. Click the <button type="button" onclick="var $btn = $('#frmAddWidget{$workspace_tab->id} BUTTON.add_widget'); $(this).effect('transfer', { to:$btn, className:'effects-transfer' }, 500, function() { $btn.effect('pulsate', {  times: 3 }, function(e) { $(this).click(); } ); } );"><span class="glyphicons glyphicons-circle-plus"></span> Add Widget</button> button to add new widgets to the dashboard.
		</p>
	</div>
	</form>
	{else}
	<div class="help-box" style="padding:5px;border:0;">
		<h1 style="margin-bottom:5px;text-align:left;">This dashboard is empty</h1>
		
		<p>
			This dashboard has no content, and you don't have permission to modify it.  You'll have to wait until someone else adds something.
		</p>
	</div>
	{/if}
{/if}

{if $page->isWriteableByWorker($active_worker)}
<form id="frmAddWidget{$workspace_tab->id}" action="#">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
<button type="button" class="add_widget"><span class="glyphicons glyphicons-circle-plus"></span> Add Widget</button>
</form>
{/if}

{$column_count = DevblocksPlatform::intClamp($workspace_tab->params.num_columns, 1, 4)}
{$column_ids = range(0, $column_count-1)}

<table cellpadding="0" cellspacing="0" border="0" width="100%" id="dashboard{$workspace_tab->id}">
	<tr>
		{$column_width_remaining = 100}
		
		{foreach from=$column_ids item=column_id name=columns}
		{if $smarty.foreach.columns.last}{$column_width=$column_width_remaining}{else}{$column_width=floor(100/$column_count)}{$column_width_remaining = $column_width_remaining - $column_width}{/if}
		<td width="{$column_width}%" valign="top" class="column">
			{foreach from=$columns.$column_id item=widget key=widget_id name=widgets}
			{capture name=widget_content}{$widget_is_preloaded = Extension_WorkspaceWidget::renderWidgetFromCache($widget, false)}{/capture}
			
			<div class="dashboard-widget{if $widget_is_preloaded} widget-preloaded{/if}" id="widget{$widget_id}">
				{if $widget_is_preloaded}
					{$smarty.capture.widget_content nofilter}
					
				{else}
					<div class="dashboard-widget-title" style="margin-bottom:5px;">
						{$widget->label}
					</div>
					<div style="text-align:center;">
						<span class="cerb-ajax-spinner"></span>
					</div>
				{/if}
			</div>
			{/foreach}
		</td>
		{/foreach}
	</tr>
</table>

<script type="text/javascript">
	// Page title
	
	document.title = "{$workspace_tab->name|escape:'javascript' nofilter} - {$page->name|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";

	// Widget loader
	
	$.widgetAjaxLoader = function() {
		this.widget_ids = [];
		this.is_running = false;
	};
	
	$.widgetAjaxLoader.prototype = {
		add: function(widget_id) {
			this.widget_ids.push(widget_id);
			this.next();
		},
		
		next: function() {
			if(this.widget_ids.length == 0)
				return;
			
			if(this.is_running == true)
				return;
			
			var widget_id = this.widget_ids.shift();
			var loader = this;
			var $div = $('#widget' + widget_id);
			
			var cb = function() {
				loader.is_running = false;
				loader.next();
			}

			this.is_running = true;
			genericAjaxGet('widget' + widget_id,'c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id=' + widget_id, cb);
		},
	};
	
	var $widgetAjaxLoader = new $.widgetAjaxLoader();
	
	{foreach from=$columns item=widgets}
		{foreach from=$widgets item=widget key=widget_id}
			if(!$('#widget{$widget_id}').is('.widget-preloaded'))
				$widgetAjaxLoader.add({$widget_id});
		{/foreach}
	{/foreach}
	
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
			$new_widget = $('<div class="dashboard-widget"></div>').attr('id','widget' + widget_id);
			
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
	
	$dashboard.on('reorder', function(e) {
		$dashboard = $(this);
		
		var $tr = $dashboard.find('TBODY > TR');
		var widget_positions = '';
		
		{foreach from=$column_ids item=column_id}
		var $col_widgets = $tr.find('> TD:nth({$column_id})').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		widget_positions += '&column[]=' + $col_widgets;
		{/foreach}
		
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
		'stop':function(e) {
			$('table#dashboard{$workspace_tab->id}').trigger('reorder');
		}
	});
</script>