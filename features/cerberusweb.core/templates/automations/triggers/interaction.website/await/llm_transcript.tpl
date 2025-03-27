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
					<div><b>(Tool: {$tool->getName()})</b></div>
				{/foreach}
				{/if}

				{if 'assistant' == $message->getRole() && !$message->getToolCalls()}
				<div data-cerb-dom="transcript-toolbar">
					<button type="button" data-cerb-button="copy-markdown" title="Copy to clipboard">
						<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="32px"
							 height="32px" viewBox="0 0 48 48" xml:space="preserve">
							<g id="glyphicons">
								<g id="copy">
									<path d="M20,24.5V21l4,4h-3.5C20.225,25,20,24.775,20,24.5z M19.5,26H24v9.5c0,0.275-0.225,0.5-0.5,0.5h-11
										c-0.275,0-0.5-0.225-0.5-0.5v-14c0-0.275,0.225-0.5,0.5-0.5H19v4.5C19,25.775,19.225,26,19.5,26z M14,24h4v-1h-4V24z M14,26h4v-1
										h-4V26z M22,33h-8v1h8V33z M22,31h-8v1h8V31z M22,29h-8v1h8V29z M22,27h-8v1h8V27z M26.021,17h5.957
										C32.529,17,33,16.567,33,16.015v-0.982C33,14.481,32.529,14,31.979,14H31v-0.912C31,12.536,30.55,12,30,12h-2
										c-0.55,0-1,0.536-1,1.088V14h-0.979C25.471,14,25,14.481,25,15.033v0.982C25,16.567,25.471,17,26.021,17z M26.8,28.2H25V31h1.8
										V28.2z M35.5,15H34v2.019C34,17.57,33.55,18,33,18h-8c-0.55,0-1-0.43-1-0.981V15h-1.5c-0.275,0-0.5,0.237-0.5,0.513V21l2.8,2.8h2
										v-3.79l5.992,5.991L27.792,31H35.5c0.275,0,0.5-0.151,0.5-0.427v-15.06C36,15.237,35.775,15,35.5,15z M30.611,26.001L28,23.389V25
										h-3v2h3v1.612L30.611,26.001z"/>
								</g>
							</g>
						</svg>
					</button>

					{*
					<button type="button" data-cerb-button="rating-good" title="Give positive feedback">
						<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="32px"
							 height="32px" viewBox="0 0 48 48" xml:space="preserve">
							<g id="glyphicons">
								<g id="thumbs-up">
									<polygon points="33,25 34,24 34,22 32,22 26,31 29,31 		"/>
									<path d="M26,14v3l-1,5h9v2l-1,1l-4,6h-5l-2-1h-2v-8l3-4l2-4H26 M26,12h-1c-0.757,0-1.45,0.428-1.789,1.106l-1.919,3.839L18.4,20.8
										C18.14,21.146,18,21.567,18,22v8c0,1.104,0.895,2,2,2h1.528l1.578,0.789C23.383,32.928,23.689,33,24,33h5
										c0.669,0,1.293-0.334,1.664-0.891l3.89-5.835l0.86-0.86C35.789,25.039,36,24.53,36,24v-2c0-1.104-0.896-2-2-2h-6.561
										c0,0,0.561-2.868,0.561-3v-3C28,12.896,27.104,12,26,12L26,12z M16,20h-4v13h4c0.55,0,1-0.45,1-1V21C17,20.45,16.55,20,16,20z"/>
								</g>
							</g>
						</svg>
					</button>
					*}

					{*
					<button type="button" data-cerb-button="rating-bad" title="Give negative feedback">
						<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="32px"
							 height="32px" viewBox="0 0 48 48" xml:space="preserve">
							<g id="glyphicons">
								<g id="thumbs-down">
									<polygon points="29,16 27,16 27,18 32,25 34,25 34,23 33,22 		"/>
									<path d="M29,16l4,6l1,1v2h-9l1,5v3h-1l-2-4l-3-4v-8h2l2-1H29 M29,14h-5c-0.311,0-0.617,0.072-0.895,0.211L21.528,15H20
									c-1.105,0-2,0.896-2,2v8c0,0.433,0.14,0.854,0.4,1.2l2.892,3.856l1.919,3.839C23.55,34.572,24.243,35,25,35h1c1.104,0,2-0.896,2-2
									v-3c0-0.132-0.013-0.264-0.039-0.393L27.439,27H34c1.104,0,2-0.896,2-2v-2c0-0.53-0.211-1.039-0.586-1.414l-0.86-0.86l-3.89-5.835
									C30.293,14.334,29.669,14,29,14L29,14z M16,15h-4v13h4c0.55,0,1-0.45,1-1V16C17,15.45,16.55,15,16,15z"/>
								</g>
							</g>
						</svg>
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