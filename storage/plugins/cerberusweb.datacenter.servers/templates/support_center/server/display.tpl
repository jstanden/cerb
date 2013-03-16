<div id="server">
	<h1 class="title">{'portal.sc.public.server.server'|devblocks_translate}: {$server->name}</h1>
	
	<div class="content" style="margin-top: 10px">
		<table cellpadding="1" cellspacing="0" border="0">
			<tr>
				<td colspan="2">
					<b>{'portal.sc.public.server.created'|devblocks_translate|capitalize}</b>
					<abbr title="{$entry->created|devblocks_date}">{$entry->created|devblocks_prettytime}</abbr>
				</td>
			</tr>				
			<tr>
				<td>
					<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/traffic_light_{$entry->state}.png{/devblocks_url}" />
				</td>
				<td>
					{if !empty($entry->journal)}
						{$entry->journal}
					{else}
						{'portal.sc.public.server.empty'|devblocks_translate}
					{/if}
				</td>
			</tr>
		</table>
	</div>
	
	{if isset($attachments_map) && is_array($attachments_map)}
		{$links = $attachments_map.links}
		{$files = $attachments_map.attachments}
		{if !empty($links) && !empty($files)}
		<fieldset>
			<legend>{'common.attachments'|devblocks_translate}</legend>

			<ul style="margin-top:0px;">
				{foreach from=$links item=link}
				{$attachment = $files.{$link->attachment_id}}
				<li>
					<a href="{devblocks_url}c=ajax&a=downloadFile&guid={$link->guid}&name={$attachment->display_name}{/devblocks_url}" target="_blank">{$attachment->display_name}</a>
					( 
						{$attachment->storage_size|devblocks_prettybytes}
						- 
						{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{$translate->_('display.convo.unknown_format')|capitalize}{/if}
					 )
				</li>
				{/foreach}
			</ul>
		</fieldset>
		{/if}
	{/if}
</div>