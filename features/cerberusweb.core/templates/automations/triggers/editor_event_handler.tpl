<div data-cerb-event-placeholders class="cerb-ui-panel cerb-ui-panel--spaced" style="display:none;">
    {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_placeholders.tpl" trigger_inputs=$trigger_inputs}
</div>

<div data-cerb-event-tester class="cerb-ui-panel cerb-ui-panel--spaced" style="display:none;">
    <div class="cerb-ui-header cerb-ui-header--tight">
        <div class="cerb-ui-header--title-sm">{'common.test'|devblocks_translate|capitalize}</div>
    </div>

    <div>
        <div data-cerb-event-tester-editor-placeholders>
            <div class="cerb-ui-editor-toolbar cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                <span class="cerb-u-text-muted cerb-u-fs-n1">{'common.placeholders'|devblocks_translate|capitalize} (KATA)</span>
                <button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-play"></span></button>
            </div>
            <textarea name="tester[placeholders]" data-editor-lines="6" spellcheck="false"></textarea>
        </div>

        <div data-cerb-event-tester-results style="margin-top:10px;position:relative;"></div>
    </div>
</div>
