<div style="margin-left:10px;">

{$translate->_('portal.sc.cfg.feeds_info')}<br>
<br>
<table cellpadding="0" cellspacing="0" border="0">
<tr>
	<td>
		<b>{$translate->_('portal.sc.cfg.feed_display_title')}</b>
	</td>
	<td>
		<b>{$translate->_('portal.sc.cfg.feed_url')}</b>
	</td>
</tr>
{foreach from=$news_rss item=news_rss_url key=news_rss_title}
<tr>
	<td>
		<input type="text" name="news_rss_title[]" value="{$news_rss_title}" size="45">
	</td>
	<td>
		<input type="text" name="news_rss_url[]" value="{$news_rss_url}" size="45">
	</td>
</tr>
{/foreach}
{section name=news_rss start=0 loop=3}
<tr>
	<td>
		<input type="text" name="news_rss_title[]" value="" size="45">
	</td>
	<td>
		<input type="text" name="news_rss_url[]" value="" size="45">
	</td>
</tr>
{/section}
</table>
{$translate->_('portal.sc.cfg.save_more_feeds')}<br>
</div>
<br>
