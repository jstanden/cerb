<span data-cerb-worklist-toolbar>
{$view_toolbar = $view->getToolbar()}
{if $view_toolbar}
    {DevblocksPlatform::services()->ui()->toolbar()->render($view_toolbar)}
{/if}
</span>
