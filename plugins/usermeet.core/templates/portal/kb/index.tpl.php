{include file="$tpl_path/portal/kb/header.tpl.php"}

<table style="text-align: left; width: 100%;" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="padding: 5px; vertical-align: top;">
				{assign var=cloudpath value=$cloud->getPath()}
				{if !empty($cloudpath)}
					<div id="kbTagCloudNav">
					<a href="{devblocks_url}c=rss&a=tags&tags={$tags_prefix|escape:"url"}{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS Feed" align="top" border="0"></a>
					<b>Location:</b>
					{foreach from=$cloudpath item=part name=parts}
						<a href="{devblocks_url}c=browse&path={$part->name|escape:"url"}{/devblocks_url}">{$part->name}</a>{if !$smarty.foreach.parts.last} + {/if}
					{/foreach}
					</div>
				{else}
					Choose a topic:
				{/if}
				
				{assign var=taginfo value=$cloud->getPathTagInfo()}
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
				
				{if !empty($articles)}
				<h2 style="margin:0px;">Articles</h2>
				<div id="kbTagCloudArticles">
			      	{foreach from=$articles item=article key=article_id}
				        <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document.gif{/devblocks_url}" alt="Search" align="top"> <a href="{devblocks_url}c=article&id={$article_id|string_format:"%06d"}{/devblocks_url}">{$article->title}</a><br>
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
					<b>Other Resources</b><br>
					<a href="#">Contact Us</a><br>
					<!-- 
					<br>
					<b>Article Tools</b><br>
					<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/printer.gif{/devblocks_url}" alt="Printer" align="top"> <a href="#">Print</a><br>
					 -->
				</div>
			</td>
		</tr>
	</tbody>
</table>

{include file="$tpl_path/portal/kb/footer.tpl.php"}