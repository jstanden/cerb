{$field_uniqid = uniqid('cfield_')}
<div id="{$field_uniqid}">
    <div class="cerb-delta-slider-container" style="margin-left:10px;">
        <input type="hidden" name="{$form_key}" value="{$form_value|default:0}">
        <div class="cerb-delta-slider {if $form_value < $value_mid}cerb-slider-green{elseif $form_value > $value_mid}cerb-slider-red{else}cerb-slider-gray{/if}" title="{$form_value}">
            <span class="cerb-delta-slider-midpoint"></span>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function() {
	var $cfield = $('#{$field_uniqid}');

    $cfield.find('div.cerb-delta-slider').each(function() {
        var $this = $(this);
        var $input = $this.siblings('input:hidden');

        $this.slider({
            disabled: false,
            value: $input.val(),
            min: {$value_min},
            max: {$value_max},
            step: 1,
            range: 'min',
            slide: function(event, ui) {
                $this.removeClass('cerb-slider-gray cerb-slider-red cerb-slider-green');

                if(ui.value < {$value_mid}) {
                    $this.addClass('cerb-slider-green');
                    $this.slider('option', 'range', 'min');
                } else if(ui.value > {$value_mid}) {
                    $this.addClass('cerb-slider-red');
                    $this.slider('option', 'range', 'max');
                } else {
                    $this.addClass('cerb-slider-gray');
                    $this.slider('option', 'range', false);
                }

                $this.attr('title', ui.value);
            },
            stop: function(event, ui) {
                $input.val(ui.value);
            }
        });
    });
});
</script>