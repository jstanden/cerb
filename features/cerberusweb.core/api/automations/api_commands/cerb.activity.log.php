<?php
class ApiCommand_CerbActivityLog extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.activity.log';
	
	function run(array $params=[], &$error=null) : array|false {
		$validator = DevblocksPlatform::services()->validation();
		
		$error = null;
		
		$actor_context = $params['actor_record_type'] ?? null;
		
		$validator->reset();
		$validator->addField('actor_record_type')->context();
		
		// We allow empty app owner_id
		if(CerberusContexts::isSameContext($actor_context, CerberusContexts::CONTEXT_APPLICATION)) {
			$validator->addField('actor_record_id')->id()->addValidator($validator->validators()->contextId($params['actor_record_type'], true));
		} else {
			$validator->addField('actor_record_id')->id()->addValidator($validator->validators()->contextId($params['actor_record_type']));
		}
		
		$validator->addField('activity_point')->string()->setRequired(true)->addValidator(function($string, &$error=null) {
			if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '.-_'))) {
				$error = "may only contain letters, numbers, dots, dashes, and underscores";
				return false;
			}
			
			if(strlen($string) > 255) {
				$error = "must be shorter than 255 characters.";
				return false;
			}
			
			return true;
		});
		$validator->addField('also_notify_worker_ids')->idArray();
		$validator->addField('also_notify_ignore_self')->boolean();
		$validator->addField('entry')->array();
		$validator->addField('target_record_type')->context();
		$validator->addField('target_record_id')->id()->addValidator($validator->validators()->contextId($params['target_record_type']));
		
		if(!$validator->validateAll($params, $error))
			return false;
		
		$activity_point = $params['activity_point'] ?? null;
		$target_context = $params['target_record_type'] ?? null;
		$target_context_id = $params['target_record_id'] ?? null;
		$actor_context = $params['actor_record_type'] ?? null;
		$actor_context_id = $params['actor_record_id'] ?? null;
		$entry = $params['entry'] ?? [];
		$also_notify_worker_ids = $params['also_notify_worker_ids'] ?? [];
		$also_notify_ignore_self = boolval($params['also_notify_ignore_self'] ?? 0);
		
		$activity_id = CerberusContexts::logActivity(
			activity_point: $activity_point,
			target_context: $target_context,
			target_context_id: $target_context_id,
			entry_array: $entry,
			actor_context: $actor_context,
			actor_context_id: $actor_context_id,
			also_notify_worker_ids: $also_notify_worker_ids,
			also_notify_ignore_self: $also_notify_ignore_self
		);
		
		return [
			'id' => $activity_id,
		];
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		if(in_array($key_path, ['actor_record_type:', 'target_record_type:'])) {
			$record_types = Extension_DevblocksContext::getAll(false);
			
			return array_map(
				fn($record_type) => $record_type->params['alias'],
				array_values($record_types),
			);
			
		} elseif('entry:' == $key_path) {
			return [
				'message@raw: {{actor}} performed an action on {{target}}',
				'variables:',
				'urls:',
			];
			
		} elseif('entry:variables:' == $key_path) {
			return [
				'var_name: Label Text',
			];
			
		} elseif('entry:urls:' == $key_path) {
			return [
				'var_name: cerb:record_type:123',
			];
			
		} elseif('' == $key_path) {
			return match ($key_path) {
				'' => [
					'activity_point:',
					'actor_record_type:',
					'actor_record_id@int:',
					'entry:',
					'target_record_type:',
					'target_record_id@int:',
					'also_notify_worker_ids@csv:',
					'also_notify_ignore_self@bool:',
				],
			};
		}
		
		return [];
	}
}