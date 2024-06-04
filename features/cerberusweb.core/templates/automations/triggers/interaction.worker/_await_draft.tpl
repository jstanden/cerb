<div>
    {include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
    <h4 style="display:inline-block;">Waiting for the draft to finish...</h4>
    <input type="hidden" name="prompts[draft]" value="">
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
    $(function() {
        var $script = $('#{$script_uid}');
        var $form = $script.closest('form');
        var $hidden = $script.prev('div').find('input:hidden');
        
        var formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'draft');
        formData.set('action', 'resume');
        formData.set('draft_id', '{$draft->id}');

        var $popup_draft = genericAjaxPopup('draft', formData, null, false, '80%');
        
        // Send
        
        $popup_draft.on('cerb-compose-sent', function(e) {
            e.stopPropagation();
            $hidden.val('compose.sent');

            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
        
        $popup_draft.on('cerb-reply-sent cerb-reply-saved', function(e) {
            e.stopPropagation();
            $hidden.val('reply.sent');

            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
        
        // Draft/save
        
        $popup_draft.on('cerb-compose-draft', function(e) {
            e.stopPropagation();
            $hidden.val('compose.draft');
            
            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
        
        $popup_draft.on('cerb-reply-draft', function(e) {
            e.stopPropagation();
            $hidden.val('reply.draft');
            
            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
        
        // Discard
        
        $popup_draft.on('cerb-compose-discard', function(e) {
            e.stopPropagation();
            $hidden.val('compose.discard');

            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
        
        $popup_draft.on('cerb-reply-discard', function(e) {
            e.stopPropagation();
            $hidden.val('reply.discard');

            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
       
        // Window close

        // (x) close button
        $popup_draft.closest('.ui-dialog').find('.ui-dialog-titlebar-close').on('click', function(e) {
            e.stopPropagation();
            $hidden.val('compose.discard');

            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });

        // ESC key
        $popup_draft.on('cerb-peek-aborted peek_aborted', function(e) {
            e.stopPropagation();
            $hidden.val('compose.discard');

            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        });
    });
</script>