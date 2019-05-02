{$width_units = $widget->width_units|default:1}
<div id="portalWidget{$widget->id}" class="cerb-portal-widget cerb-portal-widget--width-{$width_units*25}">
	<div class="cerb-portal-widget--title">
		{$widget->name}
	</div>
	<div class="cerb-portal-widget--content">
		{$widget->render($dict)}
	</div>
</div>
