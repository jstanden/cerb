<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}" data-typing-indicator="true">
	<div class="cerb-bot-chat-message cerb-bot-chat-left">
		{if !in_array($format, ['markdown','html'])}
		<div class="cerb-bot-chat-message-bubble">
			{$message|escape|nl2br nofilter}
		</div>
		{else}
		<div class="cerb-bot-chat-message-bubble">
			{$message nofilter}
		</div>
		{/if}
		{*
		<div class="cerb-bot-chat-message-time">
			{time()|devblocks_date:'h:ia'}
		</div>
		*}
	</div>
	
	<br clear="all">
</div>