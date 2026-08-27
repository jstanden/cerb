{foreach from=$transcripts item=transcript}
{if $transcript->updated_at}{$activity = $transcript->updated_at}{else}{$activity = $transcript->created_at}{/if}
{$transcript_user = $transcript->getUser()}
{$transcript_agent = $transcript->getAgent()}
{* WHO the session ran as. Every row on this page shares a provider and a shape, so the agent is the only
   thing that distinguishes one at a glance; a session started by a script that named no agent falls back to
   the provider, and then to a bare label. *}
{if $transcript_agent}{$transcript_who = $transcript_agent->getName()}{elseif $transcript->provider}{$transcript_who = $transcript->provider}{else}{$transcript_who = 'Session'}{/if}
{* The session's own stamped `display:` mark, falling back to the provider's brand. An unprimed session has
   neither, so it keeps the generic glyph. *}
{if $transcript->provider}
    {$provider_icon = $transcript->getDisplayIcon()}
    {$provider_color = $transcript->getDisplayIconColor()}
{else}
    {$provider_icon = 'bot'}
    {$provider_color = ''}
{/if}
<li class="cerb-ui-sidebar--item{if $transcript->is_read} cerb-transcript-item--read{/if}" data-id="{$transcript->uuid}" data-cerb-transcript-id="{$transcript->uuid}" data-label="{$transcript_who} &middot; {if is_a($transcript_user, 'Model_Worker')}{$transcript_user->getName()}{else}Anonymous{/if} &middot; {$transcript->uuid}">
    <div class="cerb-ui-tile cerb-ui-tile--block">
        <span class="cerb-avatar-badged">
            <span class="cerb-ui-tile--icon" style="{if $provider_color}background:{$provider_color};color:#fff;{else}background:var(--cerb-color-background-contrast-230);color:var(--cerb-color-background-contrast-150);{/if}"><span class="cerb-icons cerb-icon-{$provider_icon}"></span></span>
            {if $transcript->is_read}<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--gray cerb-transcript-badge" title="Read"><span class="cerb-icons cerb-icon-check"></span></span>{/if}
        </span>
        <div class="cerb-ui-tile--text">
            {* Eyebrow: the short uuid first (quoted like a git commit -- the leading segment is the part that
               identifies), then who it ran FOR, then when, then size. The full uuid stays on the hover. *}
            <div class="cerb-ui-tile--name">{$transcript_who} <span class="cerb-u-text-muted">+</span> {if is_a($transcript_user, 'Model_Worker')}{$transcript_user->getName()}{else}Anonymous{/if}</div>
            <div class="cerb-ui-tile--kind"><span class="cerb-transcript-item--uuid" title="{$transcript->uuid}">{$transcript->getShortUuid()}</span> &middot; <abbr title="Created {$transcript->created_at|devblocks_date}{if $transcript->updated_at && $transcript->updated_at != $transcript->created_at} &middot; Active {$transcript->updated_at|devblocks_date}{/if}">{$activity|devblocks_prettytime}</abbr>{if $transcript->token_usage > 0} &middot; {$transcript->token_usage|number_format} tokens{/if}</div>
        </div>
    </div>
</li>
{/foreach}
