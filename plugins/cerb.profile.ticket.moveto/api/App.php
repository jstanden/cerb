<?php
class ProfileScript_ProfileTicketMoveTo extends Extension_ContextProfileScript {
	const ID = 'cerb.profile.ticket.moveto.script';
	
	function renderScript($context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page_context', $context);
		$tpl->assign('page_context_id', $context_id);
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		$group_buckets = DAO_Bucket::getGroups();
		
		$tpl->assign('buckets', $buckets);
		
		$labels = [];
		
		foreach($group_buckets as $group_id => $buckets) {
			$group = $groups[$group_id];
			
			foreach($buckets as $bucket_id => $bucket) {
				$labels[$bucket_id] = sprintf("%s: %s",
					$group->name,
					$bucket->name
				);
			}
		}
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels,': ',': ');
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerb.profile.ticket.moveto::profile/script.tpl');
	}
};