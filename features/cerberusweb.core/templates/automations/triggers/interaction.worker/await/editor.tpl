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

    {if $editor_autocompletion == 'data_query'}
    <textarea id="dq_{$element_id}" name="prompts[{$var}]" data-editor-lines="15" {if $editor_readonly}data-editor-readonly{/if} spellcheck="false">{$default}</textarea>
    {elseif $editor_autocompletion == 'search_query'}
    <div class="cerb-ui-searchquery" id="sq_{$element_id}">
        <span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
        <div class="cerb-ui-searchquery--field">
            <div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
            <textarea class="cerb-ui-searchquery--input" name="prompts[{$var}]" rows="1" {if $editor_readonly}readonly="readonly"{/if} spellcheck="false">{$default}</textarea>
            <span class="cerb-ui-searchquery--caret-anchor"></span>
        </div>
        <div class="cerb-ui-searchquery--right">
            <a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
        </div>
    </div>
    {elseif $syntax == 'json'}
    <textarea name="prompts[{$var}]" data-editor-lines="15" {if $editor_readonly}data-editor-readonly{/if} spellcheck="false">{$default}</textarea>
    {elseif $syntax == 'kata'}
    <textarea name="prompts[{$var}]" data-editor-lines="15" {if $editor_readonly}data-editor-readonly{/if} spellcheck="false">{$default}</textarea>
    {elseif $syntax == 'markdown'}
    <textarea name="prompts[{$var}]" data-editor-lines="15" {if $editor_readonly}data-editor-readonly{/if} spellcheck="true">{$default}</textarea>
    {else}
    {* html / text / yaml — ScriptingEditor: monospace + inline Twig/KataScript tag highlighting, no outer-language coloring *}
    <textarea name="prompts[{$var}]" data-editor-lines="15" {if $editor_readonly}data-editor-readonly{/if} spellcheck="false">{$default}</textarea>
    {/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');
    var $editor_toolbar = $prompt.find('[data-cerb-editor-toolbar]')

    {if $editor_autocompletion == 'data_query'}
    var editor = new CerbUI.DataQuery($prompt.find('#dq_{$element_id}')[0], {
        onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource()
    });
    {elseif $editor_autocompletion == 'search_query'}
    var editor = new CerbUI.SearchQuery($prompt.find('.cerb-ui-searchquery')[0], {
        onAutocomplete: CerbUI.SearchQuery.queryFieldSource("{$record_type}"),
        context: "{$record_type}"
    });
    $prompt.find('.cerb-ui-searchquery [data-action=autocomplete]').on('click', function() { editor.openAutocomplete(); });
    {elseif $syntax == 'json'}
    var editor = new CerbUI.JsonEditor($prompt.find('textarea[name="prompts[{$var}]"]')[0], { readOnly: {if $editor_readonly}true{else}false{/if}, validate: true });
    {elseif $syntax == 'kata'}
    var kataOpts = { readOnly: {if $editor_readonly}true{else}false{/if} };
    {if is_array($editor_autocompletion)}
    kataOpts.onAutocomplete = CerbUI.KataEditor.kataFieldSource({$editor_autocompletion|json_encode nofilter});
    {/if}
    var editor = new CerbUI.KataEditor($prompt.find('textarea[name="prompts[{$var}]"]')[0], kataOpts);
    {elseif $syntax == 'markdown'}
    var editor = new CerbUI.MarkdownEditor($prompt.find('textarea[name="prompts[{$var}]"]')[0], { readOnly: {if $editor_readonly}true{else}false{/if} });
    {else}
    var editor = new CerbUI.ScriptingEditor($prompt.find('textarea[name="prompts[{$var}]"]')[0], { readOnly: {if $editor_readonly}true{else}false{/if}, gutter: {if $editor_show_line_numbers}true{else}false{/if} });
    {/if}

    {if editor_has_toolbar}
    let editor_toolbar_ul = $editor_toolbar.find('ul.cerb-ui-toolbar')[0];
    if(editor_toolbar_ul && window.CerbUI && CerbUI.Toolbar)
    new CerbUI.Toolbar(editor_toolbar_ul, {
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

    {if !$is_automation_simulated|default:false}
    editor.focus();
    {/if}
});
</script>
