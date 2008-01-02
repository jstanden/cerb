<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/book_blue_view.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Fetch &amp; Retrieve</h1></td>
	</tr>
</table>

<div style="height:400px;overflow:auto;">
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmDisplayFnr" name="frmDisplayFnr" onsubmit="toggleDiv('displayFnrSources','none');document.getElementById('displayFnrMatches').innerHTML='<br>Searching knowledge...';genericAjaxPost('frmDisplayFnr','displayFnrMatches','c=display&a=doFnr');return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doFnr">
<input type="hidden" name="view_id" value="{$view_id}">

{if !empty($topics)}
<table cellpadding="0" cellspacing="0" width="98%">
<tr>
	<td width="0%" nowrap="nowrap"><b>Keywords: </b></td>
	<td width="100%">
		<input type="text" name="q" size="24" value="{$terms}" autocomplete="off">
		<button type="submit">go!</button>
		&nbsp;<a href="javascript:;" onclick="toggleDiv('displayFnrSources');" style="font-size:90%;">show/hide sources</a>
	</td>
</tr>
</table>

<div class="block" id="displayFnrSources" style="display:block;padding:5px;">
{foreach from=$topics item=topic key=topic_id name=topics}
{assign var=resources value=$topic->getResources()}
{if !empty($topic) && !empty($resources)}
	<h2 style="display:inline;margin:0px;">{$topic->name}:</h2> <a href="javascript:;" onclick="checkAll('fnrTopic{$topic_id}')">all</a><br>
	<div id="fnrTopic{$topic_id}">
	{foreach from=$resources item=resource key=resource_id}
		<label><input type="checkbox" name="sources[]" value="{$resource_id}" {if isset($sources.$resource_id)}checked{/if}> {$resource->name}</label>
	{/foreach}
	</div>
{/if}
{if !$smarty.foreach.topics.last}<br>{/if}
{/foreach}
</div>

</form>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmDisplayFnr" name="frmDisplayFnr" onsubmit="document.getElementById('displayFnrMatches').innerHTML='Searching knowledge...';genericAjaxPost('frmDisplayFnr','displayFnrMatches','c=display&a=doFnr');return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doFnrLinks">
<input type="hidden" name="view_id" value="{$view_id}">
<div id="displayFnrMatches"></div>
</form>
</div>

{else}{* end !empty($topics)*}

No topics or resources have been configured.<br>
<br>
{if $active_worker->is_superuser}You're an administrator, why don't you <a href="{devblocks_url}c=config&a=fnr{/devblocks_url}">configure Fetch &amp; Retrieve?</a>{/if}

{/if}

<!-- 
<input type="button" value="{$translate->_('common.save_changes')}" onclick="ajax.s('{$view_id}');">
<br>
 -->
