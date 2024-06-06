{$view_context = CerberusContexts::CONTEXT_RESOURCE}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
    <tr>
        <td nowrap="nowrap"><span class="title">{$view->name}</span></td>
        <td nowrap="nowrap" align="right" class="title-toolbar">
            {if $active_worker->is_superuser && $active_worker->hasPriv("contexts.{$view_context}.create")}<a title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
            <a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
            <a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
            <a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
            {if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
            <a data-cerb-worklist-icon-copy title="{'common.copy'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-duplicate"></span></a>
            <a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
            <input type="checkbox" class="select-all">
        </td>
    </tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
    <input type="hidden" name="view_id" value="{$view->id}">
    <input type="hidden" name="context_id" value="{$view_context}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="resource">
    <input type="hidden" name="action" value="">
    <input type="hidden" name="explore_from" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellpadding="5" cellspacing="0" border="0" width="100%" class="worklistBody">

        {* Column Headers *}
        <thead>
        <tr>
            {foreach from=$view->view_columns item=header name=headers}
                {* start table header, insert column title and link *}
                <th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
                    {if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
                        <a data-cerb-worklist-sort="{$header}">{$view_fields.$header->db_label|capitalize}</a>
                    {else}
                        <a style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
                    {/if}

                    {* add arrow if sorting by this column, finish table header tag *}
                    {if $header==$view->renderSortBy}
                        <span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
                    {/if}
                </th>
            {/foreach}
        </tr>
        </thead>

        {* Column Data *}
        {foreach from=$data item=result key=idx name=results}

            {if $smarty.foreach.results.iteration % 2}
                {$tableRowClass = "even"}
            {else}
                {$tableRowClass = "odd"}
            {/if}
            <tbody style="cursor:pointer;">
            <tr class="{$tableRowClass}">
                {foreach from=$view->view_columns item=column name=columns}
                    {if DevblocksPlatform::strStartsWith($column, "cf_")}
                        {include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
                    {elseif $column == "r_name"}
                        <td>
                            <input type="checkbox" name="row_id[]" value="{$result.r_id}" style="display:none;">
                            <a href="{devblocks_url}c=profiles&type=resource&id={$result.r_id}-{$result.r_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.r_name}</a>
                            <button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.r_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
                        </td>
                    {elseif in_array($column, ["r_is_dynamic"])}
                        <td data-column="{$column}">
                            {if !empty($result.$column)}{'common.yes'|devblocks_translate|lower}{else}{'common.no'|devblocks_translate|lower}{/if}
                        </td>
                    {elseif $column == "r_extension_id"}
                        <td>
                            {if array_key_exists($result.$column, $resource_extensions)}
                                {$resource_ext = $resource_extensions.{$result.$column}}
                                {$resource_ext->name}
                            {else}
                                {$result.$column}
                            {/if}
                        </td>
                    {elseif $column == "r_storage_size"}
                        <td>
                            {if $result.r_is_dynamic}
                                --
                            {else}
                                {$result.$column|devblocks_prettybytes}
                            {/if}
                        </td>
                    {elseif in_array($column, ["r_cache_until", "r_updated_at"])}
                        <td>
                            {if !empty($result.$column)}
                                <abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
                            {/if}
                        </td>
                    {else}
                        <td data-column="{$column}">{$result.$column}</td>
                    {/if}
                {/foreach}
            </tr>
            </tbody>
        {/foreach}
    </table>

    {if $total >= 0}
        <div style="padding-top:5px;">
            {include file="devblocks:cerberusweb.core::internal/views/view_paging.tpl" view=$view}

            <div style="float:left;" id="{$view->id}_actions">
                {$view_toolbar = $view->getToolbar()}
                {include file="devblocks:cerberusweb.core::internal/views/view_toolbar.tpl" view_toolbar=$view_toolbar}
                {if !$view_toolbar['explore']}<button type="button" class="action-always-show action-explore"><span class="glyphicons glyphicons-compass"></span> {'common.explore'|devblocks_translate|lower}</button>{/if}
            </div>
        </div>
    {/if}

    <div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        var $frm = $('#viewForm{$view->id}');

        {if $pref_keyboard_shortcuts}
        $frm.bind('keyboard_shortcut',function(event) {
            var $view_actions = $('#{$view->id}_actions');
            var hotkey_activated = true;

            switch(event.keypress_event.which) {
                case 101: // (e) explore
                    var $btn = $view_actions.find('button.action-explore');

                    if(event.indirect) {
                        $btn.select().focus();

                    } else {
                        $btn.click();
                    }
                    break;

                default:
                    hotkey_activated = false;
                    break;
            }

            if(hotkey_activated)
                event.preventDefault();
        });
        {/if}
    });
</script>
