<fieldset data-cerb-event-placeholders class="peek black" style="display:none;margin:10px 0 0 0;">
    {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_placeholders.tpl" trigger_inputs=$trigger_inputs}
</fieldset>

<fieldset data-cerb-event-tester class="peek black" style="display:none;margin:10px 0 0 0;">
    <legend>{'common.test'|devblocks_translate|capitalize}</legend>

    <div>
        <div data-cerb-event-tester-editor-placeholders>
            <div class="cerb-code-editor-toolbar">
                <b>{'common.placeholders'|devblocks_translate|capitalize} (KATA)</b>
                <div class="cerb-code-editor-toolbar-divider"></div>
                <button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-play"></span></button>
            </div>
            <textarea name="tester[placeholders]" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
        </div>

        <div data-cerb-event-tester-results style="margin-top:10px;position:relative;"></div>
    </div>
</fieldset>
