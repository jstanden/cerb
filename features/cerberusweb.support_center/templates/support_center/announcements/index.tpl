{if !empty($feeds)}
{foreach from=$feeds item=feed name=feeds}
<fieldset>
	<legend>
		{if !empty($feed.url)}
			<span class="glyphicons glyphicons-wifi-alt" style="color:rgb(249,154,56);"></span> <a href="{$feed.url}" target="_blank">{$feed.title}</a>
		{else}
			{$feed.title}
		{/if}
	</legend>
	
	<div style="margin:10px 0px 0px 5px;">
	{foreach from=$feed.items item=item name=items}
		{if $smarty.foreach.items.iteration > 5}
		{else}
			{$item_guid = ''|cat:$item.title|cat:'_'|cat:$item.link|md5}
			
			<span class="glyphicons glyphicons-file" style="color:rgb(100,100,100);"></span>
			<a href="{$item.link}" target="_blank" style="font-weight:bold;text-decoration:none;">{$item.title}</a> 
			<br>
			{if !empty($item.content)}
			<div style="margin:5px 0px 5px 25px;">
				{$item.content|strip_tags|truncate:255:'...':true nofilter}
			</div>
			{/if}
		{/if}
	{/foreach}
	</div>
</fieldset>
{/foreach}
{/if} {*feeds*}
