<?php
class ApiCommand_CerbEmailRelay extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.email.relay';
	
	function run(array $params=[], &$error=null) : array|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$error = null;
		
		$values = [
			'message_id' => intval($params['message_id'] ?? null),
			'emails' => $params['emails'] ?? [],
			'include_attachments' => boolval($params['include_attachments'] ?? false),
			'body_template' => strval($params['body_template'] ?? ''),
		];
		
		// Polymorph array -> csv string
		if(is_array($values['emails']))
			$values['emails'] = implode(', ', $values['emails']);
		
		$validation->addField('message_id', 'message_id:')
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MESSAGE))
			->setRequired(true)
		;
		
		$validation->addField('emails', 'emails:')
			->string()
			->addValidator($validation->validators()->emails())
			->setRequired(true)
		;
		
		$validation->addField('include_attachments', 'include_attachments:')
			->boolean()
		;
		
		$validation->addField('body_template', 'body_template:')
			->string()
			->setMaxLength('24 bits')
		;
		
		if(!($validation->validateAll($values, $error)))
			return false;
		
		$result = CerberusMail::relay(
			$values['message_id'],
			DevblocksPlatform::parseCsvString($values['emails'] ?? '') ?? [],
			$values['include_attachments'],
			$values['body_template'],
			CerberusContexts::CONTEXT_APPLICATION,
			0
		);
		
		return [
			'status' => $result,
		];
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		if('' == $key_path) {
			return [
				'message_id@int:',
				'emails: worker@cerb.example, ',
				'include_attachments@bool:',
				'body_template@text:',
			];
		}
		
		return [];
	}
}