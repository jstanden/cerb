{if !$success}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<strong>Error!</strong>
			<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();">close</a>)</span>
			<br>
			<br>
			{$output|escape|nl2br nofilter}
		</div>
	</div>
{else}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<strong>Success!</strong>
			<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();">close</a>)</span>
			<br>
			<br>
			{$output|escape|nl2br nofilter}
		</div>
	</div>
{/if}
