<legend>Routing Rules:</legend>

<div style="display:flex;flex-flow:row wrap;gap:5px;">
    {if $routing_rules}
        {foreach from=$routing_rules item=routing_rule}
            <div class="block" style="display:inline-block;padding:0.5em 1em;">
                <h3 style="text-decoration:underline;cursor:pointer;{if $routing_rule->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.mail.routing.rule" data-context-id="{$routing_rule->id}" data-edit="true">{$routing_rule->name}</h3>
            </div>
        {/foreach}
    {/if}

    <div class="block" style="display:inline-block;padding:0.5em 1em;">
        <h3 style="text-decoration:none;cursor:pointer;{if $routing_rule->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.mail.routing.rule" data-context-id="0" data-edit="true"><span class="cerb-icons cerb-icon-circle-plus"></span></h3>
    </div>
</div>

{$script_uid = uniqid('script')}

<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
    $(function() {
        let $script = $('#{$script_uid}');
        let $routing_rules = $script.closest('fieldset');

        $routing_rules.find('[data-context]')
            .cerbPeekTrigger({
                'width': '80%'
            })
            .on('cerb-peek-saved cerb-peek-deleted', function(e) {
                e.stopPropagation();

                var formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'mail_routing_rule');
                formData.set('action', 'refreshRules');
                formData.set('event_id', '{$event_id}');

                genericAjaxPost(formData, $routing_rules);
            })
        ;
    });
</script>