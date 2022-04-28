{function menu_render level=0 menu_items=[]}
    {foreach from=$menu_items item=menu_item}
        {if !$menu_item.hidden}
            {if 'page' == $menu_item.type}
                <li>
                    <a href="{devblocks_url}{/devblocks_url}{$menu_item.path|ltrim:'/'}">{$menu_item.label}</a>
                </li>
            {elseif 'menu' == $menu_item.type}
                <li>
                    <a href="javascript:;">{$menu_item.label}</a>
                    <ul class="cerb-portal-menu--submenu">
                        {menu_render menu_items=$menu_item.items}
                    </ul>
                </li>
            {/if}
        {/if}
    {/foreach}
{/function}

<nav>
    <ul class="cerb-portal-menu">
        {* [TODO] Selected *}
        {* [TODO] class="selected" *}
        {menu_render menu_items=$menu}
    </ul>
</nav>

{$script_id = uniqid('script_')}
<script id="{$script_id}" type="text/javascript">
/*
$$.ready(function() {
    var $script = document.querySelector('#{$script_id}');
    var $nav = $script.parentElement.querySelector('nav.cerb-portal-menu');
    var $submenus = $nav.querySelectorAll('ul.cerb-portal-menu--submenu');
    
    // [TODO] Hide menus on mouseout
    $$.forEach($submenus, function(index, $el) {
        $el.previousElementSibling.addEventListener('mouseover', function(e) {
            e.stopPropagation();
            $el.style.display = 'block';
        });

        $el.closest('li').addEventListener('mouseout', function(e) {
            $el.style.display = 'none';
        });
    });
});
*/
</script>