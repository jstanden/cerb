{$view_marquees = C4_AbstractView::marqueeFlush($view->id)}
{if $view_marquees}
	{foreach from=$view_marquees item=view_marquee}
	<div class="ui-widget cerb-view-marquee">
		<div class="ui-state-highlight ui-corner-all" style="margin: 0 0 .5em 0; padding: 0 .7em;">
			<div style="float:right;margin-top:5px;margin-right:5px;">
				<a href="javascript:;" onclick="$(this).closest('div.cerb-view-marquee').html('');"><span class="glyphicons glyphicons-circle-remove"></span></a>
			</div>

			<p>
				{$view_marquee nofilter}
			</p>
		</div>
	</div>
	{/foreach}
{/if}
