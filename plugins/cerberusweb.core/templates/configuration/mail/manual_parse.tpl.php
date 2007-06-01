<div class="block">
<h2>Manual Parse Message Source</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="doManualParse">

<b>Enter the raw e-mail source below:</b><br>
<textarea rows=8 cols=80 name="source"></textarea><br>
<br>

<b>Next step:</b><br>
<select name="next">
	<option value="display" selected>Display ticket
	<option value="parse">Parse another message source
</select><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Parse</button>
</form>

</div>