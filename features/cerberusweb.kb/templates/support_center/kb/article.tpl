<div id="kb">
	
<div class="header"><h1>{$article->title}</h1></div>
<b>Article ID:</b> {$article->id|string_format:"%06d"}

<div style="padding:10px;">
	{if !empty($article->content)}
		{$article->getContent() nofilter}<br>
	{else}
		<i>[[ {$translate->_('portal.kb.public.no_content')} ]]</i><br>
	{/if}
</div>

{if !empty($breadcrumbs)}
<b>Filed under:</b>
<div style="padding-left:10px;">
{foreach from=$breadcrumbs item=trail}
	{foreach from=$trail item=step name=trail}
		<a href="{devblocks_url}c=kb&a=browse&id={$step|string_format:"%06d"}{/devblocks_url}">{$categories.$step->name}</a>{if !$smarty.foreach.trail.last} &raquo; {/if}
	{/foreach}
	<br>
{/foreach}
</div>
{/if}

</div>