{$div_id = uniqid('peek_')}
{$record_uri = $context_ext->manifest->params.alias}
{$record_aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest)}
{if !isset($toolbar_card)}{$toolbar_card = null}{/if}

<div id="{$div_id}" class="cerb-u-flex cerb-u-items-start cerb-u-gap-3" data-cerb-dialog-title="{$context_ext->manifest->name}: {$dict->_label}">
    {* A context avatar outranks everything; otherwise the record's own glyph (`_icon`, for a type whose mark
       varies per record) and then the static record-type mark. A brand color only when the record supplies
       one — every other type keeps the neutral tile. *}
    {$record_icon_color = $context_ext->getIconColor($dict)}
    <div data-cerb-card-record-image class="cerb-u-flex-shrink-0">
        <span data-cerb-card-avatar
            class="cerb-ui-avatar cerb-ui-avatar--tile"
            data-avatar="{$dict->_label}"
            {* data-avatar-seed="{$peek_context}:{$dict->id}" *}
            data-avatar-color="{if $record_icon_color}{$record_icon_color}{else}var(--cerb-color-background-contrast-180){/if}"
            data-avatar-size="75"
            {if $context_ext->hasOption('avatars')}data-avatar-image="{devblocks_url}c=avatars&context={$peek_context}&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at|default:$dict->updated}"{else}data-avatar-icon="{$context_ext->getIcon($dict)}"{/if}
        ></span>
    </div>

    <div class="cerb-u-flex-1">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div>
                <div class="cerb-ui-header--subtitle">{$context_ext->manifest->name}</div>
                <div class="cerb-ui-header--title cerb-u-break-word">{$dict->_label}</div>
            </div>
        </div>

        <div data-cerb-card-toolbar class="cerb-ui-toolbar-rail" style="margin-top:5px;">
            {if !is_array($toolbar_card) || !array_key_exists('profile', $toolbar_card)}
                {if $dict->id && $dict->record_url}<button type="button" class="cerb-peek-profile"><span class="cerb-icons cerb-icon-id-card"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
            {/if}

            {if !is_array($toolbar_card) || !array_key_exists('edit', $toolbar_card)}
                {if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
                    <button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-width="75%" data-edit="true"><span class="cerb-icons cerb-icon-gear"></span> {'common.edit'|devblocks_translate|capitalize}</button>
                {/if}
            {/if}

            {if $active_worker->is_superuser}
                <button data-cerb-button-toggle-hidden type="button" class="cerb-ui-toolbar-button" style="display:none;" aria-pressed="false" title="Hidden widgets"><span class="cerb-icons cerb-icon-eye-close"></span> Hidden Widgets <span class="cerb-ui-toolbar--badge cerb-ui-toolbar--badge-neutral badge-count">0</span></button>
            {/if}

            {if !is_array($toolbar_card) || !array_key_exists('watchers', $toolbar_card)}
                {if !empty($dict->id) && $context_ext->hasOption('watchers')}
                    {$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
                    {include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full_label=true}
                {/if}
            {/if}

            {if !is_array($toolbar_card) || !array_key_exists('comments', $toolbar_card)}
                {if $context_ext->hasOption('comments')}
                    {if $active_worker->hasPriv("contexts.{$peek_context}.comment")}<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="cerb-icons cerb-icon-comments"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
                {/if}
            {/if}

            <div data-cerb-toolbar>
                {if $toolbar_card}
                {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_card)}
                {/if}
            </div>
            {if $active_worker->is_superuser}
                <button type="button" data-cerb-toolbar-setup class="cerb-ui-toolbar-config-button" title="{'common.configure'|devblocks_translate|capitalize}" data-context="{CerberusContexts::CONTEXT_TOOLBAR}" data-context-id="record.card" data-edit="true"><span class="cerb-icons cerb-icon-gear"></span></button>
            {/if}
        </div>
    </div>
</div>

<div style="padding-top:10px;"></div>

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

<div class="cerb-card-layout cerb-card-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
    <div data-layout-zone="content" class="cerb-card-layout-zone" style="flex:1 1 100%;overflow-x:clip;">
        <div class="cerb-card-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;">
            {foreach from=$zones.content item=widget name=widgets}
                {include file="devblocks:cerberusweb.core::internal/cards/widgets/render.tpl" widget=$widget}
            {/foreach}
        </div>
    </div>
</div>

{if $active_worker->is_superuser}
<div class="cerb-button-add-widget" style="cursor:pointer;border:1px dashed var(--cerb-color-background-contrast-220);padding:2px;text-align:center;" data-context="{CerberusContexts::CONTEXT_CARD_WIDGET}" data-context-id="0" data-edit="context:{$peek_context}" data-width="75%">
    <button style="background:none;color:var(--cerb-color-background-contrast-150);" type="button"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add.widget'|devblocks_translate|capitalize}</button>
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $div = $('#{$div_id}');
    let $popup = genericAjaxPopupFind($div);
    let $layer = $popup.attr('data-layer');

    $popup.one('popup_open',function() {
        $popup.css('overflow', 'inherit');

        // Header avatar
        if(window.CerbUI && CerbUI.Avatar)
            CerbUI.Avatar.enhance($popup[0], '[data-cerb-card-avatar]');

        let doneFunc = function(e) {
            e.stopPropagation();

            if(!e.hasOwnProperty('trigger'))
                return;

            if(e.hasOwnProperty('eventData') && e.eventData.exit === 'return') {
                Devblocks.interactionWorkerPostActions(e.eventData);
            }

            let $target = e.trigger;
            let done_params = new URLSearchParams($target.attr('data-interaction-done'));

            if(done_params.has('refresh_toolbar')) {
                let refresh = done_params.get('refresh_toolbar');

                if(refresh && '0' !== refresh) {
                    $toolbar.trigger($.Event('cerb-toolbar--refresh'));
                }
            }

            let done_actions = Devblocks.toolbarAfterActions(done_params, {
                'widgets': $popup.find('.cerb-card-widget'),
                'default_widget_ids': [],
            });

            // Refresh card widgets
            if(done_actions.hasOwnProperty('refresh_widget_ids')) {
                $popup.triggerHandler($.Event('cerb-widgets-refresh', {
                    widget_ids: done_actions['refresh_widget_ids'],
                    refresh_options: { }
                }));
            }

            // Close the card popup
            if(done_params.has('close') && done_params.get('close')) {
                genericAjaxPopupClose($popup);
            }
        }
        
        // Edit button
        {if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
        $popup.find('button.cerb-peek-edit')
            .cerbPeekTrigger({ 'view_id': '{$view_id}' })
            .on('cerb-peek-saved', function(e) {
                let saved_event = $.Event(e.type, e);
                saved_event.is_rebroadcast = true;
                $popup.trigger(saved_event);

                e.stopPropagation();

                if(!e.is_rebroadcast) {
                    $popup.trigger($.Event('cerb-widgets-refresh'));

                    {if $context_ext->hasOption('avatars')}
                    if(e.hasOwnProperty('record_image_url') && window.CerbUI && CerbUI.Avatar) {
                        let avatarEl = $popup.find('[data-cerb-card-avatar]')[0];
                        if(avatarEl) {
                            avatarEl.setAttribute('data-avatar-image', e.record_image_url);
                            new CerbUI.Avatar(avatarEl);
                        }
                    }
                    {/if}
                }
            })
            .on('cerb-peek-deleted', function(e) {
                let delete_event = $.Event(e.type, e);
                delete_event.is_rebroadcast = true;
                $popup.trigger(delete_event);

                e.stopPropagation();

                if(!e.is_rebroadcast) {
                    genericAjaxPopupClose($layer);
                }
            })
        ;
        {/if}

        // Comments
        $popup.find('button.cerb-peek-comments-add')
            .cerbPeekTrigger()
            .on('cerb-peek-saved', function() {
                $popup.trigger($.Event('cerb-widgets-refresh'));
            })
        ;

        // Peeks
        $popup.find('.cerb-peek-trigger')
            .cerbPeekTrigger()
        ;

        // View profile
        $popup.find('.cerb-peek-profile').click(function(e) {
            if(e.shiftKey || e.metaKey) {
                window.open('{$dict->record_url}', '_blank', 'noopener');

            } else {
                document.location='{$dict->record_url}';
            }
        });

        // Hidden widgets

        let $toggle_widgets_button = $popup.find('[data-cerb-button-toggle-hidden]');
        let count_widgets_hidden = $popup.find('.cerb-card-widget--hidden').length;

        $toggle_widgets_button
            .on('click', function(e) {
                e.stopPropagation();

                let $btn = $(this);
                let show = 'true' !== $btn.attr('aria-pressed');

                $btn.attr('aria-pressed', show ? 'true' : 'false')
                    .toggleClass('cerb-ui-toolbar-button--active', show);

                let $hidden = $popup.find('.cerb-card-widget--hidden');

                if(show) {
                    $hidden.show();

                    // Load content for any widget revealed for the first time (harmless no-op if already loaded)
                    let load_ids = [];
                    $hidden.each(function() {
                        let $content = $(this).find('.cerb-card-widget--content');
                        if($content.length && 0 === $content.children().length)
                            load_ids.push(parseInt($(this).attr('data-widget-id')));
                    });

                    if(load_ids.length)
                        $popup.trigger($.Event('cerb-widgets-refresh', { widget_ids: load_ids }));
                } else {
                    $hidden.hide();
                }
            })
        ;

        if(count_widgets_hidden > 0) {
            $toggle_widgets_button.find('.badge-count').text(count_widgets_hidden);
            $toggle_widgets_button.show();
        }

        // Toolbar
        
        let $card_toolbar = $popup.find('[data-cerb-card-toolbar]');
        let $toolbar = $card_toolbar.find('[data-cerb-toolbar]');
        
        $toolbar.on('cerb-toolbar--refresh', function(e) {
            e.stopPropagation();
            
            genericAjaxGet('', 'c=profiles&a=renderToolbar&record_type={$dict->_context}&record_id={$dict->id}&toolbar=record.card', function(html) {
                $toolbar
                    .html(html)
                    .trigger('cerb-toolbar--refreshed')
                ;
            });
        });

        let buildCardToolbar = function() {
            let ul = $toolbar.find('ul.cerb-ui-toolbar')[0];
            if(!ul || !(window.CerbUI && CerbUI.Toolbar)) return;
            new CerbUI.Toolbar(ul, {
                caller: {
                    name: 'cerb.toolbar.record.card',
                    params: {
                        'record__context': '{$dict->_context}',
                        'record_id': '{$dict->id}'
                    }
                },
                start: function(formData) {
                },
                done: doneFunc
            });
        };
        $toolbar.on('cerb-toolbar--refreshed', buildCardToolbar);
        buildCardToolbar();

        let $toolbar_setup = $card_toolbar.find('[data-cerb-toolbar-setup]');

        $toolbar_setup
            .cerbPeekTrigger()
            .on('cerb-peek-saved', function() {
                $toolbar.trigger($.Event('cerb-toolbar--refresh'));
            })
        ;

        let $add_button = $popup.find('.cerb-button-add-widget');

        // Drag
        {if $active_worker->is_superuser}
        $popup.find('.cerb-card-layout-zone--widgets').each(function() {
            if(window.CerbUI && CerbUI.Sortable)
                new CerbUI.Sortable(this, {
                    tolerance: 'pointer',
                    items: '.cerb-card-widget',
                    handle: '.cerb-card-widget--header .cerb-icon-menu-hamburger',
                    connectWith: '.cerb-card-layout-zone--widgets',
                    helper: 'clone',
                    onStart: function() {
                        // Give empty zones a min-height so they're droppable + a faint outline so the drop
                        // areas are discoverable. The moving slot placeholder shows the actual drop spot.
                        $popup.find('.cerb-card-layout-zone--widgets')
                            .css('outline', '1px dashed var(--cerb-color-background-contrast-200)')
                            .css('outline-offset', '-2px')
                            .css('min-height', '100px')
                        ;
                    },
                    onEnd: function() {
                        // Clears on commit AND cancel (snap-back), so it never sticks
                        $popup.find('.cerb-card-layout-zone--widgets')
                            .css('outline', '')
                            .css('outline-offset', '')
                            .css('min-height', '')
                        ;
                    }
                });
        });

        // reorderWidgets posts the full zone→widget map; the bubbling sorted event fires once per drop
        $popup[0].addEventListener('cerb-ui-sortable:sorted', function() {
            $popup.trigger('cerb-reorder');
        });
        {/if}

        $popup.on('cerb-reorder', function(e) {
            let formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'card_widget');
            formData.set('action', 'reorderWidgets');
            formData.set('record_type', '{$peek_context}');

            // Zones
            $popup.find('.cerb-card-layout-zone')
                .each(function(d) {
                    let $cell = $(this);
                    let zone = $cell.attr('data-layout-zone');
                    let ids = $cell.find('.cerb-card-widget').map(function(d) { return $(this).attr('data-widget-id'); });

                    formData.append('zones[' + zone + ']', $.makeArray(ids));
                })
            ;

            genericAjaxPost(formData);
        });

        $popup.on('cerb-widget-refresh', function(e) {
            let widget_id = e.widget_id;
            let refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : [];

            CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, widget_id, false, refresh_options) ], function(err, json) {
                // Done
            });
        });

        $popup.on('cerb-widgets-refresh', function(e) {
            let widget_ids = (e.widget_ids && $.isArray(e.widget_ids)) ? e.widget_ids : [];
            let refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : { };

            let jobs = [];

            $popup.find('.cerb-card-widget').each(function() {
                let $widget = $(this);
                let widget_id = parseInt($widget.attr('data-widget-id'));

                // If we're refreshing this widget or all widgets
                if(widget_id && (0 === widget_ids.length || -1 !== $.inArray(widget_id, widget_ids))) {
                    jobs.push(
                        CerbUI.utils.apply(loadWidgetFunc, widget_id, false, refresh_options)
                    );
                }
            });

            CerbUI.utils.parallelLimit(jobs, 2, function(err, json) {
                // Done
            });
        });

        let addEvents = function($target) {
            let menuEl = $target.find('.cerb-card-widget--menu')[0];
            let $menu_link = $target.find('.cerb-card-widget--link');
            let $handle = $target.find('.cerb-card-widget--header .cerb-icon-menu-hamburger');

            {if $active_worker->is_superuser}
            $target.hoverIntent({
                interval: 50,
                timeout: 250,
                over: function (e) {
                    $handle.show();
                },
                out: function (e) {
                    $handle.hide();
                }
            });
            {/if}

            let menu = (menuEl && window.CerbUI && CerbUI.Menu) ? new CerbUI.Menu(menuEl, {
                    clickTrigger: $menu_link[0],
                    onSelect: function(li, src) {
                        let $li = $(src);

                        let $widget = $li.closest('.cerb-card-widget');
                        let widget_id = $widget.attr('data-widget-id');

                        if($li.is('.cerb-card-widget-menu--edit')) {
                            $li.clone()
                                .cerbPeekTrigger()
                                .on('cerb-peek-saved', function(e) {
                                    // [TODO] Check the event type
                                    CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
                                        // Done
                                    });
                                })
                                .on('cerb-peek-deleted', function(e) {
                                    $widget.remove();
                                    $popup.trigger('cerb-reorder');
                                })
                                .click()
                            ;

                        } else if($li.is('.cerb-card-widget-menu--refresh')) {
                            CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, widget_id, false, {}) ], function(err, json) {
                                // Done
                            });
                        } else if($li.is('.cerb-card-widget-menu--export-widget')) {
                            genericAjaxPopup('export_widget', 'c=profiles&a=invoke&module=card_widget&action=exportWidget&id=' + widget_id, null, false);
                        }
                    }
                }) : null;

            return $target;
        };

        $popup.find('.cerb-card-widget').each(function() {
            addEvents($(this));
        });

        {if $active_worker->is_superuser}
        $add_button
            .cerbPeekTrigger()
            .on('cerb-peek-saved', function(e) {
                let $zone = $popup.find('.cerb-card-layout-zone:first > .cerb-card-layout-zone--widgets:first');
                let $placeholder = $('<div class="cerb-card-widget"/>').attr('data-widget-id', e.id).hide().appendTo($zone);
                $('<div/>').attr('id', 'cardWidget' + e.id + '_{$dict->id}').addClass('cerb-card-widget--content').appendTo($placeholder);

                CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
                    $popup.trigger('cerb-reorder');
                });
            })
        ;
        {/if}

        let loadWidgetFunc = function(widget_id, is_full, refresh_options, callback) {
            let $widget = $popup.find('.cerb-card-widget[data-widget-id=' + widget_id + '] .cerb-card-widget--content').fadeTo('fast', 0.3);

            Devblocks.getSpinner(true).prependTo($widget);

            let formData;

            if(refresh_options instanceof FormData) {
                formData = refresh_options;
            } else {
                formData = new FormData();
            }

            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'card_widget');
            formData.set('action', 'renderWidget');
            formData.set('context', '{$peek_context}');
            formData.set('context_id', '{$peek_context_id}');
            formData.set('id', widget_id);
            formData.set('full', is_full ? '1' : '0');

            if(refresh_options instanceof Object) {
                Devblocks.objectToFormData(refresh_options, formData);
            }

            genericAjaxPost(formData, '', '', function(html) {
                if(0 === html.length) {
                    $widget.empty();

                } else {
                    try {
                        if(is_full) {
                            addEvents($(html)).insertBefore(
                                $widget.attr('id',null).closest('.cerb-card-widget').hide()
                            );

                            $widget.closest('.cerb-card-widget').remove();
                        } else {
                            $widget.html(html);
                        }
                    } catch(e) {
                        if(console)
                            console.error(e);
                    }
                }

                $widget.fadeTo('fast', 1.0);
                callback();
            });
        };

        $popup.triggerHandler($.Event('cerb-widgets-refresh'));
    });
});
</script>
