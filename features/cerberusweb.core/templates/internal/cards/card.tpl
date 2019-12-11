{$div_id = uniqid('peek_')}
{$record_aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest)}

<div id="{$div_id}">
    {if $context_ext->hasOption('avatars')}
        <div style="float:left;margin-right:10px;">
            <img src="{devblocks_url}c=avatars&context={$peek_context}&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at|default:$dict->updated}" style="height:75px;width:75px;border-radius:5px;vertical-align:middle;">
        </div>
    {/if}

    <div style="float:left;">
        <h1>
            {$dict->_label}
        </h1>

        <div style="margin-top:5px;">
            {include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}

            {if $dict->id && $dict->record_url}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}

            {if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
                <button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
            {/if}

            {if !empty($dict->id) && $context_ext->hasOption('watchers')}
                {$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
                {include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full_label=true}
            {/if}

            {if $context_ext->hasOption('comments')}
                {if $active_worker->hasPriv("contexts.{$peek_context}.comment")}<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
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
<div class="cerb-button-add-widget" style="cursor:pointer;border:1px dashed rgb(220,220,220);padding:2px;text-align:center;" data-context="{CerberusContexts::CONTEXT_CARD_WIDGET}" data-context-id="0" data-edit="context:{$peek_context}" data-width="75%">
    <button style="background:none;color:rgb(150,150,150);" type="button"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(150,150,150);"></span> {'common.add.widget'|devblocks_translate|capitalize}</button>
</div>
{/if}

<script type="text/javascript">
$(function() {
    var $div = $('#{$div_id}');
    var $popup = genericAjaxPopupFind($div);
    var $layer = $popup.attr('data-layer');

    $popup.one('popup_open',function(event,ui) {
        $popup.dialog('option','title', "{$record_aliases.singular|capitalize|escape:'javascript' nofilter}");
        $popup.css('overflow', 'inherit');

        // Edit button
        {if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
        $popup.find('button.cerb-peek-edit')
            .cerbPeekTrigger({ 'view_id': '{$view_id}' })
            .on('cerb-peek-saved', function(e) {
                genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
            })
            .on('cerb-peek-deleted', function(e) {
                genericAjaxPopupClose($layer);
            })
        ;
        {/if}

        // Comments
        $popup.find('button.cerb-peek-comments-add')
            .cerbPeekTrigger()
            .on('cerb-peek-saved', function() {
                genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
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

        // Interactions
        var $interaction_container = $popup;
        {include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}

        var $add_button = $popup.find('.cerb-button-add-widget');

        // Drag
        {if $active_worker->is_superuser}
        $popup.find('.cerb-card-layout-zone--widgets')
            .sortable({
                tolerance: 'pointer',
                items: '.cerb-card-widget',
                helper: 'clone',
                placeholder: 'ui-state-highlight',
                forceHelperSize: true,
                forcePlaceholderSize: true,
                handle: '.cerb-card-widget--header .glyphicons-menu-hamburger',
                connectWith: '.cerb-card-layout-zone--widgets',
                opacity: 0.7,
                start: function(event, ui) {
                    $popup.find('.cerb-card-layout-zone--widgets')
                        .css('border', '2px dashed orange')
                        .css('background-color', 'rgb(250,250,250)')
                        .css('min-height', '100vh')
                    ;
                },
                stop: function(event, ui) {
                    $popup.find('.cerb-card-layout-zone--widgets')
                        .css('border', '')
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
            formData.append('c', 'profiles');
            formData.append('a', 'handleSectionAction');
            formData.append('section', 'card_widget');
            formData.append('action', 'reorderWidgets');
            formData.append('record_type', '{$peek_context}');

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
            var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : {};

            async.series([ async.apply(loadWidgetFunc, widget_id, false, refresh_options) ], function(err, json) {
                // Done
            });
        });

        var addEvents = function($target) {
            var $menu = $target.find('.cerb-card-widget--menu');
            var $menu_link = $target.find('.cerb-card-widget--link');
            var $handle = $target.find('.glyphicons-menu-hamburger');

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
                            genericAjaxPopup('export_widget', 'c=profiles&a=handleSectionAction&section=card_widget&action=exportWidget&id=' + widget_id, null, false);
                        }
                    }
                })
            ;

            $menu_link.on('click', function(e) {
                e.stopPropagation();
                $(this).closest('.cerb-card-widget').find('.cerb-card-widget--menu').toggle();
            });

            return $target;
        }

        $popup.find('.cerb-card-widget').each(function() {
            addEvents($(this));
        });

        var jobs = [];

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
            var $widget = $popup.find('.cerb-card-widget[data-widget-id=' + widget_id + '] .cerb-card-widget--content').empty();
            var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($widget);

            var request_url = 'c=profiles&a=handleSectionAction&section=card_widget&action=renderWidget&context={$peek_context}&context_id={$peek_context_id}&id='
                + encodeURIComponent(widget_id)
                + '&full=' + encodeURIComponent(is_full ? 1 : 0)
            ;

            if(typeof refresh_options == 'object')
                request_url += '&' + $.param(refresh_options);

            genericAjaxGet('', request_url, function(html) {
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
                callback();
            });
        };

        {foreach from=$zones item=zone}
        {foreach from=$zone item=widget}
        jobs.push(
            async.apply(loadWidgetFunc, {$widget->id|default:0}, false, {})
        );
        {/foreach}
        {/foreach}

        async.parallelLimit(jobs, 2, function(err, json) {});
    });
});
</script>
