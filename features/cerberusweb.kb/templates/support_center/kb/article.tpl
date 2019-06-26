<div id="kb">
	
<h1 class="title">{$article->title}</h1>

<div class="content">
	{$article_content = $article->getContent()}
	{if $article_content}
		{$article_content nofilter}<br>
	{else}
		<i>[[ {'portal.kb.public.no_content'|devblocks_translate} ]]</i><br>
	{/if}
</div>

<fieldset>
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
		
	<b>{'common.id'|devblocks_translate}:</b> {$article->id|string_format:"%06d"}
	 &nbsp; 
	<b>{'kb_article.views'|devblocks_translate}:</b> {$article->views}
	 &nbsp; 
	<b>{'kb_article.updated'|devblocks_translate}:</b> {$article->updated|devblocks_prettytime}
	
	{if !empty($breadcrumbs)}
	<div style="margin-top:5px;">
	<b>Filed under:</b>
		<div style="padding-left:10px;">
		{foreach from=$breadcrumbs item=trail}
			{foreach from=$trail item=step name=trail}
				<a href="{devblocks_url}c=kb&a=browse&id={$step}-{$categories.$step->name|devblocks_permalink}{/devblocks_url}">{$categories.$step->name}</a>{if !$smarty.foreach.trail.last} &raquo; {/if}
			{/foreach}
			<br>
		{/foreach}
		</div>
	</div>
	{/if}
</fieldset>

{if !empty($attachments)}
	<fieldset>
		<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
		
		<ul style="margin-top:0px;">
			{foreach from=$attachments item=attachment}
			<li>
				<a href="{devblocks_url}c=ajax&a=downloadFile&id={$attachment->storage_sha1hash}&name={$attachment->name|escape:'url'}{/devblocks_url}" target="_blank" rel="noopener">{$attachment->name}</a>
				({$attachment->storage_size|devblocks_prettybytes}
				 - 
				{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if})
			</li>
			{/foreach}
		</ul>
	</fieldset>
{/if}

</div>