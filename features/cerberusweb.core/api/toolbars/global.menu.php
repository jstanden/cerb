<?php
class Toolbar_GlobalMenu extends Extension_Toolbar {
	const ID = 'cerb.toolbar.global.menu';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getInteractionInputsMeta() : array {
		return [
		];
	}
	
	function getInteractionOutputMeta(): array {
		return [
		];
	}
	
	function getInteractionAfterMeta() : array {
		return [
		];
	}
	
	public static function getInteractionsMenu() {
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if(null == $active_worker)
			return [];
		
		$legacy_interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('global', [], $active_worker);
		
		$toolbar_kata = '';
		
		if(null != ($toolbar = DAO_Toolbar::getByName('global.menu')))
			$toolbar_kata = $toolbar->toolbar_kata;
		
		if($legacy_interactions) {
			$legacy_kata = "\nmenu/legacy:\n  label: (Legacy Chat Bots)\n  items:\n";
			
			foreach ($legacy_interactions as $interaction) {
				$legacy_kata .= sprintf("    behavior/%s:\n      label: %s\n      id: %d\n      interaction: %s\n      image: %s\n      params:\n",
					uniqid(),
					$interaction['label'],
					$interaction['behavior_id'],
					$interaction['interaction'],
					$url_writer->write(sprintf('c=avatars&context=bot&context_id=%d', $interaction['bot_id'])) . '?v=0',
				);
				
				if ($interaction['params']) {
					foreach ($interaction['params'] as $k => $v) {
						$legacy_kata .= sprintf("        %s: %s\n",
							$k,
							$v
						);
					}
				}
			}
			
			$toolbar_kata .= $legacy_kata;
		}
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		return DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict);		
	}
}