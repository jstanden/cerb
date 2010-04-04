{if !$success}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<p>
				<strong>Error!</strong>
				<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();">close</a>)</span>
				<br>
				<br>
				{$output|nl2br}
			</p>
		</div>
	</div>
{else}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<p>
				<strong>Success!</strong>
				<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();">close</a>)</span>
				<br>
				<br>
				{$output|nl2br}
			</p>
		</div>
	</div>
{/if}
