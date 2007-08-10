<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayFnr" onsubmit="document.getElementById('displayFnrMatches').innerHTML='Searching knowledge...';genericAjaxPost('frmDisplayFnr','displayFnrMatches','c=display&a=doFnr');return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doFnr">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

<div class="block">

<div class="subtle2" style="margin:0px;">
<H2>Fetch &amp; Retrieve</H2>
<b>Search for:</b> (keywords)<br>
<input type="text" name="q" size="45" value="{$terms}" autocomplete="off">
<button type="submit">Search</button><br>
<br>

<b>Sources:</b> (default is all sources)<br>
<label><input type="checkbox" name="sources[]" value="jira" {if isset($sources.jira)}checked{/if}> Roadmap</label>
<label><input type="checkbox" name="sources[]" value="forums" {if isset($sources.forums)}checked{/if}> Forums</label>
<label><input type="checkbox" name="sources[]" value="wiki" {if isset($sources.wiki)}checked{/if}> Documentation</label>
<br>
</div>
<br>

<div id="displayFnrMatches"></div>

</div>

</form>