{if $active_worker->hasPriv("contexts.{$peek_context}.comment")}
{$comment_div = uniqid('comment_editor_')}
<fieldset id="{$comment_div}" class="peek">
    <legend>
        <label>
            <input type="checkbox" name="comment_enabled" value="1">
            {'common.comment'|devblocks_translate|capitalize}
        </label>
    </legend>

    <div style="display:none;">
        <div class="cerb-code-editor-toolbar">
            <button type="button" title="Toggle formatting" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--formatting" data-format="html">Formatting on</button>

            <div class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;">
                <button type="button" title="Bold" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
                <button type="button" title="Italics" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
                <button type="button" title="Link" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
                <button type="button" title="Image" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
                <button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
                <button type="button" title="Quote" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
                <button type="button" title="Code" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
                <button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
            </div>

            <div class="cerb-code-editor-toolbar-divider"></div>

            <button type="button" title="Insert @mention" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--mention"><span class="glyphicons glyphicons-user-add"></span></button>
            <div class="cerb-code-editor-toolbar-divider"></div>

            <button type="button" title="Preview" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
        </div>

        <input type="hidden" name="comment_is_markdown" value="1">
        <textarea name="comment" placeholder="{'comment.notify.at_mention'|devblocks_translate}">{$model->comment}</textarea>

        <div class="cerb-comment-attachments">
            <button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
            <ul class="chooser-container bubbles"></ul>
        </div>
    </div>
</fieldset>

<script type="text/javascript">
$(function() {
    var $container = $('#{$comment_div}');
    var $form = $container.closest('form');

    // Drag/drop attachments
    var $attachments = $container.find('.cerb-comment-attachments');
    $attachments.cerbAttachmentsDropZone();

    // Editor
    var $editor = $container.find('textarea[name=comment]')
        .cerbTextEditor()
        .cerbTextEditorAutocompleteComments()
        .cerbTextEditorInlineImagePaster({
            attachmentsContainer: $attachments
        })
    ;

    // Toggle
    $container.find('input[name="comment_enabled"]')
        .on('click', function() {
            var $this = $(this);

            if ($this.is(':checked')) {
                $this.closest('legend').next('div').show();
                $editor.focus();
            } else {
                $this.closest('legend').next('div').hide();
            }
        })
    ;

    // Comment editor toolbar

    var $editor_toolbar = $container.find('.cerb-code-editor-toolbar')
        .cerbTextEditorToolbarMarkdown()
    ;

    // Formatting
    $editor_toolbar.find('.cerb-reply-editor-toolbar-button--formatting').on('click', function() {
        var $button = $(this);

        if('html' === $button.attr('data-format')) {
            $container.find('input:hidden[name=comment_is_markdown]').val('0');
            $button.attr('data-format', 'plaintext');
            $button.text('Formatting off');
            $editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','none');
        } else {
            $container.find('input:hidden[name=comment_is_markdown]').val('1');
            $button.attr('data-format', 'html');
            $button.text('Formatting on');
            $editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','inline-block');
        }
    });

    // Upload image
    $editor_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
        event.stopPropagation();

        var new_event = $.Event('cerb-chooser-save', {
            labels: event.labels,
            values: event.values
        });

        $container.find('button.chooser_file').triggerHandler(new_event);

        $editor.cerbTextEditor('insertText', '![Image](' + event.url + ')');
    });

    // Mention
    $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--mention').on('click', function () {
        var token = $editor.cerbTextEditor('getCurrentWord');

        if(token !== '@') {
            $editor.cerbTextEditor('insertText', '@');
        }

        $editor.autocomplete('search');
    });

    {if $pref_keyboard_shortcuts}
    // Save focus
    $editor.bind('keydown', 'ctrl+return meta+return alt+return', function(e) {
        e.preventDefault();
        $form.find('button.submit').focus();
    });

    // Save click
    $editor.bind('keydown', 'ctrl+shift+return meta+shift+return alt+shift+return', function(e) {
        e.preventDefault();
        $form.find('button.submit').click();
    });
    {/if}

    // Preview
    $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--preview').on('click', function () {
        var formData = new FormData();
        formData.append('c', 'profiles');
        formData.append('a', 'handleSectionAction');
        formData.append('section', 'comment');
        formData.append('action', 'preview');
        formData.append('comment', $container.find('textarea[name=comment]').val());
        formData.append('is_markdown', $container.find('input:hidden[name=comment_is_markdown]').val());

        genericAjaxPopup(
            'comment_preview',
            formData,
            'reuse',
            false
        );
    });

    // Attachments

    $container.find('button.chooser_file').each(function() {
        ajax.chooserFile(this,'comment_file_ids');
    });
});
</script>
{/if}