{$width_units = $widget->width_units|default:1}
<div class="cerb-profile-widget" data-widget-id="{$widget->id}" style="flex:{$width_units} {$width_units} {$width_units * 0.25 * 100}%;min-width:345px;">
	<div style="padding:0px 5px 10px 5px;">
		<div class="cerb-profile-widget--header" style="border:2px solid rgb(200,200,200);box-shadow:0px 0px 2px rgb(200,200,200);background-color:rgb(235,235,235);padding:5px 0 5px 10px;margin:0 0 10px 0;border-radius:5px;position:relative;">
			<b style="font-size:1.4em;color:rgb(0,0,0);">
				{if $active_worker->is_superuser}
				<span class="glyphicons glyphicons-menu-hamburger" style="vertical-align:top;cursor:move;color:rgb(150,150,150);font-size:1.2em;"></span>
				{/if}
				<a href="javascript:;" class="cerb-profile-widget--link no-underline">{$widget->name}</a>
			</b>
			<ul class="cerb-profile-widget--menu cerb-popupmenu cerb-float" style="display:none;">
				{if $active_worker->is_superuser}
				<li class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_PROFILE_WIDGET}" data-context-id="{$widget->id}" data-edit="true">
					<a href="javascript:;">{'common.edit'|devblocks_translate|capitalize}</a>
				</li>
				{/if}
				<li class="cerb-profile-widget-menu--refresh">
					<a href="javascript:;">{'common.refresh'|devblocks_translate|capitalize}</a>
				</li>
			</ul>
		</div>
		<div id="profileWidget{$widget->id}" class="cerb-profile-widget--content">
			{* We only have full content on create/edit *}
			{if $extension}
				{$extension->render($widget, $context, $context_id, [])}
			{/if}
		</div>
	</div>
</div>