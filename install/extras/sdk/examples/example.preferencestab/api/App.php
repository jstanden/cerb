<?php
if (class_exists('Extension_PreferenceTab')):
class ExPreferencesTab extends Extension_PreferenceTab {
	function showTab() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:example.preferencestab::index.tpl');
	}
}
endif;