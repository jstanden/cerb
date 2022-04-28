<?php
class PortalLayoutWidget_Text extends Extension_PortalLayoutWidget {
	const ID = 'cerb.portal.layout.widget.text';
	
	public function init(array $widget_meta, array $layout_meta) {
		return $this->fetch($widget_meta, $layout_meta);
	}
	
	// [TODO] safe mode option
	public function fetch(array $widget_meta, array $layout_meta) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
		
		// [TODO] Extend Parsedown for inline URL callbacks, header IDs, etc
		
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
		
		$widget_meta['content'] = $content;
		
		// [TODO] Fix relative links
		if(array_key_exists('label_link', $widget_meta)) {
			$widget_link = ltrim($widget_meta['label_link'], '/');
			$url_parts = parse_url($widget_link);
			
			if(!array_key_exists('host', $url_parts)) {
				$widget_meta['label_link'] = 
					DevblocksPlatform::services()->url()->write('', true)
					. $widget_link
				;
			}
		}
		
		$tpl->assign('widget_meta', $widget_meta);
		
		return $tpl->fetch('devblocks:cerberusweb.core::portals/builder/layout/widgets/text.tpl');
	}
	
	// [TODO] Used?
	public function render(array $widget_meta, array $layout_meta) {
		echo $this->fetch($widget_meta, $layout_meta);
	}
}