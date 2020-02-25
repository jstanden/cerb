var $package_library = $library_container.find('.package-library');
var $package_chooser = $package_library.find('.package-library--package-chooser');
var $package_chooser_search = $package_library.find('.package-library--package-search');
var $package_info = $package_library.find('.package-library--package-info');
var $package_info_submit = null;
var $package_spinner = $('<span class="cerb-ajax-spinner"/>').css('zoom','0.5').css('margin-right', '5px');

$package_chooser.on('cerb-enable', function(e) {
	$package_info.one('transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd', function() {
		$package_info.empty();
	});
	
	$package_chooser_search.focus().select();
	
	$package_chooser.css('max-height','').css('opacity',1).css('transform','translateX(0%)');
	$package_info.css('opacity',0).css('transform','translateX(100%)');
});

var package_search_keyup = function(e) {
	var term = $package_chooser_search.val();
	
	if(0 == term.length) {
		$package_chooser_search.parent().find('.package-library--package').fadeIn();
		return;
	} else {
		$package_chooser_search.parent().find('.package-library--package').hide();
	}
	
	$package_chooser_search.parent().find('.package-library--package').each(function() {
		var $package = $(this);
		var package_text = $package.text().toLowerCase();
		
		if(package_text.indexOf(term) > -1) {
			$package.fadeIn();
		}
	});
};

$package_chooser_search.on('keyup', $.debounce(250, package_search_keyup) );

$package_info.on('cerb-enable', function(e) {
	$package_info.one('transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd', function() {
		var $inputs = $package_info.find('input:text,select');
		
		if(0 == $inputs.length) {
			$(window).scrollTop($package_info.scrollTop());
		} else {
			$inputs.first().focus().select();
		}
	});
	
	$package_chooser.one('transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd', function() {
		$package_chooser.css('max-height', '0');
	});
	
	$package_info.css('opacity',1).css('transform','translateX(0%)');
	$package_chooser.css('opacity',0).css('transform','translateX(-100%)');
});

$package_chooser.on('click', function(e) {
	e.stopPropagation();
	var $target = $(e.target);
	
	var $package = $target.closest('.package-library--package');
	var package_name = $package.attr('data-cerb-package');
	
	if(package_name) {
		genericAjaxGet($package_info, 'c=profiles&a=invoke&module=package&action=showPackagePrompts&package=' + encodeURIComponent(package_name), function() {
			$package_info.triggerHandler('cerb-enable');
			$package_info_submit = $package_info.find('[data-cerb-action="submit"]');
		});
	}
});

$library_container.on('cerb-package-library-form-submit--done', function(e) {
	$package_info_submit.prop('disabled', false);
	$package_spinner.detach();
});

$package_info.on('click', function(e) {
	e.stopPropagation();
	var $target = $(e.target);
	
	// Submit button
	if($target.is($package_info_submit)) {
		$package_info_submit.prop('disabled', true);
		$package_spinner.insertBefore($package_info_submit);
		
		$library_container.triggerHandler('cerb-package-library-form-submit');
		return;
	}
	
	// Cancel button
	if($target.is('[data-cerb-action="cancel"]')) {
		$package_chooser.triggerHandler('cerb-enable');
		return;
	}
});

if($package_chooser_search.is(':visible')) {
	$package_chooser_search.focus().select();
}
