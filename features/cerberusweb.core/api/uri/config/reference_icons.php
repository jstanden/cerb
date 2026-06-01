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
			'adjust',
			'alert',
			'antenna',
			'ban',
			'bell',
			'bold',
			'book',
			'book-open',
			'bot',
			'bot-message',
			'branch',
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
			'comments',
			'compass',
			'conversation',
			'copy',
			'crosshairs',
			'dice',
			'disk-export',
			'disk-save',
			'down-arrow',
			'duplicate',
			'edit',
			'embed',
			'erase',
			'eye-close',
			'eye-open',
			'fast-backward',
			'fast-forward',
			'file',
			'file-export',
			'file-import',
			'folder',
			'folder-open',
			'folder-plus',
			'gear',
			'gender-female',
			'gender-male',
			'globe',
			'hammer',
			'hash',
			'header',
			'history',
			'id-card',
			'italic',
			'lab',
			'left-arrow',
			'link',
			'list',
			'lock',
			'magic',
			'mail',
			'map',
			'megaphone',
			'mention',
			'menu-hamburger',
			'merge',
			'minus',
			'moon',
			'more',
			'more-vertical',
			'move',
			'move-horizontal',
			'move-vertical',
			'new-window',
			'paperclip',
			'paste',
			'pause',
			'pen',
			'people',
			'picture',
			'placeholders',
			'play',
			'play-button',
			'plus',
			'print',
			'pushpin',
			'quote',
			'refresh',
			'remove',
			'repeat',
			'resize-full',
			'resize-small',
			'restart',
			'right-arrow',
			'save',
			'search',
			'send',
			'share',
			'signal',
			'sort-asc',
			'sort-desc',
			'sparkles',
			'step-backward',
			'step-forward',
			'stop',
			'sun',
			'table',
			'tag',
			'tags',
			'target',
			'text-size',
			'thumbs-down',
			'thumbs-up',
			'toolbox',
			'trash',
			'unchecked',
			'unlock',
			'up-arrow',
			'user',
			'user-lock',
			'wifi',
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