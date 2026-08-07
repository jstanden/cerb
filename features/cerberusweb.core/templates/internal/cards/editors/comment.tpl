{if $active_worker->hasPriv("contexts.{$peek_context}.comment")}
{$comment_div = uniqid('comment_editor_')}
{$is_html = !DAO_WorkerPref::get($active_worker->id,'comment_disable_formatting',0)}

<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$comment_div}" data-cerb-comment>
    <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
        <div class="cerb-ui-header--title-sm">
            <label class="cerb-ui-toggle">
                <input type="checkbox" name="comment_enabled" value="1">
                <span class="cerb-ui-toggle--slider"></span>
            </label>
            {'common.comment'|devblocks_translate|capitalize}
        </div>
    </div>

    <div data-cerb-comment-body style="display:none;">
        <input type="hidden" name="comment_is_markdown" value="{if $is_html}1{else}0{/if}">

        {* Built-in formatting + markdown/plaintext toggle come from the editor; this host section (mention + preview)
           merges in after the formatting buttons. *}
        <ul class="cerb-ui-toolbar" data-cerb-editor-toolbar hidden>
            <li data-value="mention" data-icon="mention" title="Insert @mention"></li>
            <li></li>
            <li data-value="preview" data-icon="eye-open" title="Preview"></li>
        </ul>

        <textarea name="comment" spellcheck="true" placeholder="{'comment.notify.at_mention'|devblocks_translate}">{if is_a($model, 'Model_Comment')}{$model->comment}{/if}</textarea>

        <div class="cerb-comment-attachments">
            <div class="cerb-ui-file-upload" data-name="comment_file_ids" data-multiple="1"></div>
        </div>
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $container = $('#{$comment_div}');
    let $form = $container.closest('form');

    // Attachments
    let fu = null;
    if(window.CerbUI && CerbUI.FileUpload)
        fu = new CerbUI.FileUpload($container.find('.cerb-ui-file-upload')[0], { name: 'comment_file_ids', multiple: true });

    if(!(window.CerbUI && CerbUI.MarkdownEditor))
        return;

    // Markdown editor (replaces the legacy cerbTextEditor stack) — the built-in toolbar provides formatting +
    // the markdown↔plaintext switcher. The host section adds @mention + Preview; onAction routes by value.
    let previewComment = function() {
        let formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'comment');
        formData.set('action', 'preview');
        formData.set('comment', ed.getValue());
        formData.set('is_markdown', $container.find('input:hidden[name=comment_is_markdown]').val());
        genericAjaxPopup('comment_preview', formData, 'reuse', false);
    };

    let ed = new CerbUI.MarkdownEditor($container.find('textarea[name=comment]')[0], {
        mode: {if $is_html}'markdown'{else}'plaintext'{/if},
        onAutocomplete: CerbUI.MarkdownEditor.mentionSource(),
        onImage: function(info) {
            // Add the uploaded/chosen file to the attachments component
            if(fu) fu.add([{ id: info.file_id, name: info.file_name }]);
            // A pasted image enables markdown mode — sync the toolbar switcher/flag/format buttons
            if(ed._editorToolbar) ed._editorToolbar.setMode('markdown');
        },
        toolbar: {
            // The switcher also keeps the comment_is_markdown flag in lockstep.
            onMode: function(v) {
                $container.find('input:hidden[name=comment_is_markdown]').val(v === 'markdown' ? '1' : '0');
            },
            sections: [ $container.find('[data-cerb-editor-toolbar]')[0] ],
            onAction: function(value, ed) {
                if(value === 'mention') { ed.insertText('@'); ed.openAutocomplete(); return true; }
                if(value === 'preview') { previewComment(); return true; }
                return false; // bold/italic/… run their built-in
            }
        }
    });

    // Toggle (reveal/focus the editor when the comment toggle is enabled)
    if(CerbUI.Toggle) {
        let comment_toggle_el = $container.find('input[name="comment_enabled"]').closest('.cerb-ui-toggle')[0];

        if(comment_toggle_el) {
            new CerbUI.Toggle(comment_toggle_el, {
                onChange: function(checked) {
                    let $body = $container.find('[data-cerb-comment-body]');

                    if(checked) {
                        $body.show();
                        ed.focus();
                    } else {
                        $body.hide();
                    }
                }
            });
        }
    }

    {if $pref_keyboard_shortcuts}
    let $editor_input = $container.find('textarea[name=comment]');

    // Save focus
    $editor_input.bind('keydown', 'ctrl+return meta+return alt+return', function(e) {
        e.preventDefault();
        $form.find('button.submit').focus();
    });

    // Save click
    $editor_input.bind('keydown', 'ctrl+shift+return meta+shift+return alt+shift+return', function(e) {
        e.preventDefault();
        $form.find('button.submit').click();
    });
    {/if}
});
</script>
{/if}