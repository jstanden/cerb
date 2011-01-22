<div id="kb">
	
<div class="header"><h1>{$article->title}</h1></div>

<fieldset>
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
		
	<b>{'common.id'|devblocks_translate}:</b> {$article->id|string_format:"%06d"}<br>
	
	{if !empty($breadcrumbs)}
	<b>Filed under:</b>
	<div style="padding-left:10px;">
	{foreach from=$breadcrumbs item=trail}
		{foreach from=$trail item=step name=trail}
			<a href="{devblocks_url}c=kb&a=browse&id={$step|string_format:"%06d"}{/devblocks_url}">{$categories.$step->name}</a>{if !$smarty.foreach.trail.last} &raquo; {/if}
		{/foreach}
		<br>
	{/foreach}
	</div>
	{/if}
</fieldset>

<div style="padding:10px;">
	{if !empty($article->content)}
		{$article->getContent() nofilter}<br>
	{else}
		<i>[[ {$translate->_('portal.kb.public.no_content')} ]]</i><br>
	{/if}
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