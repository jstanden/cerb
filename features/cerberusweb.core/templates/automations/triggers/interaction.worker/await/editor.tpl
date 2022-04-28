{$element_id = uniqid('prompt_')}

<div class="cerb-form-builder-prompt cerb-form-builder-prompt-editor" id="{$element_id}">
    <h6>{$label}</h6>

    <div class="cerb-code-editor-toolbar">

    </div>

    <textarea name="prompts[{$var}]" data-editor-mode="{$editor_mode}" data-editor-lines="15">{$default}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');

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
    // editor.setOption('highlightActiveLine', false);
    // editor.renderer.setOption('showGutter', false);

    editor.focus();
});
</script>