<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">{$translate->_('common.knowledgebase')|capitalize}</h1>
</div>

<h2>{$article->title}</h2>

<div style="padding-bottom:5px;font-size:90%;">
{if !empty($breadcrumbs)}
	{foreach from=$breadcrumbs item=bread_stack}
		<a href="{devblocks_url}c=kb&a=browse{/devblocks_url}">{$translate->_('portal.kb.public.top')}</a> ::
		{foreach from=$bread_stack item=bread_id}
			<a href="{devblocks_url}c=kb&a=browse&id={$bread_id|string_format:"%06d"}{/devblocks_url}">{$categories.$bread_id->name}</a> :
		{/foreach}
		<br> 
	{/foreach} 
{/if}
</div>

<br>

{if !empty($article->content)}
	{if !$article->format}{$article->content|escape|nl2br}{else}{$article->content}{/if}<br>
{else}
	<i>[[ {$translate->_('portal.kb.public.no_content')} ]]</i><br>
{/if}
<br>
