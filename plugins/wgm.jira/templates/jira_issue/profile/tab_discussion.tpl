{foreach from=$comments item=comment}
<div id="comment{$comment.jira_comment_id}">
	<div class="block" style="overflow:auto;">
		{*
		<span class="tag" style="color:rgb(71,133,210);">{'common.comment'|devblocks_translate|lower}</span>
		*}
		
		<b style="font-size:1.3em;">
			{$comment.jira_author}
		</b>
		
		{$extensions = DevblocksPlatform::getExtensions('wgm.jira.comment.badge', true)}
		{foreach from=$extensions item=extension}
			{$extension->render($comment)}
		{/foreach}
		<br>
		
		{if isset($comment.created)}<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$comment.created|devblocks_date} (<abbr title="{$comment.created|devblocks_date}">{$comment.created|devblocks_prettytime}</abbr>)<br>{/if}
		
		<pre class="emailbody" style="padding-top:10px;">{$comment.body|escape:'html'|devblocks_hyperlinks nofilter}</pre>
		<br clear="all">
	</div>
	<br>
</div>
{/foreach}