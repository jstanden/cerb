<div class="bot-chat-message bot-chat-left">
	{if false && $actor}
	<div class="bot-chat-message-author">
		<b>{$actor}</b>:
	</div>
	{/if}
	{if in_array($format, ['markdown','html'])}
	<div class="bot-chat-message-bubble">
		{$message nofilter}
	</div>
	{else}
	<div class="bot-chat-message-bubble">
		{$message|escape|nl2br nofilter}
	</div>
	{/if}
	{*
	<div class="bot-chat-message-time">
		{time()|devblocks_date:'h:ia'}
	</div>
	*}
</div>

<br clear="all">
