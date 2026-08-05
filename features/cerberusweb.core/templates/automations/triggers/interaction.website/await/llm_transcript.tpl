{$element_id = uniqid('el')}
<div id="{$element_id}" class="cerb-interaction-popup--form-elements-llm-transcript">
	<h6>{$label}</h6>

	{* Tracked across the loop: the disclaimer renders once below the transcript, not per agent turn. *}
	{$has_agent_turn = false}

	<div class="cerb-ui-chat">
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
			{if 'user' == $message->getRole()}{$role_icon = 'user'}{$role_label = 'User'}{$role_class = 'user'}
			{else}{$role_icon = 'bot'}{$role_label = 'Agent'}{$role_class = 'assistant'}{$has_agent_turn = true}{/if}

			<div class="cerb-ui-chat--turn cerb-ui-chat--{$role_class}" data-cerb-dom="transcript-message" data-cerb-transcript-role="{$message->getRole()}" data-cerb-message-uuid="{$message->getUuid()}">
				<div class="cerb-ui-chat--role">
					<span class="cerb-icons cerb-icon-{$role_icon}"></span> {$role_label}

					{if 'assistant' == $message->getRole() && !$message->getToolCalls()}
					<div data-cerb-dom="transcript-toolbar">
						<button type="button" data-cerb-button="copy-markdown" title="Copy to clipboard">
							<span class="cerb-icons cerb-icon-copy"></span>
						</button>
					</div>
					{/if}
				</div>

				<pre data-cerb-dom="transcript-message-markdown" class="cerb-interaction--hidden">{$smarty.capture.message_content}</pre>

				<div class="cerb-ui-chat--body">
					<div class="emailBodyHtml">
					{$smarty.capture.message_content|devblocks_markdown_to_html nofilter}
					</div>
				</div>

				{$tools = $message->getToolCalls()}
				{if $tools}
				{foreach from=$tools item=tool}
					{$tool_labels = $tool->getLabels($tool_map)}
					{* Always hammer: the portal ships a hand-copied subset of the icon set, so a tool's `icon:`
					   would resolve to a mask this stylesheet doesn't define (an empty square). *}
					<div data-cerb-tool="{$tool->getName()}">
						<span class="cerb-icons cerb-icon-hammer"></span>

						{* This chip renders directly (no CerbUI component), so the fallback is ours to supply. *}
						{$tool_labels.summary|default:'Worked'}
					</div>
				{/foreach}
				{/if}

			</div>
		{/if}
	{/foreach}
	</div>

	{if $has_agent_turn}
	<div data-cerb-dom="transcript-disclaimer">
		(This conversation contains machine-generated answers and may have inaccuracies.)
	</div>
	{/if}
</div>

{if !($is_automation_simulated|default:false)}
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
{/if}