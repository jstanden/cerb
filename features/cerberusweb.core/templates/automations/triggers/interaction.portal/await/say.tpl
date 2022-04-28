<div class="cerb-interaction-panel--form-elements-say {if 'error' == $style}cerb-interaction-panel--form-elements-say-error{/if}">
    {if !in_array($format, ['markdown','html'])}
        {$message|escape|nl2br nofilter}
    {else}
        {$message nofilter}
    {/if}
</div>