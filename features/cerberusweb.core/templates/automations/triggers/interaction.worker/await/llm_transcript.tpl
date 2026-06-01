{$element_id = uniqid('response_')}
<div class="cerb-form-builder-prompt cerb-form-builder-response-llm-transcript" id="{$element_id}">
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
                <pre data-cerb-dom="transcript-message-markdown" class="cerb-hidden">{$smarty.capture.message_content}</pre>
                <div class="emailBodyHtml">
                    {$smarty.capture.message_content|devblocks_markdown_to_html nofilter}
                </div>

                {$tools = $message->getToolCalls()}
                {if $tools}
                    {foreach from=$tools item=tool}
                        <div class="emailBodyHtml" data-cerb-tool="{$tool->getName()}">
                            <span class="cerb-icons cerb-icon-hammer"></span>&nbsp;
                            {$tool->getLabel($tool_labels)}
                        </div>
                    {/foreach}
                {/if}

                {if 'assistant' == $message->getRole() && !$message->getToolCalls()}
                    <div data-cerb-dom="transcript-toolbar">
                        <button type="button" data-cerb-button="copy-markdown" title="Copy to clipboard" tabindex="-1">
                            <span class="cerb-icons cerb-icon-copy"></span>
                        </button>

                        {*
                        <button type="button" data-cerb-button="rating-good" data-cerb-rating="1" title="Give positive feedback">
                            <span class="cerb-icons cerb-icon-thumbs-up"></span>
                        </button>
                        *}

                        {*
                        <button type="button" data-cerb-button="rating-bad" data-cerb-rating="2" title="Give negative feedback">
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $prompt = $('#{$element_id}');

    // Scroll down
    $prompt.scrollTop($prompt.get(0).scrollHeight);

    $prompt.on('click', 'button', function(e) {
        e.stopPropagation();

        const $button = $(this);

        if($button) {
            if('copy-markdown' === $button.attr('data-cerb-button')) {
                const $message = $button.closest('[data-cerb-dom=transcript-message]');
                const $message_markdown = $message.find('[data-cerb-dom=transcript-message-markdown]');

                if ($message_markdown) {
                    const $div = $('<div/>');
                   $div.html($message_markdown.html());
                    navigator.clipboard.writeText($div.text());
                    $div.remove();
                    Devblocks.createAlert('Copied to clipboard!');
                }
            }
        }
    })
});
</script>