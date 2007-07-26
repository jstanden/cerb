
<div style="position: relative; width:100%; height: 30;">
	<span style="position: absolute; left: 0;"><h1 style="display:inline;">RSS</h1>
		{include file="file:$path/tickets/menu.tpl.php"}
	</span>
	<span style="position: absolute; right: 0;">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>Sender</option>
			<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>Ticket ID</option>
			<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>Subject</option>
			<option value="content"{if $quick_search_type eq 'content'}selected{/if}>Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</span>
</div>

<div class="block">
<H2>My Ticket Feeds</H2>
<form action="{devblocks_url}{/devblocks_url}" method="post" name="myFeedsForm">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="removeRss">
<input type="hidden" name="id" value="">
<br>

{foreach from=$feeds item=feed name=feeds}
	<div class="subtle">
	<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/feed-icon-16x16.gif{/devblocks_url}" border="0" align="top"> 
	<h2 style="display:inline;">{$feed->title}</h2>&nbsp; <a href="javascript:;" onclick="document.myFeedsForm.id.value='{$feed->id}';document.myFeedsForm.submit();">{$translate->_('common.remove')|lower}</a><br> 
	<b>URL:</b> <a href="{devblocks_url full=true}c=rss&hash={$feed->hash}{/devblocks_url}" target="_blank">{devblocks_url full=true}c=rss&hash={$feed->hash}{/devblocks_url}</a><br>
	</div>
	<br>
{foreachelse}
	You haven't created any feeds.  Click the RSS button in the header of any ticket list.
{/foreach}
</form>
</div>
