Results for '<b>{$query}</b>':<br>

{if !empty($results)}
	<div style="margin-left:20px;">
	{foreach from=$results item=result key=article_id}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="top"> 
		<a href="javascript:;" class="ticketLink" style="font-size:12px;" onclick="genericAjaxPanel('c=kb.ajax&a=showArticlePeekPanel&id={$result.kb_id}',null,false,'700px');"><b>{$result.kb_title}</b></a><br>
	{/foreach}
	</div>
{else}
	No matching articles found.<br>
{/if}

{*<button type="button" onclick="toggleDiv('kbSearch{$message->id}','none');">Close</button>*}