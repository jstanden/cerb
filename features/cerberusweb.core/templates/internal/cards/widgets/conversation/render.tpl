<div class="cerb-peek-timeline-pager">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="40%" align="right" nowrap="nowrap">
                <button type="button" class="cerb-button-first"><span class="glyphicons glyphicons-fast-backward"></span></button>
                <button type="button" class="cerb-button-prev"><span class="glyphicons glyphicons-step-backward"></span></button>
            </td>
            <td width="20%" align="center" nowrap="nowrap" style="font-weight:bold;font-size:1.2em;padding:0px 10px;">
                <span class="cerb-peek-timeline-label"></span>
            </td>
            <td width="40%" align="left" nowrap="nowrap">
                <button type="button" class="cerb-button-next"><span class="glyphicons glyphicons-step-forward"></span></button>
                <button type="button" class="cerb-button-last"><span class="glyphicons glyphicons-fast-forward"></span></button>
            </td>
        </tr>
    </table>
</div>

<div style="overflow:auto;">
    <fieldset class="peek cerb-peek-timeline" style="margin:0;padding:0;border:0;">
        <div class="cerb-peek-timeline-preview" style="margin:0;">
            {include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
        </div>
    </fieldset>
</div>

<script type="text/javascript">
$(function() {
    var $timeline = {$timeline_json|default:'{}' nofilter};

    // Timeline
    var $widget = $('#cardWidget{$widget->getUniqueId($dict->record_id)}');
    var $timeline_fieldset = $widget.find('fieldset.cerb-peek-timeline');
    var $timeline_pager = $widget.find('div.cerb-peek-timeline-pager');
    var $timeline_preview = $widget.find('div.cerb-peek-timeline-preview');

    $timeline_fieldset.on('cerb-redraw', function() {
        // Spinner
        $timeline_preview.empty().append(Devblocks.getSpinner());

        // Label
        $timeline_pager.find('span.cerb-peek-timeline-label').text('{'common.message'|devblocks_translate|capitalize} ' + ($timeline.index+1) + ' of ' + $timeline.length);

        // Pager
        if($timeline.objects.length <= 1) {
            $timeline_pager.hide();
        } else {
            $timeline_pager.show();
        }

        // Preview window
        if($timeline.objects.length === 0) {
            $timeline_fieldset.hide();
        } else {
            $timeline_fieldset.show();
        }

        // Buttons
        if($timeline.index === 0) {
            $timeline_pager.find('button.cerb-button-first').hide();
            $timeline_pager.find('button.cerb-button-prev').hide();
            $timeline_pager.find('button.cerb-button-next').focus();
        } else {
            $timeline_pager.find('button.cerb-button-first').show();
            $timeline_pager.find('button.cerb-button-prev').show();
        }

        if($timeline.index === $timeline.last) {
            $timeline_pager.find('button.cerb-button-next').hide();
            $timeline_pager.find('button.cerb-button-last').hide();
            $timeline_pager.find('button.cerb-button-prev').focus();
        } else {
            $timeline_pager.find('button.cerb-button-next').show();
            $timeline_pager.find('button.cerb-button-last').show();
        }

        // Ajax update
        var $timeline_object = $timeline.objects[$timeline.index];

        if($timeline_object) {
            var context = $timeline_object.context;
            var context_id = $timeline_object.context_id;
            genericAjaxGet($timeline_preview, 'c=profiles&a=invoke&module=ticket&action=getPeekPreview&context=' + context + '&context_id=' + context_id + '&view_id={$view_id}');
        }
    });

    $timeline_pager.find('button.cerb-button-first').click(function() {
        $timeline.index = 0;
        $timeline_fieldset.trigger('cerb-redraw');
    });

    $timeline_pager.find('button.cerb-button-prev').click(function() {
        $timeline.index = Math.max(0, $timeline.index - 1);
        $timeline_fieldset.trigger('cerb-redraw');
    });

    $timeline_pager.find('button.cerb-button-next').click(function() {
        $timeline.index = Math.min($timeline.last, $timeline.index + 1);
        $timeline_fieldset.trigger('cerb-redraw');
    });

    $timeline_pager.find('button.cerb-button-last').click(function() {
        $timeline.index = $timeline.last;
        $timeline_fieldset.trigger('cerb-redraw');
    });

    $timeline_fieldset.trigger('cerb-redraw');
});
</script>