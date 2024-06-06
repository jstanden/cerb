<div style="float:right;">
    {$fromRow = ($view->renderPage * $view->renderLimit) + 1}
    {$toRow = ($fromRow - 1) + $view->renderLimit}
    {$nextPage = $view->renderPage + 1}
    {$prevPage = $view->renderPage - 1}
    {$lastPage = ceil($total/$view->renderLimit)-1}

    {* Sanity checks *}
    {if $toRow > $total}{$toRow = $total}{/if}
    {if $fromRow > $toRow}{$fromRow = $toRow}{/if}

    {if $view->renderPage > 0}
        <a data-cerb-worklist-page-link="0">&lt;&lt;</a>
        <a data-cerb-worklist-page-link="{$prevPage}">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
    {/if}
    ({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
    {if $toRow < $total}
        <a data-cerb-worklist-page-link="{$nextPage}">{'common.next'|devblocks_translate|capitalize}&gt;</a>
        <a data-cerb-worklist-page-link="{$lastPage}">&gt;&gt;</a>
    {/if}
</div>