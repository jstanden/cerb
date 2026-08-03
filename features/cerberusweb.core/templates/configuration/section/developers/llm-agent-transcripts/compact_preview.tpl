{$sent_count = $preview.send|count}
{$is_summarize = ($preview.mode == 'summarize')}
{* Compacted sends always lead with one boundary node (summary or marker), so the tail is the rest. *}
{if $preview.compacted}{$tail_count = $sent_count - 1}{else}{$tail_count = $sent_count}{/if}

<div class="cerb-ui-panel cerb-ui-panel--filled cerb-u-p-2 cerb-u-mb-2">
    {if $preview.compacted}
        <b>Over budget</b> -- ~{$preview.tokens} est tokens &gt; {$preview.max_tokens}.
        {if $is_summarize}
            Summarized <b>{$preview.folded|count}</b> older message(s) into a new root node and kept
            <b>{$tail_count}</b> recent turn(s) verbatim after it <span class="cerb-u-text-muted">(live summary below; keep_tail_tokens: {$preview.keep_tail_tokens})</span>.
        {else}
            Bounded out <b>{$preview.folded|count}</b> older message(s) behind an empty marker and kept
            <b>{$tail_count}</b> recent turn(s) verbatim <span class="cerb-u-text-muted">(no summary)</span>.
        {/if}
    {elseif $preview.reason == 'tail_covers_all'}
        <b>Nothing to compact</b> -- the verbatim tail already covers the whole context
        (~{$preview.tokens} est tokens), so there's nothing older to fold. <b>{$sent_count}</b> message(s) sent as-is.
    {else}
        <b>Under budget</b> -- ~{$preview.tokens} est tokens &le; {$preview.max_tokens}.
        No compaction; <b>{$sent_count}</b> message(s) sent as-is.
    {/if}
</div>

{* What the live summarization call actually cost. This is the ONLY way to see whether the warm-prefix
   sidecar hit the provider cache — a prefix mismatch is silent, and most expensive on exactly the long
   sessions that trigger compaction. Cached should carry the bulk; a big "In" means it missed. *}
{if $preview.summary_usage}
<div style="display:flex;flex-wrap:wrap;gap:1em;align-items:center;margin-bottom:1em;">
    <div class="cerb-ui-chip" style="flex:0 0 auto;" title="The one-off summarization call. `In` is the whole prompt (fresh + cached); `Cached` is the share served from the provider's prompt cache. A high Cached% means the warm prefix matched — the point of doing it this way.">
        <div class="cerb-ui-chip--head">Summary call</div>
        <div><div class="cerb-ui-chip--label">In</div><div class="cerb-ui-chip--value">{$preview.summary_usage.prompt|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Cached</div><div class="cerb-ui-chip--value">{$preview.summary_usage.coverage}%</div></div>
        <div><div class="cerb-ui-chip--label">Wrote</div><div class="cerb-ui-chip--value">{$preview.summary_usage.cache_write|number_format}</div></div>
        <div><div class="cerb-ui-chip--label">Out</div><div class="cerb-ui-chip--value">{$preview.summary_usage.output|number_format}</div></div>
    </div>
    {if $preview.summary_usage.coverage >= 50}
    <span class="cerb-ui-pill cerb-ui-pill--green" title="The warm prefix matched, so most of the conversation billed at the cache-read rate.">Cache hit</span>
    {else}
    <span class="cerb-ui-pill cerb-ui-pill--orange" title="Most of the prompt billed as fresh input — the prefix didn't match, or the cache had lapsed. Expected on a cold/idle session; a concern mid-conversation.">Cold prefix</span>
    {/if}
    {* A SMALL write is correct and expected: the breakpoint sits one message back, so a warm session reads the
       agent's own entry and mints only the delta past it (measured: 108 tokens on a 9,395-token prompt). What's
       wrong is a write that's a large SHARE of the prompt — that means we minted a whole new entry instead of
       extending one, and compaction is about to orphan it (measured: 28,023 of 32,787 on a cold session). *}
    {$write_share = ($preview.summary_usage.prompt > 0) ? (100 * $preview.summary_usage.cache_write / $preview.summary_usage.prompt) : 0}
    {if $write_share >= 25}
    <span class="cerb-ui-pill cerb-ui-pill--red" title="This call minted a new cache entry of {$preview.summary_usage.cache_write|number_format} tokens — {$write_share|string_format:'%d'}% of the prompt, so it extended nothing. A summarize sidecar's entry can't be reused (compaction replaces this history; the preview is one-shot), and a write bills ~1.25x.">Wasted cache write</span>
    {elseif $preview.summary_usage.cache_write > 0}
    <span class="cerb-u-text-muted" title="The rolling delta past the entry this read from — the normal cost of extending a warm cache by one turn.">+{$preview.summary_usage.cache_write|number_format} delta</span>
    {/if}
</div>
{/if}

{if $preview.summary_cold_fallback}
<div class="cerb-ui-panel cerb-ui-panel--filled cerb-u-p-2 cerb-u-mb-2 cerb-u-text-muted">
    <span class="cerb-icons cerb-icon-circle-info"></span> The warm-prefix summarizer couldn't run, so this used
    the flattened archive path instead (no cache reuse). That's the same fallback live compaction makes.
</div>
{/if}

{* Static markup — no .enhance(); the banded variant needs no JS. *}
{function name=compact_turn}
    {if $msg.role == 'user'}{$mod = 'cerb-ui-agent-transcript--user'}{$icon = 'user'}{$label = 'user'}
    {elseif $msg.role == 'agent'}{$mod = 'cerb-ui-agent-transcript--assistant'}{$icon = 'bot'}{$label = 'agent'}
    {elseif $msg.role == 'summary'}{$mod = 'cerb-ui-agent-transcript--boundary'}{$icon = 'archive'}{$label = 'new root'}
    {elseif $msg.is_tool_result}{$mod = ''}{$icon = 'hammer'}{$label = 'tool result'}
    {else}{$mod = ''}{$icon = 'circle-info'}{$label = $msg.role}{/if}
    <div class="cerb-ui-agent-transcript--turn {$mod}">
        <div class="cerb-ui-agent-transcript--role"><span class="cerb-icons cerb-icon-{$icon}"></span> {$label}</div>
        {if $msg.text != ''}<div class="cerb-ui-agent-transcript--body"><pre class="emailbody">{$msg.text}</pre></div>{/if}
        {if $msg.tools}
        <div class="cerb-u-text-muted cerb-u-mt-2"><span class="cerb-icons cerb-icon-hammer"></span> <b>{$msg.tools}</b></div>
        {/if}
    </div>
{/function}

{if $preview.folded}
<div class="cerb-field-cell-label">{if $is_summarize}Folded into summary{else}Bounded out (marker){/if}</div>
<div class="cerb-ui-agent-transcript cerb-ui-agent-transcript--banded cerb-ui-agent-transcript--folded cerb-u-mb-2">
    {foreach from=$preview.folded item=msg}{call name=compact_turn msg=$msg}{/foreach}
</div>
{/if}

<div class="cerb-field-cell-label">Would be sent</div>
<div class="cerb-ui-agent-transcript cerb-ui-agent-transcript--banded">
    {foreach from=$preview.send item=msg}{call name=compact_turn msg=$msg}{/foreach}
</div>
