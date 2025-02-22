<div class="cerb-interaction-popup--form-elements-say {if $error_style}cerb-interaction-popup--form-elements-say--style-error{/if} {if $styles && is_array($styles)}{foreach from=$styles item=style}cerb-interaction-style--{$style}{/foreach}{/if}">
    {if !in_array($format, ['markdown','html'])}
        {$message|escape|nl2br nofilter}
    {else}
        {$message nofilter}
    {/if}
</div>