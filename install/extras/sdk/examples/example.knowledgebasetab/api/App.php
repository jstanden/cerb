<?php
if (class_exists('Extension_KnowledgebaseTab')):
class ExKnowledgebaseTab extends Extension_KnowledgebaseTab {
	function showTab() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();		
		$tpl->display('devblocks:example.knowledgebasetab::index.tpl');		
	}
}
endif;