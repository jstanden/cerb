<div class="bot-chat-message bot-chat-left">
	{if $bot}
	<div class="bot-chat-message-author">
		<img src="{devblocks_url}c=avatars&context=bot&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}" style="height:16px;width:16px;border-radius:5px;vertical-align:middle;">
		<b>{$bot->name}</b>:
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
