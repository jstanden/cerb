<form data-cerb-compact-form style="min-width:440px;">
    <div class="cerb-u-mb-2 cerb-u-text-muted">
        Preview how compaction would shape this transcript's context. <b>Nothing is saved</b> — this shows what
        would be sent to the model. The summary text is generated only when applied; this is a structural preview.
    </div>

    <div class="cerb-fields-container cerb-u-mb-2">
        <div class="cerb-fields-container-item">
            <div class="cerb-field-cell-label">Summarize</div>
            <label class="cerb-ui-toggle"><input type="checkbox" name="summarize" value="1" checked><span class="cerb-ui-toggle--slider"></span></label>
        </div>
        <div class="cerb-fields-container-item">
            <div class="cerb-field-cell-label">context_ratio <span class="cerb-u-text-muted">(of window)</span></div>
            <input type="number" name="context_ratio" value="0.9" min="0" max="1" step="any" style="width:8em;">
        </div>
        <div class="cerb-fields-container-item">
            <div class="cerb-field-cell-label">tail_ratio <span class="cerb-u-text-muted">(of window)</span></div>
            <input type="number" name="tail_ratio" value="0.05" min="0" max="1" step="any" style="width:8em;">
        </div>
    </div>

    <div class="cerb-ui-toolbar-strip">
        <button type="submit" class="cerb-ui-button cerb-ui-button--primary"><span class="cerb-icons cerb-icon-archive"></span> Preview</button>
        <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="compact-cancel">{'common.cancel'|devblocks_translate|capitalize}</button>
    </div>

    <div data-cerb-compact-result class="cerb-u-mt-2"></div>
</form>
