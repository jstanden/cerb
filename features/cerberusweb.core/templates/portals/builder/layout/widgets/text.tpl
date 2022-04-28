{if $widget_meta.label}
    <h3>
        {if $widget_meta.label_link}
            <a href="{$widget_meta.label_link}">{$widget_meta.label}</a>
        {else}
            {$widget_meta.label}
        {/if}
    </h3>
{/if}
{$widget_meta.content nofilter}