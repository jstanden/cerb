{if !empty($error)}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
			<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
			{$error}</p>
		</div>
	</div>
{elseif !empty($output)}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
			<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
			{$output}</p>
		</div>
	</div>
{else}
	<br>
{/if}
