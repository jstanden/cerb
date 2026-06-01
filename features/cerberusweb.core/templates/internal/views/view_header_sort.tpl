{* add arrow if sorting by this column, finish table header tag *}
{if $header==$view->renderSortBy}
    <span class="cerb-icons {if $view->renderSortAsc}cerb-icon-sort-asc{else}cerb-icon-sort-desc{/if}" style="font-size:14px;{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
{/if}