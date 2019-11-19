{$div_id = "comment{uniqid()}"}

<div id="{$div_id}">
    {if in_array($model->context,[CerberusContexts::CONTEXT_DRAFT,CerberusContexts::CONTEXT_MESSAGE])}
    {include file="devblocks:cerberusweb.core::internal/comments/note.tpl" note=$model}
    {else}
    {include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" comment=$model}
    {/if}

    <div>
        <button type="button" data-cerb-button-action="close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        var $div = $('#{$div_id}');
        var $popup = genericAjaxPopupFind($div);

        $popup.one('popup_open',function(event,ui) {
            $popup.dialog('option','title', "{'common.preview'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            $popup.find('button[data-cerb-button-action=close]').on('click', function() {
                genericAjaxPopupClose($popup);
            });
        });
    });
</script>
