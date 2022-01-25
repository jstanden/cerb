{if !$success}
	<div class="error-box">
		<span style="float:right;cursor:pointer;font-size:1.5em;"><span class="glyphicons glyphicons-circle-remove" onclick="$(this).closest('.error-box').remove();"></span></span>
		<h1 style="font-size:1.5em;font-weight:bold;">Error!</h1>
		<p>
			<pre class="emailbody" dir="auto">{$output|escape nofilter}</pre>
		</p>
	</div>
{else}
	<div class="help-box">
		<span style="float:right;cursor:pointer;font-size:1.5em;"><span class="glyphicons glyphicons-circle-remove" onclick="$(this).closest('.help-box').remove();"></span></span>
		<h1 style="font-size:1.5em;font-weight:bold;">Success!</h1>
		<p>
			<pre class="emailbody" dir="auto">{$output|escape nofilter}</pre>
		</p>
	</div>
{/if}
