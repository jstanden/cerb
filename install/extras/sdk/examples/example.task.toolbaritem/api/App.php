<?php
if (class_exists('Extension_TaskToolbarItem',true)):
	class ExTaskToolbarItem extends Extension_TaskToolbarItem {
		function render(Model_Task $task) { 
			$tpl = DevblocksPlatform::getTemplateService();
			
			$tpl->assign('task', $task); /* @var $message Model_Ticket */			
			$tpl->display('devblocks:example.task.toolbaritem::button.tpl');
		}
	};
endif;