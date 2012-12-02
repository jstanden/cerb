<div class="dashboard-widget-title" style="margin-bottom:5px;">
	{$widget->label}
	<div style="float:right;display:none;" class="toolbar">
		<a href="javascript:;" class="dashboard-widget-refresh" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
		<a href="javascript:;" class="dashboard-widget-edit" onclick="genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id={$widget->id}',null,false,'550');"><span class="cerb-sprite2 sprite-gear"></span></a>
	</div>
</div>

<input type="hidden" name="widget_pos[]" value="{$widget->id}">

{$widget_extension = Extension_WorkspaceWidget::get($widget->extension_id)}
{if $widget_extension instanceof Extension_WorkspaceWidget}
	{$widget_extension->render($widget)}
{/if}
