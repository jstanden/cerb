{$element_id = uniqid('response_')}
<div class="cerb-form-builder-prompt cerb-form-builder-response-map" id="{$element_id}">
    {DevblocksPlatform::services()->ui()->map()->render($map)}
</div>