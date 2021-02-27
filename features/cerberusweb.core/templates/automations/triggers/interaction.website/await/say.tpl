<div class="cerb-interaction-popup--form-elements-say {if 'error' == $style}cerb-interaction-popup--form-elements-say-error{/if}">
    {if !in_array($format, ['markdown','html'])}
        {$message|escape|nl2br nofilter}
    {else}
        {$message nofilter}
    {/if}
</div>