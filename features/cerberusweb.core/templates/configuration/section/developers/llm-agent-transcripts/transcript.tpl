<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
    <div class="cerb-ui-header--title" style="font-family:ui-monospace,Menlo,Consolas,monospace;">{$llm_session->uuid}</div>
    <div class="cerb-ui-header--right">
        <div class="cerb-ui-toolbar-strip">
            {if !$llm_session->is_read}
            <button type="button" class="cerb-ui-toolbar-button" data-cerb-button="mark-read"><span class="cerb-icons cerb-icon-circle-ok"></span> {{'common.archive'|devblocks_translate|capitalize}}</button>
            {/if}
            <button type="button" class="cerb-ui-toolbar-button" data-cerb-button="delete"><span class="cerb-icons cerb-icon-trash"></span> {{'common.delete'|devblocks_translate|capitalize}}</button>
            <button type="button" class="cerb-ui-toolbar-button" data-cerb-button="fork"><span class="cerb-icons cerb-icon-hierarchy"></span> Fork</button>
            {if $llm_session->isPrimed()}
            <button type="button" class="cerb-ui-toolbar-button" data-cerb-button="compact"><span class="cerb-icons cerb-icon-archive"></span> Compact</button>
            {/if}
            <button type="button" class="cerb-ui-toolbar-button" data-cerb-button="permalink" data-cerb-permalink="{devblocks_url full=true}c=config&a=llm_agent_transcripts&uuid={$llm_session->uuid}{/devblocks_url}"><span class="cerb-icons cerb-icon-link"></span> {{'common.permalink'|devblocks_translate|capitalize}}</button>
        </div>
    </div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:1em;margin-bottom:1em;">
    <div class="cerb-ui-chip" style="flex:0 0 auto;" title="The session's LLM configuration.">
        <div class="cerb-ui-chip--head">LLM</div>
        <div><div class="cerb-ui-chip--label">Provider</div><div class="cerb-ui-chip--value"><span class="cerb-icons cerb-icon-{DevblocksPlatform::services()->llm()->getProviderIcon($llm_session->provider)}"></span> {$llm_session->provider}</div></div>
        {if $llm_session->isPrimed() && $llm_session->getModel()}<div><div class="cerb-ui-chip--label">Model</div><div class="cerb-ui-chip--value">{$llm_session->getModel()}</div></div>{/if}
        {if $llm_session_auth}<div><div class="cerb-ui-chip--label">Authentication</div><div class="cerb-ui-chip--value"><a data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$llm_session_auth->id}" data-cerb-peek>{$llm_session_auth->name}</a></div></div>{/if}
    </div>
    {if $llm_session->user_type || $llm_session->user_ip}
    <div class="cerb-ui-chip" style="flex:0 0 auto;" title="The session's initiating user.">
        <div class="cerb-ui-chip--head">User</div>
        {if is_a($llm_session_user, 'Model_Worker')}<div><div class="cerb-ui-chip--label">Worker</div><div class="cerb-ui-chip--value"><a data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$llm_session_user->id}" data-cerb-peek>{$llm_session_user->getName()}</a></div></div>{else}<div><div class="cerb-ui-chip--label">Portal Visitor</div><div class="cerb-ui-chip--value">Anonymous</div></div>{/if}
        {if $llm_session->user_ip}<div><div class="cerb-ui-chip--label">IP</div><div class="cerb-ui-chip--value">{$llm_session->user_ip}</div></div>{/if}
    </div>
    {/if}
    {if $llm_session->token_usage > 0 || $session_usage.prompt > 0 || $session_usage.output > 0}
    <div class="cerb-ui-chip" style="flex:0 0 auto;" title="Context Used = current conversation size (last turn's real prompt + response). Input / Cached Reads / Cached Writes / Output are cumulative over the session — every turn re-sends the full history, so cached reads dominate. Input is full-price fresh tokens; Cached Reads are ~0.1x; Cached Writes are 1.25x.">
        <div class="cerb-ui-chip--head">Tokens</div>
        {if $llm_session->token_usage > 0}<div><div class="cerb-ui-chip--label">Context Used</div><div class="cerb-ui-chip--value">{$llm_session->token_usage|number_format}</div></div>{/if}
        <div><div class="cerb-ui-chip--label">Input</div><div class="cerb-ui-chip--value">{$session_usage.input|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Cached Reads</div><div class="cerb-ui-chip--value">{$session_usage.cache_read|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Cached Writes</div><div class="cerb-ui-chip--value">{$session_usage.cache_write|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Output</div><div class="cerb-ui-chip--value">{$session_usage.output|number_format}</div></div>
    </div>
    {/if}
    {if $message_counts.user > 0 || $message_counts.agent > 0 || $message_counts.tools > 0}
    <div class="cerb-ui-chip" style="flex:0 0 auto;" title="Message counts for this transcript.">
        <div class="cerb-ui-chip--head">Messages</div>
        <div><div class="cerb-ui-chip--label">User</div><div class="cerb-ui-chip--value">{$message_counts.user|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Agent</div><div class="cerb-ui-chip--value">{$message_counts.agent|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Tool Calls</div><div class="cerb-ui-chip--value">{$message_counts.tools|number_format}</div></div>
    </div>
    {/if}
    {if $llm_session_automation}
    <div class="cerb-ui-chip" style="flex:0 0 auto;" title="The automation + node that owns this session.">
        <div class="cerb-ui-chip--head">Automation</div>
        <div><div class="cerb-ui-chip--label">Name</div><div class="cerb-ui-chip--value"><a data-context="{CerberusContexts::CONTEXT_AUTOMATION}" data-context-id="{$llm_session_automation->id}" data-cerb-peek>{$llm_session_automation->name}</a></div></div>
        <div><div class="cerb-ui-chip--label">Node</div><div class="cerb-ui-chip--value" style="word-break:break-all;">{$llm_session->automation_node}</div></div>
    </div>
    {/if}
</div>

{* The agent's mark comes from the session's stamped `display:` block, falling back to the provider's own —
   so a model served over an OpenAI-compatible endpoint (z.ai, Qwen, llama.cpp) reads as ITS vendor. The
   Provider chip above deliberately keeps the provider's icon: that field names the provider, factually. *}
{$agent_provider_icon = $llm_session->getDisplayIcon()}
{$agent_provider_color = $llm_session->getDisplayIconColor()}

{* The session's tool inventory — a sibling panel, not part of the conversation (it's config, not a turn). *}
{if $tool_map}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
    <div class="cerb-ui-header cerb-ui-header--tight">
        <div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-hammer"></span> Tools ({$tool_map|count})</div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.6em;">
        {foreach from=$tool_map key=tool_name item=tool}
        <div class="cerb-ui-chip" style="flex:0 0 auto;">
            <div class="cerb-ui-chip--head">{$tool_name}</div>
            {if isset($tool_automations[$tool_name])}{$tool_automation = $tool_automations[$tool_name]}
                <div><div class="cerb-ui-chip--label">Automation</div><div class="cerb-ui-chip--value"><a data-context="{CerberusContexts::CONTEXT_AUTOMATION}" data-context-id="{$tool_automation->id}" data-cerb-peek><span class="cerb-icons cerb-icon-zap"></span> {$tool_automation->name}</a></div></div>
            {elseif $tool.uri}
                <div><div class="cerb-ui-chip--label">Tool</div><div class="cerb-ui-chip--value"><code class="cerb-u-text-muted">{$tool.uri}</code></div></div>
            {else}
                <div><div class="cerb-ui-chip--label">Tool</div><div class="cerb-ui-chip--value cerb-u-text-muted">inline</div></div>
            {/if}
        </div>
        {/foreach}
    </div>
</div>
{/if}

{* Bare markup — CerbUI.AgentTranscript.enhance() (in index.tpl) builds the chrome. *}
<div class="cerb-ui-agent-transcript" data-cerb-agent-transcript>
    {if $llm_session->system_prompt}
    {* Collapsible (starts folded) — the prompt is long and sits above the conversation. Body is Markdown; source is the raw text for Text view. *}
    <div data-cerb-transcript-turn data-role="system" data-cerb-transcript-collapsible="collapsed">
        <span data-cerb-transcript-avatar data-avatar-icon="list" data-avatar-seed="system:{$llm_session->uuid}"></span>
        <div data-cerb-transcript-sender>
            <span class="cerb-ui-agent-transcript--sender-name">System prompt</span> <span class="cerb-u-text-muted">Instructions</span>
        </div>
        <div data-cerb-transcript-body>
            <div class="commentBodyHtml">
                {$system_prompt_html = DevblocksPlatform::parseMarkdown($llm_session->system_prompt, true)}
                {DevblocksPlatform::purifyHTML($system_prompt_html, true, true, [$filter_links]) nofilter}
            </div>
        </div>
        <pre data-cerb-transcript-source>{$llm_session->system_prompt}</pre>
    </div>
    {/if}

    {foreach from=$turns item=turn}
        {if $turn.is_checkpoint}
        <div class="cerb-ui-agent-transcript--checkpoint"><span class="cerb-icons cerb-icon-archive"></span> Conversation summarized — earlier turns folded</div>
        {/if}
        {if 'user' == $turn.role}{$role_class = 'user'}{else}{$role_class = 'assistant'}{/if}

        {* nofilter: keep the capture RAW — the single {$smarty.capture.turn_markdown} output below auto-escapes
           it once. Without this, the variable filter escapes here AND on output → visible &amp;amp; entities. *}
        {capture name="turn_markdown"}{foreach from=$turn.messages item=message}{foreach from=$message->getMessages() item=content}{if 'text' == $content['type']}{$content['content'] nofilter}{/if}{/foreach}{/foreach}{/capture}

        <div data-cerb-transcript-turn data-role="{$role_class}" data-seq="{$turn.seq_last}">
            {if 'user' == $turn.role}
                {if is_a($llm_session_user, 'Model_Worker')}
                    <span data-cerb-transcript-avatar data-avatar="{$llm_session_user->getName()}" data-avatar-seed="worker:{$llm_session_user->id}" data-avatar-image="{$llm_session_user->getImageUrl()}"></span>
                {else}
                    <span data-cerb-transcript-avatar data-avatar-icon="user" data-avatar-seed="user"></span>
                {/if}
            {else}
                <span data-cerb-transcript-avatar data-avatar-icon="{$agent_provider_icon}" data-avatar-seed="agent:{$llm_session->uuid}"{if $agent_provider_color} data-avatar-color="{$agent_provider_color}"{/if}></span>
            {/if}

            <div data-cerb-transcript-sender>
                {if 'user' == $turn.role}
                    {if is_a($llm_session_user, 'Model_Worker')}
                        <a class="cerb-ui-agent-transcript--sender-name cerb-u-underline-hover" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$llm_session_user->id}" data-cerb-peek>{$llm_session_user->getName()}</a>
                        {if $llm_session_user->title}<span class="cerb-u-text-muted">{$llm_session_user->title}</span>{/if}
                    {else}
                        <span class="cerb-ui-agent-transcript--sender-name">User</span> <span class="cerb-u-text-muted">Portal visitor</span>
                    {/if}
                {else}
                    <span class="cerb-ui-agent-transcript--sender-name">Agent</span> <span class="cerb-ui-pill">{$llm_session->provider}{if $llm_session->isPrimed() && $llm_session->getModel()} &middot; {$llm_session->getModel()}{/if}</span>
                {/if}
            </div>

            <div data-cerb-transcript-meta title="{$turn.ts_first|devblocks_date}">{$turn.ts_first|devblocks_prettytime}</div>

            {* ONE aside per turn — agent-transcript.js relocates only the FIRST into the header. *}
            {$show_tokens_chip = 'user' != $turn.role && ($turn.usage.output > 0 || $turn.usage.prompt > 0)}
            {if $show_tokens_chip || $turn.is_truncated}
            <div data-cerb-transcript-aside>
                {if $turn.is_truncated}<span class="cerb-ui-pill cerb-ui-pill--orange" title="{if 'filter' == $turn.finish_reason}The provider withheld this response (finish_reason: filter){else}The model hit its output limit — this response is cut off (finish_reason: length){/if}">{if 'filter' == $turn.finish_reason}Filtered{else}Truncated{/if}</span>{/if}
                {if $show_tokens_chip}
                <div class="cerb-ui-chip" style="flex:0 0 auto;" title="Prompt {$turn.usage.prompt|number_format} tokens ({$turn.usage.cache_read|number_format} from cache) &middot; Output {$turn.usage.output|number_format}">
                    <div class="cerb-ui-chip--head">Tokens</div>
                    <div><div class="cerb-ui-chip--label">In</div><div class="cerb-ui-chip--value">{$turn.usage.prompt|number_format}</div></div>
                    <div><div class="cerb-ui-chip--label">Out</div><div class="cerb-ui-chip--value">{$turn.usage.output|number_format}</div></div>
                    {if $turn.usage.prompt > 0}<div><div class="cerb-ui-chip--label">Cached</div><div class="cerb-ui-chip--value">{$turn.usage.coverage}%</div></div>{/if}
                </div>
                {/if}
            </div>
            {/if}

            {* A CerbUI.Toolbar source list; the component prepends its built-in copy item. *}
            {if 'user' == $turn.role}
            <ul class="cerb-ui-toolbar" data-cerb-transcript-turn-toolbar>
                <li data-value="fork-from-here" data-icon="hierarchy" title="Fork from here"></li>
            </ul>
            {/if}

            {if $smarty.capture.turn_markdown|trim}
                <pre data-cerb-transcript-source>{$smarty.capture.turn_markdown}</pre>
            {/if}

            {foreach from=$turn.messages item=message}
                {capture name="msg_text"}{foreach from=$message->getMessages() item=content}{if 'text' == $content['type']}{$content['content']}{/if}{/foreach}{/capture}
                {if $smarty.capture.msg_text|trim}
                    <div data-cerb-transcript-body>
                        {foreach from=$message->getMessages() item=content}
                            {if 'text' == $content['type']}
                                <div class="commentBodyHtml">
                                    {$message_html = DevblocksPlatform::parseMarkdown($content['content'], true)}
                                    {DevblocksPlatform::purifyHTML($message_html, true, true, [$filter_links]) nofilter}
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                {/if}

                {if $message->getImages()}
                    <div data-cerb-transcript-images>
                        {foreach from=$message->getImages() item=image}
                            <img src="{$image['url']}" alt="" loading="lazy">
                        {/foreach}
                    </div>
                {/if}

                {* Thinking + tool calls: authored in order, collected by the component into the sub-thread. *}
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
                            {if isset($tool_automations[$tool_call->getName()])}{$tool_automation = $tool_automations[$tool_call->getName()]}
                            <a data-cerb-transcript-tool-label data-context="{CerberusContexts::CONTEXT_AUTOMATION}" data-context-id="{$tool_automation->id}" data-cerb-peek>{$tool_call->getName()}</a>
                            {/if}

                            {$tool_params = $tool_call->getParameters()}
                            {if $tool_params}<textarea data-cerb-transcript-tool-params spellcheck="false">{$tool_params|json_encode:448}</textarea>{/if}

                            {if $tool_call_id && isset($tool_results[$tool_call_id])}
                                {if isset($tool_results_json[$tool_call_id])}<textarea data-cerb-transcript-tool-result data-content-type="application/json" spellcheck="false">{$tool_results_json[$tool_call_id]}</textarea>{else}<pre data-cerb-transcript-tool-result>{$tool_results[$tool_call_id]}</pre>{/if}
                            {/if}
                        </div>
                    {/foreach}
                {/if}
            {/foreach}
        </div>
    {/foreach}
</div>
