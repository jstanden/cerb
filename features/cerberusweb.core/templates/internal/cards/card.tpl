{$div_id = uniqid('peek_')}
{$record_uri = $context_ext->manifest->params.alias}
{$record_aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest)}
{if !isset($toolbar_card)}{$toolbar_card = null}{/if}

<div id="{$div_id}">
    {if $context_ext->hasOption('avatars')}
        <div style="float:left;margin-right:10px;">
            <img src="{devblocks_url}c=avatars&context={$peek_context}&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at|default:$dict->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
        </div>
    {/if}

    <div style="float:left;">
        <h1 style="word-break:break-all;">
            {$dict->_label}
        </h1>

        <div data-cerb-card-toolbar style="margin-top:5px;">
            {if !is_array($toolbar_card) || !array_key_exists('profile', $toolbar_card)}
                {if $dict->id && $dict->record_url}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
            {/if}

            {if !is_array($toolbar_card) || !array_key_exists('edit', $toolbar_card)}
                {if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
                    <button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-width="75%" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
                {/if}
            {/if}

            {if !is_array($toolbar_card) || !array_key_exists('watchers', $toolbar_card)}
                {if !empty($dict->id) && $context_ext->hasOption('watchers')}
                    {$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
                    {include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full_label=true}
                {/if}
            {/if}

            {if !is_array($toolbar_card) || !array_key_exists('comments', $toolbar_card)}
                {if $context_ext->hasOption('comments')}
                    {if $active_worker->hasPriv("contexts.{$peek_context}.comment")}<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
                {/if}
            {/if}

            <div data-cerb-toolbar style="display:inline-block;vertical-align:middle;">
                {if $toolbar_card}
                {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_card)}
                {/if}
            </div>
            {if $active_worker->is_superuser}
                <div data-cerb-toolbar-setup style="display:inline-block;vertical-align:middle;">
                    <a href="javascript:" data-context="{CerberusContexts::CONTEXT_TOOLBAR}" data-context-id="record.card" data-edit="true"><span class="glyphicons glyphicons-cogwheel" style="color:lightgray;"></span></a>
                </div>
            {/if}
        </div>
    </div>
</div>

<div style="clear:both;padding-top:10px;"></div>

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

<div class="cerb-card-layout cerb-card-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
    <div data-layout-zone="content" class="cerb-card-layout-zone" style="flex:1 1 100%;overflow-x:hidden;">
        <div class="cerb-card-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;">
            {foreach from=$zones.content item=widget name=widgets}
                {include file="devblocks:cerberusweb.core::internal/cards/widgets/render.tpl" widget=$widget}
            {/foreach}
        </div>
    </div>
</div>

{if $active_worker->is_superuser}
<div class="cerb-button-add-widget" style="cursor:pointer;border:1px dashed var(--cerb-color-background-contrast-220);padding:2px;text-align:center;" data-context="{CerberusContexts::CONTEXT_CARD_WIDGET}" data-context-id="0" data-edit="context:{$peek_context}" data-width="75%">
    <button style="background:none;color:var(--cerb-color-background-contrast-150);" type="button"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(150,150,150);"></span> {'common.add.widget'|devblocks_translate|capitalize}</button>
</div>
{/if}

<script type="text/javascript">
$(function() {
    var $div = $('#{$div_id}');
    var $popup = genericAjaxPopupFind($div);
    var $layer = $popup.attr('data-layer');

    $popup.one('popup_open',function() {
        $popup.dialog('option','title', "{$record_aliases.singular|capitalize|escape:'javascript' nofilter}");
        $popup.css('overflow', 'inherit');

        // Edit button
        {if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
        $popup.find('button.cerb-peek-edit')
            .cerbPeekTrigger({ 'view_id': '{$view_id}' })
            .on('cerb-peek-saved', function(e) {
                var saved_event = $.Event(e.type, e);
                saved_event.is_rebroadcast = true;
                $popup.trigger(saved_event);

                e.stopPropagation();

                if(!e.is_rebroadcast) {
                    $popup.trigger($.Event('cerb-widgets-refresh'));
                }
            })
            .on('cerb-peek-deleted', function(e) {
                var delete_event = $.Event(e.type, e);
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

        // Menus
        $popup.find('ul.cerb-menu').menu();

        // View profile
        $popup.find('.cerb-peek-profile').click(function(e) {
            if(e.shiftKey || e.metaKey) {
                window.open('{$dict->record_url}', '_blank', 'noopener');

            } else {
                document.location='{$dict->record_url}';
            }
        });
        
        // Toolbar
        
        var $card_toolbar = $popup.find('[data-cerb-card-toolbar]');
        var $toolbar = $card_toolbar.find('[data-cerb-toolbar]');

        $toolbar.cerbToolbar({
            caller: {
                name: 'cerb.toolbar.record.card',
                params: {
                    'record__context': '{$dict->_context}',
                    'record_id': '{$dict->id}'
                }
            },
            start: function(formData) {
            },
            done: function(e) {
                e.stopPropagation();

                var $target = e.trigger;

                if(!$target.is('.cerb-bot-trigger'))
                    return;

                if (e.eventData.exit === 'error') {

                } else if(e.eventData.exit === 'return') {
                    Devblocks.interactionWorkerPostActions(e.eventData);
                }

                var done_params = new URLSearchParams($target.attr('data-interaction-done'));

                // Refresh all widgets by default
                if(!done_params.has('refresh_widgets[]')) {
                    done_params.set('refresh_widgets[]', 'all');
                }

                var refresh = done_params.getAll('refresh_widgets[]');
                var widget_ids = [];

                if(-1 !== $.inArray('all', refresh)) {
                    // Everything
                } else {
                    $popup.find('.cerb-card-widget')
                        .filter(function() {
                            var $this = $(this);
                            var name = $this.attr('data-widget-name');

                            if(undefined === name)
                                return false;

                            return -1 !== $.inArray(name, refresh);
                        })
                        .each(function() {
                            var $this = $(this);
                            var widget_id = parseInt($this.attr('data-widget-id'));

                            if(widget_id)
                                widget_ids.push(widget_id);
                        })
                    ;
                }

                var evt = $.Event('cerb-widgets-refresh', {
                    widget_ids: widget_ids,
                    refresh_options: { }
                });

                $popup.triggerHandler(evt);

                // Close the card popup
                if(done_params.has('close') && done_params.get('close')) {
                    genericAjaxPopupClose($popup);
                }
            }
        });

        var $toolbar_setup = $card_toolbar.find('[data-cerb-toolbar-setup]');

        $toolbar_setup.find('a')
            .cerbPeekTrigger()
            .on('cerb-peek-saved', function() {
                genericAjaxGet('', 'c=profiles&a=renderToolbar&record_type={$dict->_context}&record_id={$dict->id}&toolbar=record.card', function(html) {
                    $toolbar
                        .html(html)
                        .trigger('cerb-toolbar--refreshed')
                    ;
                });
            })
        ;

        var $add_button = $popup.find('.cerb-button-add-widget');

        // Drag
        {if $active_worker->is_superuser}
        $popup.find('.cerb-card-layout-zone--widgets')
            .sortable({
                tolerance: 'pointer',
                cursorAt: { top: 5, left: 5 },
                items: '.cerb-card-widget',
                helper: function(event, element) {
                    return element.clone()
                        .css('outline','2px dashed var(--cerb-color-background-contrast-150)')
                        .css('outline-offset','-2px')
                        .css('background-color', 'var(--cerb-background-color)')
                        ;
                },
                placeholder: 'cerb-widget-drag-placeholder',
                forceHelperSize: true,
                forcePlaceholderSize: true,
                handle: '.cerb-card-widget--header .glyphicons-menu-hamburger',
                connectWith: '.cerb-card-layout-zone--widgets',
                opacity: 0.7,
                start: function(event, ui) {
                    ui.placeholder.css('flex', ui.item.css('flex'));
                    $popup.find('.cerb-card-layout-zone--widgets')
                        .css('outline', '2px dashed orange')
                        .css('outline-offset', '-3px')
                        .css('background-color', 'var(--cerb-color-background-contrast-250)')
                        .css('min-height', '100px')
                    ;
                },
                stop: function(event, ui) {
                    $popup.find('.cerb-card-layout-zone--widgets')
                        .css('outline', '')
                        .css('outline-offset', '')
                        .css('background-color', '')
                        .css('min-height', 'initial')
                    ;
                },
                update: function(event, ui) {
                    $popup.trigger('cerb-reorder');
                }
            })
        ;
        {/if}

        $popup.on('cerb-reorder', function(e) {
            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'card_widget');
            formData.set('action', 'reorderWidgets');
            formData.set('record_type', '{$peek_context}');

            // Zones
            $popup.find('.cerb-card-layout-zone')
                .each(function(d) {
                    var $cell = $(this);
                    var zone = $cell.attr('data-layout-zone');
                    var ids = $cell.find('.cerb-card-widget').map(function(d) { return $(this).attr('data-widget-id'); });

                    formData.append('zones[' + zone + ']', $.makeArray(ids));
                })
            ;

            genericAjaxPost(formData);
        });

        $popup.on('cerb-widget-refresh', function(e) {
            var widget_id = e.widget_id;
            var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : [];

            async.series([ async.apply(loadWidgetFunc, widget_id, false, refresh_options) ], function(err, json) {
                // Done
            });
        });

        $popup.on('cerb-widgets-refresh', function(e) {
            var widget_ids = (e.widget_ids && $.isArray(e.widget_ids)) ? e.widget_ids : [];
            var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : { };

            var jobs = [];

            $popup.find('.cerb-card-widget').each(function() {
                var $widget = $(this);
                var widget_id = parseInt($widget.attr('data-widget-id'));

                // If we're refreshing this widget or all widgets
                if(widget_id && (0 === widget_ids.length || -1 !== $.inArray(widget_id, widget_ids))) {
                    jobs.push(
                        async.apply(loadWidgetFunc, widget_id, false, refresh_options)
                    );
                }
            });

            async.parallelLimit(jobs, 2, function(err, json) {
                // Done
            });
        });

        var addEvents = function($target) {
            var $menu = $target.find('.cerb-card-widget--menu');
            var $menu_link = $target.find('.cerb-card-widget--link');
            var $handle = $target.find('.cerb-card-widget--header .glyphicons-menu-hamburger');

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

            $menu
                .menu({
                    select: function(event, ui) {
                        var $li = $(ui.item);
                        $li.closest('ul').hide();

                        var $widget = $li.closest('.cerb-card-widget');
                        var widget_id = $widget.attr('data-widget-id');

                        if($li.is('.cerb-card-widget-menu--edit')) {
                            $li.clone()
                                .cerbPeekTrigger()
                                .on('cerb-peek-saved', function(e) {
                                    // [TODO] Check the event type
                                    async.series([ async.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
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
                            async.series([ async.apply(loadWidgetFunc, widget_id, false, {}) ], function(err, json) {
                                // Done
                            });
                        } else if($li.is('.cerb-card-widget-menu--export-widget')) {
                            genericAjaxPopup('export_widget', 'c=profiles&a=invoke&module=card_widget&action=exportWidget&id=' + widget_id, null, false);
                        }
                    }
                })
            ;

            $menu_link.on('click', function(e) {
                e.stopPropagation();
                $(this).closest('.cerb-card-widget').find('.cerb-card-widget--menu').toggle();
            });

            return $target;
        };

        $popup.find('.cerb-card-widget').each(function() {
            addEvents($(this));
        });

        {if $active_worker->is_superuser}
        $add_button
            .cerbPeekTrigger()
            .on('cerb-peek-saved', function(e) {
                var $zone = $popup.find('.cerb-card-layout-zone:first > .cerb-card-layout-zone--widgets:first');
                var $placeholder = $('<div class="cerb-card-widget"/>').attr('data-widget-id', e.id).hide().appendTo($zone);
                $('<div/>').attr('id', 'cardWidget' + e.id + '_{$dict->id}').addClass('cerb-card-widget--content').appendTo($placeholder);

                async.series([ async.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
                    $popup.trigger('cerb-reorder');
                });
            })
        ;
        {/if}

        var loadWidgetFunc = function(widget_id, is_full, refresh_options, callback) {
            var $widget = $popup.find('.cerb-card-widget[data-widget-id=' + widget_id + '] .cerb-card-widget--content').fadeTo('fast', 0.3);

            Devblocks.getSpinner(true).prependTo($widget);

            var formData;

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
