{if !$success}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<strong>Error!</strong>
			<span style="float:right;"><a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></span>
			<br>
<pre class="emailbody" dir="auto">
{$output|escape nofilter}
</pre>
		</div>
	</div>
{else}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<strong>Success!</strong>
			<span style="float:right;"><a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></span>
			<br>
<pre class="emailbody" dir="auto">
{$output|escape nofilter}
</pre>
		</div>
	</div>
{/if}
