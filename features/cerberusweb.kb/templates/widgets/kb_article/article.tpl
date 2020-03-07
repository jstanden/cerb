{$height = $widget->extension_params.height|round}

<div class="cerb-kb-article-content" style="{if $height}max-height:{$height}px;overflow:auto;{/if}">
{$article->getContentProtected() nofilter}
</div>