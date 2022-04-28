<?php
class PortalWidget_Text extends Extension_PortalWidget {
	const ID = 'cerb.portal.widget.text';
	
	public function init(array $widget_meta, array $page_meta) {
		return $this->fetch($widget_meta);
	}
	
	// [TODO] safe mode option
	public function fetch(array $widget_meta) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
		
		// [TODO] Handle URLs
		
		$content = Portal_Builder::parseMarkdown($widget_meta['content'] ?? '');
		$content_params = $widget_meta['content_params'] ?? [];
		
		if(!is_array($content_params))
			$content_params = [];
		
		$lexer = [
			'tag_comment'   => ['${#', '#}'],
			'tag_block'     => ['${%', '%}'],
			'tag_variable'  => ['${', '}'],
			'interpolation' => ['$#{', '}'],
		];
		
		if(false === ($content = $tpl_builder->build($content, $content_params, $lexer)))
			$content = '';
		
		$tpl->assign('content_html', $content);
		
		return $tpl->fetch('devblocks:cerberusweb.core::portals/builder/widgets/text.tpl');
	}
	
	// [TODO] Used?
	public function render(array $widget_meta, array $page_meta) {
		echo $this->fetch($widget_meta);
	}
}