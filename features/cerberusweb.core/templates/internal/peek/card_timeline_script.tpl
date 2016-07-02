// Timeline
var $timeline_fieldset = $popup.find('fieldset.cerb-peek-timeline');
var $timeline_pager = $popup.find('div.cerb-peek-timeline-pager');
var $timeline_preview = $popup.find('div.cerb-peek-timeline-preview').width($timeline_fieldset.width());

$timeline_fieldset.on('cerb-redraw', function() {
	// Spinner
	$timeline_preview.html('<span class="cerb-ajax-spinner"></span>');
	
	// Label
	$timeline_pager.find('span.cerb-peek-timeline-label').text('{'common.message'|devblocks_translate|capitalize} ' + ($timeline.index+1) + ' of ' + $timeline.length);
	
	// Pager
	if($timeline.objects.length <= 1) {
		$timeline_pager.hide();
	} else {
		$timeline_pager.show();
	}
	
	// Preview window
	if($timeline.objects.length == 0) {
		$timeline_fieldset.hide();
	} else {
		$timeline_fieldset.show();
	}
	
	// Buttons
	if($timeline.index == 0) {
		$timeline_pager.find('button.cerb-button-first').hide();
		$timeline_pager.find('button.cerb-button-prev').hide();
	} else {
		$timeline_pager.find('button.cerb-button-first').show();
		$timeline_pager.find('button.cerb-button-prev').show();
	}
	
	if($timeline.index == $timeline.last) {
		$timeline_pager.find('button.cerb-button-next').hide();
		$timeline_pager.find('button.cerb-button-last').hide();
	} else {
		$timeline_pager.find('button.cerb-button-next').show();
		$timeline_pager.find('button.cerb-button-last').show();
	}
	
	// Ajax update
	var $timeline_object = $timeline.objects[$timeline.index];
	
	if($timeline_object) {
		var context = $timeline_object.context;
		var context_id = $timeline_object.context_id;
		genericAjaxGet($timeline_preview, 'c=profiles&a=handleSectionAction&section=ticket&action=getPeekPreview&context=' + context + '&context_id=' + context_id);
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