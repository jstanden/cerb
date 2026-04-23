<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" data-typing-indicator="true">
	<div class="bot-chat-message bot-chat-left">
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
</div>