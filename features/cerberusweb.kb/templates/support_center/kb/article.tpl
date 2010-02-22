<div id="kb">
	
<div class="header"><h1>{$article->title|escape}</h1></div>
<b>Article ID:</b> {$article->id|string_format:"%06d"}

<div style="padding:10px;">
	{if !empty($article->content)}
		{if !$article->format}{$article->content|escape|nl2br}{else}{$article->content}{/if}<br>
	{else}
		<i>[[ {$translate->_('portal.kb.public.no_content')} ]]</i><br>
	{/if}
	<br>
</div>

</div>