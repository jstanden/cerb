<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupDevelopersReferenceIcons extends Extension_PageSection {
	static function getCerbIcons($limit=null, $page=0, $filter=null, &$paging=[]) : array {
		$icons = [
			'academic-cap',
			'adjust',
			'alert',
			'antenna',
			'archive',
			'ban',
			'bell',
			'bold',
			'book',
			'book-open',
			'bookmark',
			'bot',
			'bot-message',
			'branch',
			'bug',
			'building-apartments',
			'building-gov',
			'building-house',
			'building-office',
			'calendar',
			'camera',
			'check',
			'chevron-down',
			'chevron-left',
			'chevron-right',
			'chevron-up',
			'circle-arrow-down',
			'circle-arrow-left',
			'circle-arrow-right',
			'circle-arrow-up',
			'circle-exclamation-mark',
			'circle-info',
			'circle-minus',
			'circle-ok',
			'circle-plus',
			'circle-question-mark',
			'circle-remove',
			'clipboard',
			'clock',
			'cloud',
			'cloud-download',
			'cloud-upload',
			'color-palette',
			'comments',
			'compass',
			'computer-desktop',
			'computer-laptop',
			'computer-tablet',
			'console',
			'conversation',
			'copy',
			'crosshairs',
			'cube',
			'dashboard',
			'database',
			'dequeue',
			'dice',
			'disk-export',
			'disk-save',
			'down-arrow',
			'download',
			'duplicate',
			'edit',
			'embed',
			'enqueue',
			'erase',
			'eye-close',
			'eye-open',
			'face-frown',
			'face-neutral',
			'face-smile',
			'fast-backward',
			'fast-forward',
			'file',
			'file-document',
			'file-export',
			'file-image',
			'file-import',
			'file-zip',
			'flag',
			'folder',
			'folder-open',
			'folder-plus',
			'funnel',
			'gear',
			'gender-female',
			'gender-male',
			'gift',
			'globe',
			'hammer',
			'hash',
			'header',
			'heart',
			'hierarchy',
			'history',
			'hourglass',
			'id-card',
			'inbox',
			'italic',
			'lab',
			'left-arrow',
			'light-bulb',
			'link',
			'list',
			'location',
			'lock',
			'magic',
			'mail',
			'mail-lock',
			'map',
			'megaphone',
			'mention',
			'menu-hamburger',
			'merge',
			'microphone',
			'minus',
			'mobile',
			'moon',
			'more',
			'more-vertical',
			'move',
			'move-horizontal',
			'move-vertical',
			'new-window',
			'nodes',
			'paintbrush',
			'paperclip',
			'paste',
			'pause',
			'pen',
			'phone-handset',
			'phone-headset',
			'picture',
			'placeholders',
			'play',
			'play-button',
			'plug',
			'plus',
			'print',
			'pushpin',
			'qr-code',
			'quote',
			'refresh',
			'remove',
			'repeat',
			'resize-full',
			'resize-small',
			'restart',
			'return',
			'right-arrow',
			'save',
			'search',
			'send',
			'share',
			'shield',
			'sign-out',
			'signal',
			'sort-asc',
			'sort-desc',
			'sparkle',
			'sparkles',
			'spinner',
			'star',
			'step-backward',
			'step-forward',
			'stop',
			'stopwatch',
			'sun',
			'table',
			'tag',
			'tags',
			'target',
			'telescope',
			'text-size',
			'thumbs-down',
			'thumbs-up',
			'ticket',
			'todo',
			'toolbox',
			'transfer',
			'translate',
			'trash',
			'trophy',
			'unchecked',
			'unlock',
			'up-arrow',
			'upload',
			'user',
			'user-lock',
			'users',
			'wifi',
			'window-bottom',
			'window-left',
			'window-right',
			'window-top',
			'wrench',
			'zap',
			'zoom-in',
			'zoom-out',
		];
		
		if($filter) {
			$icons = array_filter($icons, function($icon) use ($filter) {
				return stristr($icon, $filter);
			});
		}
		
		if($limit) {
			$total = count($icons);
			
			$icons = array_splice($icons, $page*$limit, $limit);
			
			$paging = DevblocksPlatform::services()->data()->generatePaging($icons, $total, $limit, $page);
		}
		
		return $icons;
	}
	
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		
		array_shift($stack); // config
		array_shift($stack); // reference_icons
		
		$visit->set(ChConfigurationPage::ID, 'reference_icons');
		
		$icons_cerb = self::getCerbIcons();
		$tpl->assign('icons_cerb', $icons_cerb);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/reference/icons/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}
}