{$div_id = uniqid('preview')}

<div id="{$div_id}" class="{$css_class|default:'emailBodyHtml'}" dir="auto">
{$content nofilter}
</div>

{if !$is_inline}
<script type="text/javascript">
$(function() {
    var $div = $('#{$div_id}');
    var $popup = genericAjaxPopupFind($div);

    $popup.one('popup_open',function() {
        $popup.dialog('option','title', "{'common.preview'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
        $popup.css('overflow', 'inherit');
    });
});
</script>
{/if}