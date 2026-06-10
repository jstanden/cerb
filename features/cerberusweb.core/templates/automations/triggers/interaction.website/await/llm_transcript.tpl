{$element_id = uniqid('el')}
<div id="{$element_id}" class="cerb-interaction-popup--form-elements-llm-transcript">
	<h6>{$label}</h6>

	{foreach from=$transcript_messages item=message}
		{strip}
		{capture name=message_content}
			{foreach from=$message->getMessages() item=content}
				{if 'text' == $content.type}
				{$content.content nofilter}
				{/if}
			{/foreach}
		{/capture}
		{/strip}

		{if $smarty.capture.message_content}
			<div data-cerb-dom="transcript-message" data-cerb-transcript-role="{$message->getRole()}" data-cerb-message-uuid="{$message->getUuid()}">
				<pre data-cerb-dom="transcript-message-markdown" class="cerb-interaction--hidden">{$smarty.capture.message_content}</pre>
				<div class="emailBodyHtml">
				{$smarty.capture.message_content|devblocks_markdown_to_html nofilter}
				</div>

				{$tools = $message->getToolCalls()}
				{if $tools}
				{foreach from=$tools item=tool}
					<div data-cerb-tool="{$tool->getName()}">
						<span class="cerb-icons cerb-icon-hammer"></span>

						{$tool->getLabel($tool_labels)}
					</div>
				{/foreach}
				{/if}

				{if 'assistant' == $message->getRole() && !$message->getToolCalls()}
				<div data-cerb-dom="transcript-toolbar">
					<button type="button" data-cerb-button="copy-markdown" title="Copy to clipboard">
						<span class="cerb-icons cerb-icon-copy"></span>
					</button>

					{*
					<button type="button" data-cerb-button="rating-good" title="Give positive feedback">
						<span class="cerb-icons cerb-icon-thumbs-up"></span>
					</button>
					*}

					{*
					<button type="button" data-cerb-button="rating-bad" title="Give negative feedback">
						<span class="cerb-icons cerb-icon-thumbs-down"></span>
					</button>
					*}

					<span data-cerb-dom="transcript-disclaimer">
						(This answer is machine generated and may not be accurate.)
					</span>
				</div>
				{/if}
			</div>
		{/if}
	{/foreach}
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $prompt = document.querySelector('#{$element_id}');

	// Scroll down
	const $container = $prompt.closest('.cerb-interaction-popup--container');
	$container.scrollTop = $container.scrollHeight;

	$prompt.addEventListener('click', function(e) {
		e.stopPropagation();

		const $button = e.target.closest('button');

		if($button) {
			if('copy-markdown' === $button.getAttribute('data-cerb-button')) {
				const $message = $button.closest('[data-cerb-dom=transcript-message]');
				const $message_markdown = $message.querySelector('[data-cerb-dom=transcript-message-markdown]');

				if($message_markdown) {
					// [TODO] Give visual feedback of some kind
					const div = document.createElement('div');
					div.innerHTML = $message_markdown.innerHTML;
					navigator.clipboard.writeText(div.innerText);
					div.remove();
				}
			}
		}
	})
}
</script>