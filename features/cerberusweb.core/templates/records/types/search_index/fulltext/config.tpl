{$div_uid = uniqid('fieldset')}
<div id="{$div_uid}">
    {capture assign="ph_content"}e.g. {literal}{{content}}{/literal}{/capture}
    {capture assign="ph_boost"}e.g. {literal}{{title}}{/literal}{/capture}

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">Indexing</div>
        </div>

        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">Index records matching this query <span class="cerb-ui-form--hint">(blank for all)</span></label>
                <div class="cerb-ui-searchquery" id="recordQuery_{$div_uid}">
                    <span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
                    <div class="cerb-ui-searchquery--field">
                        <div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
                        <textarea name="params[record_query]" class="cerb-ui-searchquery--input" rows="1" spellcheck="false">{$model->extension_params.record_query}</textarea>
                        <span class="cerb-ui-searchquery--caret-anchor"></span>
                    </div>
                    <div class="cerb-ui-searchquery--right">
                        <a data-action="worklist" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Build from a worklist…"><span class="cerb-icons cerb-icon-table"></span></a>
                        <a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
                    </div>
                </div>
            </div>

            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">Using this record content template</label>
                {include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="params[content]" value=$model->extension_params.content context=$model->record_type placeholder=$ph_content lines=8}
            </div>

            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">Boost terms using this template</label>
                {include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="params[content_boost]" value=$model->extension_params.content_boost context=$model->record_type placeholder=$ph_boost lines=4}
            </div>
        </div>
    </div>

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">{'common.queries'|devblocks_translate|capitalize}</div>
        </div>

        <div class="cerb-ui-form--field">
            <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                <label class="cerb-ui-toggle">
                    <input type="checkbox" name="params[wildcards_disable]" id="wildcardsDisable_{$div_uid}" value="1" {if $model->extension_params.wildcards_disable}checked="checked"{/if}>
                    <span class="cerb-ui-toggle--slider"></span>
                </label>
                <label for="wildcardsDisable_{$div_uid}">Disable wildcards (*)</label>
            </div>
        </div>
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $div = $('#{$div_uid}');
    let $extension_params = $div.closest('.search-index-params');

    // Record-query editor (inline CerbUI.SearchQuery with field autocomplete). The old click-to-open
    // worklist popup is now an OPTIONAL toolbar button, not the forced path.
    let currentContext = '{$model->record_type}';
    let sqEl = $div.find('.cerb-ui-searchquery')[0];
    let recordQuerySq = null;

    if(sqEl && window.CerbUI && CerbUI.SearchQuery) {
        recordQuerySq = new CerbUI.SearchQuery(sqEl, {
            context: currentContext,
            onAutocomplete: CerbUI.SearchQuery.queryFieldSource(currentContext)
        });

        let acBtn = sqEl.querySelector('[data-action=autocomplete]');
        if(acBtn) acBtn.addEventListener('click', function() { recordQuerySq.openAutocomplete(); });

        // Optional path: open the worklist chooser, then pull its quick-search query back into the editor.
        let wlBtn = sqEl.querySelector('[data-action=worklist]');
        if(wlBtn) wlBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if(!currentContext) return;

            let q = recordQuerySq.getValue();
            let width = $(window).width() - 100;
            let $chooser = genericAjaxPopup('chooser' + Devblocks.uniqueId(),
                'c=internal&a=invoke&module=records&action=chooserOpenParams&context=' + encodeURIComponent(currentContext) + '&q=' + encodeURIComponent(q),
                null, true, width);

            $chooser.on('chooser_save', function(event) {
                recordQuerySq.setValue(event.worklist_quicksearch || '');
                recordQuerySq.focus();
            });
        });
    }

    // On record type change
    $extension_params.on('cerb-search-index-params-change-context', function(e, context) {
        e.stopPropagation();

        // Repoint the record-query editor + each inline template editor to the new record type
        currentContext = context;
        if(recordQuerySq) recordQuerySq.setContext(context);

        $div.find('.cerb-ui-template-field').each(function() {
            if(this.cerbTemplateField) this.cerbTemplateField.setContext(context);
        });
    });
});
</script>
