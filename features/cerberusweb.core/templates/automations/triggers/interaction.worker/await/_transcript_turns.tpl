{* The turns of a transcript — bare markup that CerbUI.AgentTranscript enhances into chrome.
   Shared by TWO callers, which is the whole point of it being a partial:
     - `llm_transcript.tpl`, the full element render;
     - the `echoTurn` prompt action, which re-renders JUST this region (plus the reader's own pending turn)
       so a submitted message appears immediately instead of after the agent's reply.
   Rendering both from one source is what keeps the optimistic turn byte-identical to the real one — otherwise
   it visibly reflows the moment the real render lands.

   Expects the caller's assigns: $turns, $llm_session_user, $agent_provider_icon/$agent_provider_color,
   $transcript, $show_tokens, $filter_links, $tool_map, $tool_results, $tool_durations. *}
{foreach from=$turns item=turn}
    {if $turn.is_checkpoint}
    <div class="cerb-ui-agent-transcript--checkpoint"><span class="cerb-icons cerb-icon-archive"></span> Conversation summarized — earlier turns folded</div>
    {/if}
    {if 'user' == $turn.role}{$role_class = 'user'}{else}{$role_class = 'assistant'}{/if}

    {* TWO seqs, because they answer different questions and conflating them caused duplicate turns:

         data-seq       — the NEWEST message in the turn. The fork point "rewind from here" wants, and what
                          the Setup→Developers viewer means by the same attribute.
         data-turn-seq  — the turn's STABLE identity (the message that opened it). The live poll addresses
                          THIS, because an agent turn accretes messages through the tool loop: keyed on the
                          newest one, a growing turn stops matching the node on screen and gets appended
                          again as a second copy of itself. *}
    <div data-cerb-transcript-turn data-role="{$role_class}"{if $turn.seq_last} data-seq="{$turn.seq_last}"{/if}{if $turn.seq_first} data-turn-seq="{$turn.seq_first}"{/if}{if $turn.is_streaming} data-cerb-transcript-streaming{/if}{if $turn.is_pending} data-cerb-transcript-pending{/if}>
        {if 'user' == $turn.role}
            {if is_a($llm_session_user, 'Model_Worker')}
                <span data-cerb-transcript-avatar data-avatar="{$llm_session_user->getName()}" data-avatar-seed="worker:{$llm_session_user->id}" data-avatar-image="{$llm_session_user->getImageUrl()}"></span>
            {else}
                <span data-cerb-transcript-avatar data-avatar-icon="user" data-avatar-seed="user"></span>
            {/if}
        {else}
            <span data-cerb-transcript-avatar data-avatar-icon="{$agent_provider_icon}" data-avatar-seed="agent:{$transcript->uuid}"{if $agent_provider_color} data-avatar-color="{$agent_provider_color}"{/if}></span>
        {/if}

        <div data-cerb-transcript-sender>
            {if 'user' == $turn.role}
                {if is_a($llm_session_user, 'Model_Worker')}
                    <span class="cerb-ui-agent-transcript--sender-name">{$llm_session_user->getName()}</span>
                    {if $llm_session_user->title}<span class="cerb-u-text-muted">{$llm_session_user->title}</span>{/if}
                {else}
                    <span class="cerb-ui-agent-transcript--sender-name">User</span>
                {/if}
            {else}
                <span class="cerb-ui-agent-transcript--sender-name">Agent</span>
            {/if}
        </div>

        {if $turn.ts_label}
        <div data-cerb-transcript-meta title="{$turn.ts_first|devblocks_date}">{$turn.ts_label}</div>
        {/if}

        {* The aside is pinned right of the meta and always visible; the hover toolbar sits to its left.
           ONE aside per turn — agent-transcript.js relocates only the FIRST into the header. The truncation
           badge is NOT gated on `tokens:`: it explains why an answer stops mid-sentence, which a reader needs
           whether or not they asked to see token counts. *}
        {$show_tokens_chip = $show_tokens && 'user' != $turn.role && ($turn.usage.output > 0 || $turn.usage.prompt > 0)}
        {if $show_tokens_chip || $turn.is_truncated}
        <div data-cerb-transcript-aside>
            {if $turn.is_truncated}<span class="cerb-ui-pill cerb-ui-pill--orange" title="{if 'filter' == $turn.finish_reason}The provider withheld this response{else}The model hit its output limit — this response is cut off{/if}">{if 'filter' == $turn.finish_reason}Filtered{else}Truncated{/if}</span>{/if}
            {if $show_tokens_chip}
            <div class="cerb-ui-chip" title="Prompt {$turn.usage.prompt|number_format} tokens ({$turn.usage.cache_read|number_format} from cache) &middot; Output {$turn.usage.output|number_format}">
                <div class="cerb-ui-chip--head">Tokens</div>
                <div><div class="cerb-ui-chip--label">In</div><div class="cerb-ui-chip--value">{$turn.usage.prompt|number_format}</div></div>
                <div><div class="cerb-ui-chip--label">Out</div><div class="cerb-ui-chip--value">{$turn.usage.output|number_format}</div></div>
                {if $turn.usage.prompt > 0}<div><div class="cerb-ui-chip--label">Cached</div><div class="cerb-ui-chip--value">{$turn.usage.coverage}%</div></div>{/if}
            </div>
            {/if}
        </div>
        {/if}

        {if 'user' != $turn.role}
            {capture name="turn_markdown"}{foreach from=$turn.messages item=message}{foreach from=$message->getMessages() item=content}{if 'text' == $content.type}{$content.content}{/if}{/foreach}{/foreach}{/capture}
            {if $smarty.capture.turn_markdown|trim}
                <pre data-cerb-transcript-source>{$smarty.capture.turn_markdown}</pre>
            {/if}
        {/if}

        {foreach from=$turn.messages item=message}
            {capture name="msg_text"}{foreach from=$message->getMessages() item=content}{if 'text' == $content.type}{$content.content}{/if}{/foreach}{/capture}
            {if $smarty.capture.msg_text|trim}
                <div data-cerb-transcript-body>
                    {foreach from=$message->getMessages() item=content}
                        {if 'text' == $content.type}
                            <div class="commentBodyHtml">
                                {$message_html = DevblocksPlatform::parseMarkdown($content.content, true)}
                                {DevblocksPlatform::purifyHTML($message_html, true, true, [$filter_links]) nofilter}
                            </div>
                        {/if}
                    {/foreach}
                </div>
            {/if}

            {if $message->getImages()}
                <div data-cerb-transcript-images>
                    {foreach from=$message->getImages() item=image}
                        <img src="{$image.url}" alt="" loading="lazy">
                    {/foreach}
                </div>
            {/if}

            {* Thinking + tool calls interleave in author order; the component folds them into the agent
               turn's sub-thread. `thinking: summary|raw|hide` (passed to enhance() below) governs display. *}
            {foreach from=$message->getThinking() item=thinking}
                <div data-cerb-transcript-thinking>
                    <div class="commentBodyHtml">{$thinking_html = DevblocksPlatform::parseMarkdown($thinking, true)}{DevblocksPlatform::purifyHTML($thinking_html, true, true, [$filter_links]) nofilter}</div>
                </div>
            {/foreach}

            {if 'user' != $turn.role}
                {foreach from=$message->getToolCalls() item=tool_call}
                    {$tool_call_id = $tool_call->getId()}
                    {$tool_labels = $tool_call->getLabels($tool_map)}
                    {$tool_icon = $tool_call->getIcon($tool_map)}
                    <div data-cerb-transcript-tool data-tool-name="{$tool_call->getName()}" data-tool-id="{$tool_call_id}"{if $tool_icon} data-icon="{$tool_icon}"{/if}{if isset($tool_durations[$tool_call_id])} data-duration-ms="{$tool_durations[$tool_call_id]}"{/if} data-summary-active="{$tool_labels.active}" data-summary-past="{$tool_labels.summary}">
                        {if $tool_call->getParameters()}<textarea data-cerb-transcript-tool-params spellcheck="false">{$tool_call->getParameters()|json_encode:448}</textarea>{/if}
                        {if $tool_call_id && isset($tool_results[$tool_call_id])}<pre data-cerb-transcript-tool-result>{$tool_results[$tool_call_id]}</pre>{/if}
                    </div>
                {/foreach}
            {/if}
        {/foreach}

        {* Typing indicator, at the tail of the answer as it's being written — where a reader is actually
           looking, rather than only in a chip below the whole conversation. Emitted with
           `data-cerb-transcript-body` so the component treats it as an ordinary content node and places it
           last in the turn's flow; nothing in the JS knows it exists, and it simply stops being rendered
           once the turn finalizes. *}
        {if $turn.is_streaming}
            <div data-cerb-transcript-body data-cerb-transcript-cursor>
                <svg class="cerb-ui-spinner cerb-ui-spinner--dots" viewBox="0 0 120 40" style="width:28px;" aria-hidden="true">
                    <circle cx="20" cy="20" r="12"/><circle cx="60" cy="20" r="12"/><circle cx="100" cy="20" r="12"/>
                </svg>
            </div>
        {/if}
    </div>
{/foreach}
