<fieldset data-cerb-toolbar-help class="peek black" style="display:none;margin:10px 0 0 0;">
    {if $toolbar_ext}
    {include file="devblocks:cerberusweb.core::toolbars/editor_toolbar_help.tpl" toolbar_ext=$toolbar_ext}
    {/if}
</fieldset>

<fieldset data-cerb-toolbar-tester class="peek black" style="display:none;margin:10px 0 0 0;">
    <legend style="font-size:140%;">{'common.test'|devblocks_translate|capitalize}</legend>

    <div>
        <div data-cerb-toolbar-tester-editor-placeholders>
            <div class="cerb-code-editor-toolbar">
                <b>{'common.placeholders'|devblocks_translate|capitalize} (KATA)</b>
                <div class="cerb-code-editor-toolbar-divider"></div>
                <button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-play"></span></button>
            </div>
            <textarea name="tester[placeholders]" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
        </div>

        <div data-cerb-toolbar-tester-results style="margin-top:10px;position:relative;"></div>
    </div>
</fieldset>
