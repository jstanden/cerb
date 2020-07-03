{$is_widget_writeable = Context_WorkspaceWidget::isWriteableByActor($widget, $active_worker)}
{$width_units = $widget->width_units|default:1}
{$widget_extension = $widget->getExtension()}
<div class="cerb-workspace-widget" data-widget-id="{$widget->id}" data-widget-name="{$widget->name}" style="flex:{$width_units} {$width_units} {$width_units * 0.25 * 100}%;">
	<div>
		<div class="cerb-workspace-widget--header {if $is_widget_writeable}cerb-draggable{/if}">
			<b>
				<a href="javascript:;" class="cerb-workspace-widget--link no-underline">
					{$widget->label}<!--
					--><span class="glyphicons glyphicons-chevron-down"></span>
				</a>
				{if $is_widget_writeable}
				<span class="glyphicons glyphicons-menu-hamburger" style="vertical-align:baseline;color:rgb(200,200,200);float:right;display:none;"></span>
				{/if}
			</b>
		</div>
		<div>
			<ul class="cerb-workspace-widget--menu cerb-popupmenu cerb-float" style="display:none;margin-top:-12px;">
				{if $is_widget_writeable}
				<li class="cerb-workspace-widget-menu--edit" data-context="{CerberusContexts::CONTEXT_WORKSPACE_WIDGET}" data-context-id="{$widget->id}" data-edit="true" data-width="75%">
					<a href="javascript:;">{'common.edit'|devblocks_translate|capitalize}</a>
				</li>
				<li class="cerb-workspace-widget-menu--export-widget">
					<a href="javascript:;">{'common.export.widget'|devblocks_translate|capitalize}</a>
				</li>
				{/if}
				
				{if $widget_extension && $widget_extension instanceof ICerbWorkspaceWidget_ExportData}
				<li class="cerb-workspace-widget-menu--export-data">
					<a href="javascript:;">{'common.export.data'|devblocks_translate|capitalize}</a>
				</li>
				{/if}
				
				<li class="cerb-workspace-widget-menu--refresh">
					<a href="javascript:;">{'common.refresh'|devblocks_translate|capitalize}</a>
				</li>
			</ul>
		</div>
		<div id="workspaceWidget{$widget->id}" class="cerb-workspace-widget--content">
			{if $full}
				{$widget_extension->render($widget, $context, $context_id, [])}
			{/if}
		</div>
	</div>
</div>
