{$element_id = uniqid('prompt_')}

<div class="cerb-form-builder-prompt cerb-form-builder-prompt-editor" id="{$element_id}">
    <h6>{$label}</h6>

    <div data-cerb-editor-toolbar class="cerb-code-editor-toolbar">
        {if $editor_has_toolbar}
            {DevblocksPlatform::services()->ui()->toolbar()->render($editor_toolbar)}
            <div class="cerb-code-editor-toolbar-divider"></div>
        {else}
            <button type="button" style="visibility:hidden;"></button>
        {/if}
    </div>

    <textarea name="prompts[{$var}]" data-editor-mode="{$editor_mode}" data-editor-lines="15" {if $editor_readonly}data-editor-readonly="true"{/if} {if !$editor_show_line_numbers}data-editor-line-numbers="false"{/if}>{$default}</textarea>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');
    var $editor_toolbar = $prompt.find('[data-cerb-editor-toolbar]')
    
    var $editor = $prompt.find('textarea[data-editor-mode]')
        .cerbCodeEditor()
                
        {if $editor_autocompletion == 'data_query'}
        .cerbCodeEditorAutocompleteDataQueries()
        {elseif $editor_autocompletion == 'search_query'}
        .cerbCodeEditorAutocompleteSearchQueries({
            "context": ""
        })
        {elseif $editor_mode == 'ace/mode/cerb_kata' && is_array($editor_autocompletion)}
        .cerbCodeEditorAutocompleteKata({
            autocomplete_suggestions: {$editor_autocompletion|json_encode nofilter}
        })
        {/if}
        
        .nextAll('pre.ace_editor')
    ;

    var editor = ace.edit($editor.attr('id'));
    
    {if editor_has_toolbar}
    $editor_toolbar.cerbToolbar({
        caller: {
            name: 'cerb.toolbar.interaction.worker.await.editor',
            params: {
            }
        },
        start: function(formData) {
            var pos = editor.getCursorPosition();
            
            formData.set('caller[params][selected_text]', editor.getSelectedText());
            formData.set('caller[params][cursor_row]', pos.row);
            formData.set('caller[params][cursor_column]', pos.column);
            formData.set('caller[params][value]', editor.getValue());
        },
        done: function(e) {
            e.stopPropagation();
            
            var $target = e.trigger;

            if (!$target.is('.cerb-bot-trigger'))
                return;

            if (e.eventData.exit === 'error') {
                // Show error
            } else if (e?.eventData?.exit === 'return') {
                Devblocks.interactionWorkerPostActions(e.eventData, editor);
            }
        },
        reset: function(e) {
            e.stopPropagation();
        },
        error: function(e) {
            e.stopPropagation();
        }
    });
    {/if}

    editor.focus();
});
</script>