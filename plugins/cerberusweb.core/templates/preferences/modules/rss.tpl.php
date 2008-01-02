<h2>RSS Notifications</h2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post" name="myFeedsForm">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveRss">
<input type="hidden" name="id" value="">

{foreach from=$feeds item=feed name=feeds}
	<div class="subtle">
	<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/feed-icon-16x16.gif{/devblocks_url}" border="0" align="top"> 
	<h2 style="display:inline;">{$feed->title}</h2>&nbsp; <a href="javascript:;" onclick="document.myFeedsForm.id.value='{$feed->id}';document.myFeedsForm.submit();">{$translate->_('common.remove')|lower}</a><br> 
	<b>URL:</b> <a href="{devblocks_url full=true}c=rss&hash={$feed->hash}{/devblocks_url}" target="_blank">{devblocks_url full=true}c=rss&hash={$feed->hash}{/devblocks_url}</a><br>
	</div>
	<br>
{foreachelse}
	You haven't created any feeds.  Click the RSS button in the header of any ticket list.<br>
{/foreach}

<!-- <button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>  -->
</form>

