<div class="dashboard-widget-title" style="margin-bottom:5px;">
	{$widget->label}
	<div style="float:right;display:none;" class="toolbar">
		<a href="javascript:;" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
		<a href="javascript:;" onclick="genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id={$widget->id}',null,false,'500');"><span class="cerb-sprite2 sprite-gear"></span></a>
	</div>
</div>

<div class="updated">Last updated: {$widget->updated_at|devblocks_date}</div>

{$widget_extension = Extension_WorkspaceWidget::get($widget->extension_id)}
{if $widget_extension instanceof Extension_WorkspaceWidget}
	{$widget_extension->render($widget)}
{/if}

<script type="text/javascript">
$('#widget{$widget->id}').unbind('hover').hover(
	function(e) {
		$(this).find('div.dashboard-widget-title > div.toolbar, canvas.overlay').show();
	},
	function(e) {
		$(this).find('div.dashboard-widget-title > div.toolbar, canvas.overlay').hide();
	}
);
</script>