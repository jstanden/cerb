{if DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')}
{if !empty($va_behaviors)}
    <script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
        {if $va_actions.jquery_scripts}
        {
            {foreach from=$va_actions.jquery_scripts item=jquery_script}
            try {
                {$jquery_script nofilter}
            } catch(e) { }
            {/foreach}

            let $view = $('div#view{$view->id}');
            let $va_actions = $('#view{$view->id}_va_actions');
            let $va_button = $('<a title="This worklist was modified by bots"><div style="background-color:var(--cerb-color-background-contrast-230);display:inline-block;margin-top:3px;border-radius:11px;padding:2px;"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:14px;height:14px;margin:0;"></div></a>');
            $va_button.click(function(e) {
                e.stopPropagation();
                let $va_action_log = $('#view{$view->id}_va_actions');
                if($va_action_log.is(':hidden')) {
                    $va_action_log.fadeIn();
                } else {
                    $va_action_log.fadeOut();
                }
            });
            $va_button.insertAfter($view.find('TABLE.worklist SPAN.title'));

            $va_actions.find('button.cancel').on('click', function(e) {
                e.stopPropagation();
                $(this).closest('div.block').fadeOut();
            });

            $va_actions.insertAfter($view.find('TABLE.worklist'));
        }
        {/if}
    </script>

    <div class="block" style="display:none;margin:5px;" id="view{$view->id}_va_actions">
        <b>This worklist was modified by bots:</b>

        <div style="padding:10px;">
            <ul class="bubbles">
                {foreach from=$va_behaviors item=bot_behavior name=bot_behaviors}
                    {$bot = $bot_behavior->getBot()}
                    <li>
                        <img src="{devblocks_url}c=avatars&context=bot&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}" class="cerb-avatar">
                        <a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$bot_behavior->id}" data-profile-url="{devblocks_url}c=profiles&a=behavior&id={$bot_behavior->id}{/devblocks_url}">{$bot_behavior->title}</a>
                    </li>
                {/foreach}
            </ul>
        </div>

        <button type="button" class="cancel">{'common.ok'|devblocks_translate|upper}</button>
    </div>
{/if}
{/if}