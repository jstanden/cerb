<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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

class PageSection_InternalDashboards extends Extension_PageSection {
	function render() {}
	
	
	function setWidgetPositionsAction() {
		@$columns = DevblocksPlatform::importGPC($_REQUEST['column'], 'array', array());

		if(is_array($columns))
		foreach($columns as $idx => $widget_ids) {
			foreach(DevblocksPlatform::parseCsvString($widget_ids) as $n => $widget_id) {
				$pos = sprintf("%d%03d", $idx, $n);
				
				DAO_WorkspaceWidget::update($widget_id, array(
					DAO_WorkspaceWidget::POS => $pos,
				));
			}
			
			// [TODO] Kill cache on dashboard
		}
	}
	
	function handleWidgetActionAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'string', '');
		@$widget_action = DevblocksPlatform::importGPC($_REQUEST['widget_action'], 'string', '');
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		if(false == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;
		
		if(!Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			return;
		
		if(false == ($widget_extension = $widget->getExtension()))
			return;
		
		if($widget_extension instanceof Extension_WorkspaceWidget && method_exists($widget_extension, $widget_action.'Action')) {
			call_user_func([$widget_extension, $widget_action.'Action']);
		}
	}
}