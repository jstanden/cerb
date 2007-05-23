{include file="$path/feeds/menu.tpl.php"}

<div class="block">
<h2>My Feeds</h2>
<ul>
{foreach from=$feeds item=feed key=feed_id}
	<li style="margin:5px;">
		<a href="{devblocks_url}c=feeds&m=manage&id={$feed.f_code}{/devblocks_url}">{$feed.f_title}</a>
		&nbsp;
		<a href="{devblocks_url}c=rss&id={$feed.f_code}{/devblocks_url}"><img src="{devblocks_url}c=resource&p=cerberusweb.rss&f=images/feed-icon-16x16.gif{/devblocks_url}" align="top" border="0"></a>
	</li>
{/foreach}
</ul>
</div>
<br>
