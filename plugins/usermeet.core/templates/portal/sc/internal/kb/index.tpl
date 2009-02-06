<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">{$translate->_('common.knowledgebase')|capitalize}</h1>
</div>

<div style="padding-bottom:5px;">
<a href="{devblocks_url}c=kb&a=browse{/devblocks_url}">{$translate->_('portal.kb.public.top')}</a> ::
{if !empty($breadcrumb)}
	{foreach from=$breadcrumb item=bread_id}
		<a href="{devblocks_url}c=kb&a=browse&id={$bread_id|string_format:"%06d"}{/devblocks_url}">{$categories.$bread_id->name}</a> :
	{/foreach} 
{/if}
</div>
<br>

{if !empty($tree.$root_id)}
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
	<td width="50%" valign="top">
	{foreach from=$tree.$root_id item=count key=cat_id name=kbcats}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top">
		<a href="{devblocks_url}c=kb&a=browse&id={$cat_id|string_format:"%06d"}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
	
		{if !empty($tree.$cat_id)}
			&nbsp; &nbsp; 
			{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
				 <a href="{devblocks_url}c=kb&a=browse&id={$child_id|string_format:"%06d"}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
			{/foreach}
			<br>
		{/if}
		<br>
		
		{if $smarty.foreach.kbcats.iteration==$mid}
			</td>
			<td width="50%" valign="top">
		{/if}
	{/foreach}
	</td>
	</tr>
</table>
{/if}

{if !empty($articles)}
<h2 style="margin:0px;">Articles</h2>
<div id="kbTagCloudArticles">
      {foreach from=$articles item=article key=article_id}
        <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document.gif{/devblocks_url}" alt="Search" align="top"> 
        <a href="{devblocks_url}c=kb&a=article&id={$article_id|string_format:"%06d"}{/devblocks_url}">{$article.kb_title}</a><br>
      {/foreach}
</div>
{/if}
