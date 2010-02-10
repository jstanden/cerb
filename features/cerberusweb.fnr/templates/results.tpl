{if !empty($feeds)}
{foreach from=$feeds item=feed name=feeds}
	<br>
	<div class="block">
	<table cellspacing="0" cellpadding="0" border="0" width="98%">
		<tr>
			<td width="100%"><h2 style="margin:0px;">{$feed.name|escape}</h2></td>
			<td width="0%" nowrap="nowrap" align="right" style="color:rgb(0,150,0);">&nbsp;<b>{$feed.topic_name}</b></td>
		</tr>
	</table>
	
	{foreach from=$feed.feed.items item=item name=items}
		{assign var=item_guid value=''|cat:$item.title|cat:'_'|cat:$item.link|md5}
		
			<span class="cerb-sprite sprite-document"></span> <a href="javascript:;" onclick="toggleDiv('{$item_guid}_preview');" style="font-weight:bold;">{$item.title|escape}</a> 
			<br>

			<div class="subtle" style="margin-bottom:5px;margin-left:10px;padding:5px;display:none;" id="{$item_guid}_preview">
				{$item.content|escape:"script"}
				<br>
				<b>URL:</b> <a href="{$item.link}" style="color:rgb(50,180,50);font-weight:bold;" target="_blank">{$item.link}</a>
			</div>
	{/foreach}
	</div>
{/foreach}
{/if} {*feeds*}
<br>