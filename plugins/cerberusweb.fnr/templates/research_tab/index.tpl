{if !empty($fnr_topics)}
	<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmResearchFnr" onsubmit="document.getElementById('researchFnrMatches').innerHTML='<br>{'fnr.ui.research.searching'|devblocks_translate|escape:'quotes'}...';genericAjaxPost('frmResearchFnr','researchFnrMatches','c=fnr.ajax&a=doFnr');return false;">
	<input type="hidden" name="c" value="fnr.ajax">
	
	<table cellpadding="0" cellspacing="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap"><b>{'fnr.ui.research.keywords'|devblocks_translate|capitalize}: </b></td>
		<td width="100%">
			<input type="text" name="q" size="24" value="{$q}" autocomplete="off">
			<button type="submit">{'common.search_go'|devblocks_translate|lower}</button>
		</td>
	</tr>
	</table>
	
	<div class="block" id="researchFnrSources" style="display:block;padding:5px;">
	{foreach from=$fnr_topics item=topic key=topic_id name=topics}
	{assign var=resources value=$topic->getResources()}
	{if !empty($topic) && !empty($resources)}
		<h2 style="display:inline;margin:0px;">{$topic->name}:</h2> <a href="javascript:;" onclick="checkAll('fnrTopic{$topic_id}')">{'common.all'|devblocks_translate|lower}</a><br>
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
	
	<div id="researchFnrMatches"></div>
	
{else}
	{'fnr.ui.research.not_configured'|devblocks_translate}<br>

{/if}