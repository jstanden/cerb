{$view_marquees = C4_AbstractView::marqueeFlush($view->id)}
{if $view_marquees}
	{foreach from=$view_marquees item=view_marquee}
	<div class="cerb-alert cerb-alert-rounded cerb-alert-margins-tb cerb-view-marquee">
		<div class="cerb-alert-close">
			<span class="glyphicons glyphicons-circle-remove" onclick="$(this).closest('div.cerb-alert').remove();"></span>
		</div>

		{$view_marquee nofilter}
	</div>
	{/foreach}
{/if}
