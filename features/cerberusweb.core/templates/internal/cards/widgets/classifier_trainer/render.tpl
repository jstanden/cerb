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

            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'classifier');
            formData.set('action', 'predict');
            formData.set('classifier_id', '{$classifier->id}');
            formData.set('text', $input.val());

            genericAjaxPost(formData, $output, '', function(json) {
                $input.select().focus();
            });
        }
    });

    $input.select().focus();
});
</script>