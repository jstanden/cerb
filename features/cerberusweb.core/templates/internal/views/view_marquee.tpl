{$view_marquee = C4_AbstractView::getMarquee($view->id, true)}
{if !empty($view_marquee)}
<div class="ui-widget cerb-view-marquee">
	<div class="ui-state-highlight ui-corner-all" style="margin: 0 0 .5em 0; padding: 0 .7em;"> 
		<div style="float:right;margin-top:5px;margin-right:5px;">
			(<a href="javascript:;" onclick="$(this).closest('div.cerb-view-marquee').html('');">dismiss</a>)
		</div>
		
		<p>
			<span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
			{$view_marquee nofilter}
		</p>
	</div>
</div>
{/if}
