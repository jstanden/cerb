{if !$smtp_test && !empty($smtp_test_output)}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
			<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
			{$smtp_test_output}</p>
		</div>
	</div>
	<br>
{elseif $smtp_test===true}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
			<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
			Outgoing mail settings were tested successfully.</p>
		</div>
	</div>
	<br>
{/if}