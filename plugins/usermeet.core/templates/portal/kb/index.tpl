{include file="$tpl_path/portal/kb/header.tpl"}

<table style="text-align: left; width: 100%;" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="padding: 5px; vertical-align: top;">
				{*
				{assign var=cloudpath value=$cloud->getPath()}
				{assign var=taginfo value=$cloud->getPathTagInfo()}
				
				{if !empty($cloudpath)}
					<div id="kbTagCloudNav">
					<a href="{devblocks_url}c=rss&a=tags&tags={$tags_prefix|escape:"url"}{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS Feed" align="top" border="0"></a>
					<b>Location:</b>
					{foreach from=$cloudpath item=part name=parts}
						<a href="{devblocks_url}c=browse&path={$part->name|escape:"url"}{/devblocks_url}">{$part->name}</a>{if !$smarty.foreach.parts.last} + {/if}
					{/foreach}
					</div>
				{elseif !empty($taginfo)}
					Choose a topic:
				{else}
					Nothing to see here yet!
				{/if}
				
				{assign var=tags value=$taginfo.tags}
				{assign var=weights value=$taginfo.weights}
				{assign var=font_weights value=$taginfo.font_weights}
				{if !empty($tags)}
				<div id="kbTagCloud">
					{foreach from=$tags item=tag key=tag_id name=tags}
						<span style="font-size:{$font_weights.$tag_id}px;"><a href="{devblocks_url}c=browse&path={if !empty($tags_prefix)}{$tags_prefix|escape:"url"}+{/if}{$tag->name|escape:"url"}{/devblocks_url}">{$tag->name}</a>{if !$smarty.foreach.tags.last},{/if}</span> 
					{/foreach}
				</div>
				{else}
					<br>
				{/if}
				*}
				
				<div style="padding-bottom:5px;">
				<a href="{devblocks_url}c=browse{/devblocks_url}">Top</a> ::
				{if !empty($breadcrumb)}
					{foreach from=$breadcrumb item=bread_id}
						<a href="{devblocks_url}c=browse&id={$bread_id|string_format:"%06d"}{/devblocks_url}">{$categories.$bread_id->name}</a> :
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
						<a href="{devblocks_url}c=browse&id={$cat_id|string_format:"%06d"}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
					
						{if !empty($tree.$cat_id)}
							&nbsp; &nbsp; 
							{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
								 <a href="{devblocks_url}c=browse&id={$child_id|string_format:"%06d"}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
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
				        <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document.gif{/devblocks_url}" alt="Search" align="top"> <a href="{devblocks_url}c=article&id={$article_id|string_format:"%06d"}{/devblocks_url}">{$article.kb_title}</a><br>
			        {/foreach}
				</div>
				{/if}
			</td>
			<td style="width: 200px; white-space: nowrap; vertical-align: top;">
				<div style="border-left: 1px solid rgb(200, 200, 200); border-right: 1px solid rgb(200, 200, 180); padding: 10px; margin-right: 5px; background-color: rgb(245, 245, 255);">
					<form action="{devblocks_url}{/devblocks_url}" method="post">
					<input type="hidden" name="a" value="doSearch">
					<b>Search</b><br>
					<input name="query" value="" size="16" style="width: 150px;" type="text"><button type="submit">&raquo;</button>
					</form>
					<br>
					<div style="margin-bottom:5px;">
						<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS Feed" align="top" border="0">
						<a href="{devblocks_url full=true}c=rss&a=recent_changes{/devblocks_url}">Recent Changes</a>
					</div> 
					<div style="margin-bottom:5px;">
						<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS Feed" align="top" border="0">
						<a href="{devblocks_url full=true}c=rss&a=most_popular{/devblocks_url}">Most Popular Articles</a>
					</div>
				</div>
			</td>
		</tr>
	</tbody>
</table>

{include file="$tpl_path/portal/kb/footer.tpl"}