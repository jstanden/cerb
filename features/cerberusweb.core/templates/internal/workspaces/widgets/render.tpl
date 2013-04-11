{$widget_extension = Extension_WorkspaceWidget::get($widget->extension_id)}

<div class="dashboard-widget-title" style="margin-bottom:5px;">
	{$widget->label}
	<div style="float:right;display:none;" class="toolbar">
		<a href="javascript:;" class="dashboard-widget-menu"><span class="cerb-sprite2 sprite-gear"></span><span class="cerb-sprite sprite-arrow-down-black"></span></a>
		
		<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;margin-left:-180px;">
			<li><a href="javascript:;" class="dashboard-widget-edit" onclick="genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id={$widget->id}',null,false,'550');">Configure</a></li>
			<li><a href="javascript:;" class="dashboard-widget-refresh" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}');">Refresh</a></li>
			{if $widget_extension instanceof ICerbWorkspaceWidget_ExportData}
			<li><a href="javascript:;" class="dashboard-widget-export-data" onclick="genericAjaxPopup('widget_export','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetExportPopup&widget_id={$widget->id}',null,false,'650');">Export Data</a></li>
			{/if}
		</ul>
	</div>
</div>

<script type="text/javascript">
$('#widget{$widget->id}')
	.find('div.dashboard-widget-title > div.toolbar > a.dashboard-widget-menu')
	.click(function() {
		$(this).next('ul.cerb-popupmenu').toggle();
	})
	.next('ul.cerb-popupmenu')
	.hover(
		function(e) { }, 
		function(e) { $(this).hide(); }
	)
	.find('> li')
	.click(function(e) {
		$(this).closest('ul.cerb-popupmenu').hide();
		
		e.stopPropagation();
		if(!$(e.target).is('li'))
			return;
		
		$(this).find('a').trigger('click');
	});
</script>

<input type="hidden" name="widget_pos[]" value="{$widget->id}">

{if $widget_extension instanceof Extension_WorkspaceWidget}
	{$widget_extension->render($widget)}
{/if}
