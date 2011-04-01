<form action="{devblocks_url}{/devblocks_url}" method="post" name="myFeedsForm">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveRss">
<input type="hidden" name="id" value="">

<fieldset>
	<legend>RSS Notifications</legend>
	
	{foreach from=$feeds item=feed name=feeds}
		<div>
		<span class="cerb-sprite sprite-rss"></span> 
		<b>{$feed->title}</b>&nbsp; <a href="javascript:;" onclick="document.myFeedsForm.id.value='{$feed->id}';document.myFeedsForm.submit();">{$translate->_('common.remove')|lower}</a><br> 
		<a href="{devblocks_url full=true}c=rss&hash={$feed->hash}{/devblocks_url}" target="_blank">{devblocks_url full=true}c=rss&hash={$feed->hash}{/devblocks_url}</a><br>
		</div>
		<br>
	{foreachelse}
		You haven't created any feeds.  Click the RSS button in the header of any ticket list.<br>
	{/foreach}
</fieldset>

<!-- <button type="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>  -->
</form>

