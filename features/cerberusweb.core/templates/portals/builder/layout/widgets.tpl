{foreach from=$sections item=blocks key=section_key}
<div data-cerb-section-name="{$section_key}">
    {foreach from=$blocks item=block key=block_key}
        <div data-cerb-block-name="{$block_key}" style="flex:{$block.scale} {$block.scale} {$block.width};{if $block.scale}min-width:250px;{/if}{if in_array($block.align,['left','center','right','justify'])}text-align:{$block.align}{/if}">
            {foreach from=$block.widgets item=widget}
                <div data-cerb-widget-name="{$widget._key}" class="cerb-portal-widget--width-{$widget.width}">
                    {$widget._html nofilter}
                </div>
            {/foreach}
        </div>
    {/foreach}
</div>
{/foreach}
