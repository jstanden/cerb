{$ul_uniqid = uniqid('values_')}
<ul id="{$ul_uniqid}" class="bubbles">
    {foreach from=$target_dicts item=target_dict}
        <li>
            {* [TODO] Avatars *}
            <a href="javascript:;" data-context="{$target_dict->_context}" data-context-id="{$target_dict->id}">{$target_dict->_label}</a>
        </li>
    {/foreach}
</ul>

<script type="text/javascript">
$(function() {
    let $ul = $('#{$ul_uniqid}');
    $ul.find('a[data-context]').cerbPeekTrigger();
});
</script>