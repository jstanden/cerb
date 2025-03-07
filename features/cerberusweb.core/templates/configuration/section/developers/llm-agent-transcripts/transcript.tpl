<h1>Transcript: {$llm_session->uuid}</h1>

<div class="cerb-code-editor-toolbar">
    {if !$llm_session->is_read}
    <button class="button" data-cerb-button="mark-read"><span class="glyphicons glyphicons-circle-ok"></span> {{'home.my_notifications.button.mark_read'|devblocks_translate|capitalize}}</button>
    {/if}
    <button class="button" data-cerb-button="delete"><span class="glyphicons glyphicons-circle-remove"></span> {{'common.delete'|devblocks_translate|capitalize}}</button>
</div>

{foreach from=$messages item=message}
<div style="margin-bottom:1em;padding:0.5em;{if 'assistant' == $message->getRole()}background-color:var(--cerb-color-background-contrast-240);{/if}">
    <div style="margin-bottom:0.5em;">
        <h1>{$message->getRole()}</h1>
    </div>

    {foreach from=$message->getMessages() item=content}
    <div class="commentBodyHtml">
        {if 'text' == $content['type']}
            {$content = DevblocksPlatform::parseMarkdown($content['content'], true)}
            {DevblocksPlatform::purifyHTML($content, true, true, [$filter_links]) nofilter}
        {/if}
    </div>
    {/foreach}

    {if 'assistant' == $message->getRole()}
        {foreach from=$message->getToolCalls() item=tool_call}
        <div>
            <details open>
                <summary>
                    <b>(Tool) {$tool_call->getName()}</b>
                </summary>
                <pre>{$tool_call->getParameters()|json_encode:128}</pre>
            </details>
        </div>
        {/foreach}
    {elseif 'tool' == $message->getRole()}
        {foreach from=$message->getToolResults() item=tool_result key=tool_id}
            <details>
                <summary>
                    <b>(Tool Result) {$tool_id}</b>
                </summary>
                <pre>{$tool_result}</pre>
            </details>
        {/foreach}
    {/if}
</div>
{/foreach}