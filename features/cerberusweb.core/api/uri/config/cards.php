<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_SetupCards extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'cards');
		
		$context_manifests = Extension_DevblocksContext::getAll(false, array('cards'));
		$tpl->assign('context_manifests', $context_manifests);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cards/index.tpl');
	}
	
	private function _getRecordType($ext_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('ext_id', $ext_id);

		//  Make sure the extension exists before continuing
		if(false == ($context_ext = Extension_DevblocksContext::get($ext_id)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		CerberusContexts::getContext($context_ext->id, null, $labels, $values, '', true, false);
		$tpl->assign('values', $values);
		
		$keys = array();
		
		// Tokenize the placeholders
		foreach($labels as $k => &$label) {
			$label = trim($label);
			
			if('_label' == $k || '_label' == substr($k, -6)) {
				$label = !empty($label) ? ($label.' (Record)') : '(Record)';
			}
			
			$parts = explode(' ', $label);
			
			$ptr =& $keys;
			
			while($part = array_shift($parts)) {
				if(!isset($ptr[$part]))
					$ptr[$part] = array();
				
				$ptr =& $ptr[''.$part];
			}
		}
		
		// Convert the flat tokens into a tree
		$forward_recurse = function(&$node, $node_key, &$stack) use (&$keys, &$forward_recurse, &$labels) {
			$len = count($node);
			
			if(!empty($node_key))
				array_push($stack, ''.$node_key);
			
			switch($len) {
				case 0:
					$o = new stdClass();
					$o->label = implode(' ', $stack);
					$o->key = array_search($o->label, $labels);
					$o->l = $node_key;
					$node = $o;
					break;
					
				default:
					if(is_array($node))
					foreach($node as $k => &$n) {
						$forward_recurse($n, $k, $stack);
					}
					break;
			}
			
			array_pop($stack);
		};
		
		$stack = array();
		$forward_recurse($keys, '', $stack);

		$condense = function(&$node, $key=null, &$parent=null) use (&$condense) {
			// If this node has exactly one child
			if(is_array($node) && 1 == count($node)) {
				reset($node);
				
				// Replace the current node with its only child
				$k = key($node);
				$n = array_pop($node);
				if(is_object($n))
					$n->l = $key . ' ' . $n->l;
				
				// Deconstruct our parent
				$keys = array_keys($parent);
				$vals = array_values($parent);
				
				// Replace this node's key and value in the parent
				$idx = array_search($key, $keys);
				$keys[$idx] = $key.' '.$k;
				$vals[$idx] = $n;
				
				// Reconstruct the parent
				$parent = array_combine($keys, $vals);
			}
			
			// If this node still has children, recurse into them
			if(is_array($node))
			foreach($node as $k => &$n)
				$condense($n, $k, $node);
		};
		$condense($keys);
		
		$tpl->assign('keys', $keys);
		
		$tokens = array();
		
		$properties = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'card:' . $context_ext->id, array(), true);
		
		if(empty($properties))
			$properties = $context_ext->getDefaultProperties();
		
		$tpl->assign('tokens', $properties);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cards/edit_record.tpl');
	}
	
	// Ajax
	function getRecordTypeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id']);
		$this->_getRecordType($ext_id);
	}
	
	function saveRecordTypeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		// Type of custom fields
		@$ext_id = DevblocksPlatform::importGPC($_POST['ext_id'],'string','');
		@$tokens = DevblocksPlatform::importGPC($_POST['tokens'],'array',array());
		
		header('Content-Type: application/json');
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'card:' . $ext_id, $tokens, true);
		echo json_encode(true);
		return;
	}
};