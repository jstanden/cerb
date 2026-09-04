{$div_uid = uniqid('div')}

<div class="cerb-ui-header">
    <div>
        <div class="cerb-ui-header--title">Agent Transcripts</div>
        <div class="cerb-ui-header--subtitle"></div>
    </div>
</div>

<div id="{$div_uid}" class="cerb-ui-sidebar-layout">
    <aside class="cerb-ui-sidebar" data-cerb-sidebar-limit="{$limit}" style="--cerb-ui-sidebar-width:320px;">
        <div class="cerb-ui-sidebar--head">
            <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                <div class="cerb-ui-toolbar-strip">
                    <button type="button" class="cerb-ui-toolbar-button" data-cerb-button="refresh" title="{{'common.refresh'|devblocks_translate|capitalize}}"><span class="cerb-icons cerb-icon-refresh"></span></button>
                </div>
                <div class="cerb-ui-switcher" data-cerb-transcript-scope style="margin-left:auto;">
                    <button type="button" class="cerb-ui-switcher--active" data-value="active">Open<span class="cerb-ui-switcher--badge" data-cerb-transcript-count="active" title="{$transcript_counts.active.title}">{$transcript_counts.active.label}</span></button>
                    <button type="button" data-value="archived">Archived<span class="cerb-ui-switcher--badge" data-cerb-transcript-count="archived" title="{$transcript_counts.archived.title}">{$transcript_counts.archived.label}</span></button>
                </div>
            </div>
        </div>

        <div class="cerb-ui-sidebar--body">
            <ul class="cerb-transcript-list">
                {include file="devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcripts.tpl" transcripts=$transcripts}
            </ul>

            <div class="cerb-transcript-more-wrap"{if $transcripts|count < $limit} style="display:none;"{/if}>
                <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="more">{{'common.more'|devblocks_translate|capitalize}} <span class="cerb-icons cerb-icon-circle-arrow-down"></span></button>
            </div>
        </div>
    </aside>

    <div class="cerb-ui-sidebar-layout--content"></div>
</div>

<style nonce="{DevblocksPlatform::getRequestNonce()}">
/* Transcript rows render as tiles; strip the tile's card chrome so it blends into the
   sidebar row (which supplies its own padding, hover, and active-selection fill). */
#{$div_uid} .cerb-ui-sidebar--item .cerb-ui-tile {
    background: transparent;
    border-color: transparent;
    padding: 0;
    width: 100%;
    min-width: 0;
}

#{$div_uid} .cerb-transcript-item--uuid {
    font-family: ui-monospace, Menlo, Consolas, monospace;
}

/* The scope switcher has no room in the collapsed icon-only rail. */
#{$div_uid} .cerb-ui-sidebar--collapsed .cerb-ui-switcher {
    display: none;
}

/* Keep the kind eyebrow (worker · time · tokens) on one line in the narrow rail. */
#{$div_uid} .cerb-ui-sidebar--item .cerb-ui-tile--kind {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-transform: none;
    font-size: 0.9em;
}

/* On the active (accent-filled) row, let the tile text inherit the row's (white) text color. */
#{$div_uid} .cerb-ui-sidebar--item-active .cerb-ui-tile--kind {
    color: inherit;
    opacity: 0.85;
}

#{$div_uid} .cerb-ui-sidebar--item-active .cerb-ui-tile--name {
    color: inherit;
}

/* Read state: a gray check badge on the provider icon, and the icon itself grays out
   (overrides the inline brand color) — no text graying. */
#{$div_uid} .cerb-avatar-badged > .cerb-transcript-badge {
    font-size: 8px;
}

#{$div_uid} .cerb-transcript-item--read .cerb-ui-tile--icon {
    background: var(--cerb-color-tag-gray) !important;
    color: #fff !important;
}

/* Read rows mute the uuid name to the same gray as the eyebrow so they're easy to skim past. */
#{$div_uid} .cerb-transcript-item--read .cerb-ui-tile--name {
    color: var(--cerb-color-background-contrast-150);
}

#{$div_uid} .cerb-transcript-more-wrap {
    padding: 6px 12px 2px;
    text-align: center;
}
</style>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
    let $container = $('#{$div_uid}');
    let $sidebar = $container.find('aside.cerb-ui-sidebar');
    let $list = $sidebar.find('ul.cerb-transcript-list');
    let $pager = $sidebar.find('.cerb-transcript-more-wrap');
    let $viewer = $container.find('.cerb-ui-sidebar-layout--content');
    let $current_transcript = null;
    let current_transcript_id = null;
    let sb = null;

    const funcLoadTranscript = function(transcript_id) {
        $viewer.html(Devblocks.getSpinner());

        let formData = new FormData();
        formData.set('c', 'config');
        formData.set('a', 'invoke');
        formData.set('module', 'llm_agent_transcripts');
        formData.set('action', 'getTranscript');
        formData.set('transcript_id', transcript_id);

        genericAjaxPost(formData, $viewer, null, function(json) {
            Devblocks.clearAlerts();

            if(json && typeof json == 'object') {
                if(json.error) {
                    Devblocks.createAlertError(json.error);

                } else {
                    $viewer.html(json.html);
                    $viewer.find('[data-cerb-peek]').cerbPeekTrigger();

                    // The component builds the transcript's chrome. `fork-from-here` stays ours (it needs the
                    // session id); the header actions are outside the transcript root, on the delegate below.
                    if(window.CerbUI && CerbUI.AgentTranscript) {
                        CerbUI.AgentTranscript.enhance($viewer[0], undefined, {
                            // Opting IN to the raw params/results the component hides by default, and to the
                            // Markdown/Text switcher. This is the superuser audit surface — seeing exactly what
                            // the agent sent and got back, in either form, is the entire point of it.
                            view: 'toggle',
                            thinking: 'raw',
                            tools: 'raw',
                            expand: 'all',
                            onTurnAction: function(value, ctx) {
                                if('fork-from-here' !== value)
                                    return false;

                                funcForkFromHere(ctx.seq);
                                return true;
                            }
                        });
                    }

                    $viewer.find('[data-cerb-permalink]').on('click', function(e) {
                        e.stopPropagation();
                        let permalink_url = $(this).attr('data-cerb-permalink');
                        genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url=' + encodeURIComponent(permalink_url));
                    });
                }
            }
        });
    };

    const funcForkFromHere = function(at_seq) {
        if(!current_transcript_id || !at_seq)
            return;

        let fd = new FormData();
        fd.set('c', 'config');
        fd.set('a', 'invoke');
        fd.set('module', 'llm_agent_transcripts');
        fd.set('action', 'forkFromHere');
        fd.set('transcript_id', current_transcript_id);
        fd.set('at_seq', at_seq);

        genericAjaxPost(fd, null, null, function(json) {
            Devblocks.clearAlerts();

            if(json && typeof json == 'object') {
                if(json.error) {
                    Devblocks.createAlertError(json.error);

                } else {
                    Devblocks.createAlert('Forked from here.');
                    funcLoadList();
                    funcLoadTranscript(json.transcript_id);
                }
            }
        });
    };

    const funcSelectTranscript = function(li) {
        if(!li)
            return;

        $current_transcript = $(li);
        current_transcript_id = li.getAttribute('data-cerb-transcript-id');

        if(sb)
            sb.setActive(li);

        funcLoadTranscript(current_transcript_id);
    };

    const funcSelectNext = function($li) {
        let $next = ($li && $li.length) ? $li.nextAll('li[data-cerb-transcript-id]').first() : $();

        if($next.length) {
            funcSelectTranscript($next[0]);
        } else {
            $current_transcript = null;
            current_transcript_id = null;
            if(sb) sb.setActive(null);
        }
    };

    // Scope totals for the Open/Archived switcher badges. Every response that can move a transcript
    // between scopes (list, mark-read, delete) returns them, so the badges never drift from the list. The
    // server sends both forms (abbreviated label + exact tooltip) so the abbreviation isn't reimplemented here.
    const funcUpdateCounts = function(counts) {
        if(!counts || typeof counts != 'object')
            return;

        $sidebar.find('[data-cerb-transcript-count]').each(function() {
            let key = this.getAttribute('data-cerb-transcript-count');

            if(!(key in counts))
                return;

            this.textContent = counts[key].label;
            this.title = counts[key].title;
        });
    };

    const funcUpdatePager = function(count) {
        let limit = parseInt($sidebar.attr('data-cerb-sidebar-limit'), 10) || 100;
        $pager.css('display', count >= limit ? '' : 'none');
    };

    // before_id (optional) = paginate older than this uuid (append); omitted = a full reload (replace).
    const funcLoadList = function(before_id) {
        // 'active' (default) = unarchived only; 'archived' = archived only. Driven by the head Switcher.
        let scope = $sidebar.attr('data-cerb-transcript-scope') || 'active';
        let limit = $sidebar.attr('data-cerb-sidebar-limit') || '100';

        let formData = new FormData();
        formData.set('c', 'config');
        formData.set('a', 'invoke');
        formData.set('module', 'llm_agent_transcripts');
        formData.set('action', 'loadTranscripts');
        formData.set('limit', limit);
        formData.set('filter', scope);

        if(before_id)
            formData.set('before_id', before_id);

        let $spinner = Devblocks.getSpinner(true);
        $list.fadeTo('fast', 0.2);

        if(!before_id)
            $spinner.insertBefore($list);

        genericAjaxPost(formData, null, null, function(json) {
            Devblocks.clearAlerts();
            $spinner.remove();
            $list.fadeTo('fast', 1.0);

            if(json && typeof json == 'object') {
                if(json.error) {
                    Devblocks.createAlertError(json.error);

                } else {
                    let $items = $(json.html);
                    let count = $items.filter('li[data-cerb-transcript-id]').length;

                    if(before_id)
                        $list.append($items);
                    else
                        $list.html($items);

                    funcUpdatePager(count);
                    funcUpdateCounts(json.counts);
                }
            }
        });
    };

    // Left rail — CerbUI.Sidebar supplies the chrome (collapse) + selection/active state. List items are
    // authored (passthrough), so refreshed/appended <li>s keep working without re-enhancement.
    if(window.CerbUI && CerbUI.Sidebar) {
        sb = new CerbUI.Sidebar($sidebar[0], {
            fullHeight: true,
            onSelect: function(li) {
                if(!li.hasAttribute('data-cerb-transcript-id'))
                    return false;

                funcSelectTranscript(li);
                return true;
            }
        });
    } else {
        $sidebar.on('click', 'li[data-cerb-transcript-id]', function(e) {
            e.stopPropagation();
            funcSelectTranscript(this);
        });
    }

    // Sidebar toolbar: refresh / unread / more (delegated — survives list re-render).
    $sidebar.on('click', 'button[data-cerb-button]', function(e) {
        e.stopPropagation();
        let $button = $(this);
        let button_action = $button.attr('data-cerb-button');

        if('refresh' === button_action) {
            funcLoadList();

        } else if('more' === button_action) {
            let $oldest = $list.find('li[data-cerb-transcript-id]').last();
            funcLoadList($oldest.attr('data-cerb-transcript-id'));
        }
    });

    // Active / Archived scope switcher (default active). Reloads the list on change.
    if(window.CerbUI && CerbUI.Switcher) {
        let switcherEl = $sidebar[0].querySelector('.cerb-ui-switcher[data-cerb-transcript-scope]');
        if(switcherEl) {
            new CerbUI.Switcher(switcherEl, {
                onSelect: function(value) {
                    $sidebar.attr('data-cerb-transcript-scope', value);
                    funcLoadList();
                }
            });
        }
    }

    // Viewer header toolbar: delete / mark-read / fork / compact / permalink (delegated — survives AJAX).
    $viewer.on('click', 'button[data-cerb-button]', function(e) {
        e.stopPropagation();
        let $button = $(this);
        let button_action = $button.attr('data-cerb-button');

        if('delete' === button_action) {
            CerbUI.Confirm.open({
                title: 'Delete Transcript',
                body: 'Are you sure you want to permanently delete this transcript?',
                onConfirm: function() {
                    let formData = new FormData();
                    formData.set('c', 'config');
                    formData.set('a', 'invoke');
                    formData.set('module', 'llm_agent_transcripts');
                    formData.set('action', 'deleteTranscript');
                    formData.set('transcript_id', current_transcript_id);

                    genericAjaxPost(formData, null, null, function(json) {
                        Devblocks.clearAlerts();

                        if(json && typeof json == 'object') {
                            if(json.error) {
                                Devblocks.createAlertError(json.error);

                            } else {
                                $viewer.empty();
                                funcUpdateCounts(json.counts);

                                let $item = $current_transcript;
                                let $next = ($item && $item.length) ? $item.nextAll('li[data-cerb-transcript-id]').first() : $();

                                if($item && $item.length)
                                    $item.remove();

                                if($next.length)
                                    funcSelectTranscript($next[0]);
                                else {
                                    $current_transcript = null;
                                    current_transcript_id = null;
                                    if(sb) sb.setActive(null);
                                }
                            }
                        }
                    });
                }
            });

        } else if('mark-read' === button_action) {
            let formData = new FormData();
            formData.set('c', 'config');
            formData.set('a', 'invoke');
            formData.set('module', 'llm_agent_transcripts');
            formData.set('action', 'markTranscriptRead');
            formData.set('transcript_id', current_transcript_id);

            genericAjaxPost(formData, null, null, function(json) {
                Devblocks.clearAlerts();

                if(json && typeof json == 'object') {
                    if(json.error) {
                        Devblocks.createAlertError(json.error);

                    } else {
                        $viewer.empty();
                        funcUpdateCounts(json.counts);

                        let $item = $current_transcript;

                        if($item && $item.length && 0 === $item.find('.cerb-transcript-badge').length) {
                            $('<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--gray cerb-transcript-badge" title="Read"><span class="cerb-icons cerb-icon-check"></span></span>')
                                .appendTo($item.find('.cerb-avatar-badged'));
                        }

                        if($item && $item.length) {
                            $item.addClass('cerb-transcript-item--read');
                        }

                        funcSelectNext($item);
                    }
                }
            });

        } else if('fork' === button_action) {
            if(!current_transcript_id || !(window.CerbUI && CerbUI.Dialog))
                return;

            let req = new FormData();
            req.set('c', 'config');
            req.set('a', 'invoke');
            req.set('module', 'llm_agent_transcripts');
            req.set('action', 'forkTranscriptForm');
            req.set('transcript_id', current_transcript_id);

            let source_id = current_transcript_id;

            CerbUI.Dialog.fromAjax(req, {
                title: 'Fork Transcript',
                modal: true,
                width: 460,
                onLoad: function(content) {
                    let $content = $(content);

                    $content.on('click', '[data-cerb-button="fork-cancel"]', function() {
                        let dlg = CerbUI.Dialog.from(content);
                        if(dlg) dlg.close();
                    });

                    $content.on('submit', 'form[data-cerb-fork-form]', function(e) {
                        e.preventDefault();

                        let provider = $content.find('input[name="target_provider"]:checked').val();

                        if(!provider) {
                            Devblocks.createAlertError('Select a target provider.');
                            return;
                        }

                        let fd = new FormData();
                        fd.set('c', 'config');
                        fd.set('a', 'invoke');
                        fd.set('module', 'llm_agent_transcripts');
                        fd.set('action', 'forkTranscript');
                        fd.set('transcript_id', source_id);
                        fd.set('target_provider', provider);
                        fd.set('model', $content.find('input[name="model"]').val() || '');

                        genericAjaxPost(fd, null, null, function(json) {
                            Devblocks.clearAlerts();

                            if(json && typeof json == 'object') {
                                if(json.error) {
                                    Devblocks.createAlertError(json.error);

                                } else {
                                    let dlg = CerbUI.Dialog.from(content);
                                    if(dlg) dlg.close();

                                    Devblocks.createAlert('Forked to a new transcript.');
                                    funcLoadList();
                                    funcLoadTranscript(json.transcript_id);
                                }
                            }
                        });
                    });
                }
            });

        } else if('compact' === button_action) {
            if(!current_transcript_id || !(window.CerbUI && CerbUI.Dialog))
                return;

            let req = new FormData();
            req.set('c', 'config');
            req.set('a', 'invoke');
            req.set('module', 'llm_agent_transcripts');
            req.set('action', 'compactPreviewForm');
            req.set('transcript_id', current_transcript_id);

            let source_id = current_transcript_id;

            CerbUI.Dialog.fromAjax(req, {
                title: 'Compact — Preview',
                modal: true,
                width: 640,
                onLoad: function(content) {
                    let $content = $(content);

                    if(window.CerbUI && CerbUI.Toggle)
                        $content.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

                    $content.on('click', '[data-cerb-button="compact-cancel"]', function() {
                        let dlg = CerbUI.Dialog.from(content);
                        if(dlg) dlg.close();
                    });

                    $content.on('submit', 'form[data-cerb-compact-form]', function(e) {
                        e.preventDefault();

                        let $result = $content.find('[data-cerb-compact-result]');
                        $result.html(Devblocks.getSpinner());

                        let fd = new FormData(this);
                        fd.set('c', 'config');
                        fd.set('a', 'invoke');
                        fd.set('module', 'llm_agent_transcripts');
                        fd.set('action', 'compactPreview');
                        fd.set('transcript_id', source_id);

                        genericAjaxPost(fd, null, null, function(json) {
                            Devblocks.clearAlerts();

                            if(json && typeof json == 'object') {
                                if(json.error) {
                                    Devblocks.createAlertError(json.error);
                                    $result.empty();

                                } else {
                                    $result.html(json.html);
                                    let dlg = CerbUI.Dialog.from(content);
                                    if(dlg) dlg.reflow();
                                }
                            }
                        });
                    });
                }
            });

        }
    });

    {if $transcript_id}
    (function() {
        let $initial = $list.find('li[data-cerb-transcript-id="{$transcript_id}"]');

        if($initial.length)
            funcSelectTranscript($initial[0]);
        else
            funcLoadTranscript('{$transcript_id}');
    })();
    {/if}
});
</script>
