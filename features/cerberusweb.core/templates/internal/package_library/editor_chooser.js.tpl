var $package_library = $library_container.find('.package-library');
var $package_chooser = $package_library.find('.package-library--package-chooser');
var $package_info = $package_library.find('.package-library--package-info');

$package_chooser.on('cerb-enable', function(e) {
	$package_info.one('transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd', function() {
		$package_info.empty();
	});
	
	$package_chooser.css('max-height','').css('opacity',1).css('transform','translateX(0%)');
	$package_info.css('opacity',0).css('transform','translateX(100%)');
});

$package_info.on('cerb-enable', function(e) {
	$package_info.one('transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd', function() {
		$package_info.find('input:text').first().focus().select();
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
		genericAjaxGet($package_info, 'c=profiles&a=handleSectionAction&section=package&action=showPackagePrompts&package=' + encodeURIComponent(package_name), function() {
			$package_info.triggerHandler('cerb-enable');
		});
	}
});

$package_info.on('click', function(e) {
	e.stopPropagation();
	var $target = $(e.target);
	
	// Submit button
	if($target.is('[data-cerb-action="submit"]')) {
		$library_container.triggerHandler('cerb-package-library-form-submit');
		return;
	}
	
	// Cancel button
	if($target.is('[data-cerb-action="cancel"]')) {
		$package_chooser.triggerHandler('cerb-enable');
		return;
	}
});
