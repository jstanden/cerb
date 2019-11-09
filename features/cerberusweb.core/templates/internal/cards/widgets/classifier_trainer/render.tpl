{if $is_writeable && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE}.import")}
<div style="margin-bottom:10px;">

</div>
{/if}

<input type="text" class="expression-tester" style="width:95%;" autocomplete="off" spellcheck="false" autofocus="autofocus" placeholder="Enter some text and press ENTER for a classification prediction">

<div class="output" style="margin:5px;"></div>

<script type="text/javascript">
$(function() {
    var $widget = $('#cardWidget{$widget->getUniqueId($classifier->id)}');

    // Test classifier
    var $input = $widget.find('INPUT.expression-tester');
    var $output = $widget.find('DIV.output');

    $input.on('keyup', function(e) {
        e.stopPropagation();
        var keycode = e.keyCode || e.which;

        if(13 == keycode) {
            e.preventDefault();

            genericAjaxGet($output, 'c=profiles&a=handleSectionAction&section=classifier&action=predict&classifier_id={$classifier->id}&text=' + encodeURIComponent($input.val()), function(json) {
                $input.select().focus();
            });
        }
    });

    $input.select().focus();
});
</script>