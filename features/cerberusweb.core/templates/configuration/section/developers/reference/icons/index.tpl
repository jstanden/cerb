<h2>Icon Reference (Built-in)</h2>

<div style="column-width:200px;margin-bottom:20px;">
    {foreach from=$icons_cerb item=icon}
        <div>
            <span class="cerb-icons cerb-icon-{$icon}" title=".cerb-icon-{$icon}" style="font-size:200%;margin:5px;"></span>
            <span>{$icon}</span>
        </div>
    {/foreach}
</div>