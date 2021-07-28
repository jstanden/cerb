<div>
    {include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
    <h4 style="display:inline-block;">Waiting for the record editor to finish...</h4>
    <input type="hidden" name="prompts[event]" value="">
    <input type="hidden" name="prompts[record_type]" value="">
    <input type="hidden" name="prompts[record_id]" value="">
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
    $(function() {
        var $script = $('#{$script_uid}');
        var $form = $script.closest('form');
        var $hidden_event = $script.prev('div').find('input:hidden[name="prompts[event]"]');
        var $hidden_record_type = $script.prev('div').find('input:hidden[name="prompts[record_type]"]');
        var $hidden_record_id = $script.prev('div').find('input:hidden[name="prompts[record_id]"]');
        
        var $trigger = $('<div/>')
            .attr('data-context', '{$context_ext->id}')
            .attr('data-context-id', '{$record_id}')
            .attr('data-edit', '')
            .cerbPeekTrigger()
            .on('cerb-peek-saved cerb-peek-deleted cerb-peek-aborted', function(e) {
                e.stopPropagation();
                
                if(e.type === 'cerb-peek-saved') {
                    $hidden_event.val(e.is_new ? 'record.created' : 'record.updated');
                    $hidden_record_type.val(e.context);
                    $hidden_record_id.val(e.id);
                } else if(e.type === 'cerb-peek-deleted') {
                    $hidden_event.val('record.deleted');
                    $hidden_record_type.val(e.context);
                    $hidden_record_id.val(e.id);
                } else if(e.type === 'cerb-peek-aborted') {
                    $hidden_event.val('record.aborted');
                }

                var evt = $.Event('cerb-form-builder-submit');
                $form.triggerHandler(evt);
                
                $trigger.remove();
            })
            .click()
        ;
    });
</script>