{$width_units = $widget->width_units|default:1}
<div class="cerb-workspace-widget" data-widget-id="{$widget->id}" style="flex:{$width_units} {$width_units} {$width_units * 0.25 * 100}%;min-width:345px;overflow-x:hidden;">
	<div style="padding:0px 5px 10px 5px;">
		<div class="cerb-workspace-widget--header" style="border:2px solid rgb(200,200,200);box-shadow:0px 0px 2px rgb(200,200,200);background-color:rgb(235,235,235);padding:5px 0 5px 10px;margin:0 0 10px 0;border-radius:5px;position:relative;">
			<b style="font-size:1.4em;color:rgb(0,0,0);">
				{if $active_worker->is_superuser}
				<span class="glyphicons glyphicons-menu-hamburger" style="vertical-align:top;cursor:move;color:rgb(150,150,150);font-size:1.2em;"></span>
				{/if}
				<a href="javascript:;" class="cerb-workspace-widget--link no-underline">{$widget->label}</a>
			</b>
		</div>
		<div>
			<ul class="cerb-workspace-widget--menu cerb-popupmenu cerb-float" style="display:none;margin-top:-12px;">
				{if $active_worker->is_superuser}
				<li class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKSPACE_WIDGET}" data-context-id="{$widget->id}" data-edit="true" data-width="75%">
					<a href="javascript:;">{'common.edit'|devblocks_translate|capitalize}</a>
				</li>
				{/if}
				<li class="cerb-workspace-widget-menu--refresh">
					<a href="javascript:;">{'common.refresh'|devblocks_translate|capitalize}</a>
				</li>
			</ul>
		</div>
		<div id="workspaceWidget{$widget->id}" class="cerb-workspace-widget--content">
			{* We only have full content on create/edit *}
			{if $extension}
				{$extension->render($widget, $context, $context_id, [])}
			{/if}
		</div>
	</div>
</div>