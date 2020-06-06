{$widget_uniqid = uniqid('cardWidget')}
<div id="cardWidget{$widget_uniqid}">
    <form action="{devblocks_url}{/devblocks_url}" method="POST" class="cerb-form-builder" onsubmit="return false;">
        {$widget_ext->renderForm($widget, $dict, $is_refresh)}
    </form>
</div>

<script type="text/javascript">
$(function() {
    var $widget = $('#cardWidget{$widget_uniqid}');
    var $form = $widget.find('> form');
    var $popup = genericAjaxPopupFind($widget);

    $form.on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        $form.triggerHandler('cerb-form-builder-submit');
        return false;
    });

    $form.on('cerb-form-builder-submit', function(e) {
        e.stopPropagation();

        // Grab the entire form params
        var formData = new FormData($form.get(0));

        var evt = $.Event('cerb-widget-refresh');
        evt.widget_id = {$widget->id};
        evt.refresh_options = formData;

        $popup.triggerHandler(evt);
    });

    $form.on('cerb-form-builder-reset', function(e) {
        e.stopPropagation();

        var evt = $.Event('cerb-widget-refresh');
        evt.widget_id = {$widget->id};
        evt.refresh_options = {
            'reset': 1
        };

        $popup.triggerHandler(evt);
    });

    $form.find('input[type=text],textarea').first().focus();
});
</script>
