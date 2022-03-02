<?php
class BotAction_PgpEncrypt extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.pgp.encrypt';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'public_key_ids' => [
					'type' => 'records',
					'required' => true,
					'notes' => 'The [PGP public keys](/docs/records/types/gpg_public_key/) to encrypt with',
				],
				'public_key_template' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'An option [bot scripting](/docs/bots/scripting/) template with comma-separated public key IDs',
				],
				'message' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The message to encrypt',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the encrypted message',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_pgp_encrypt.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$public_key_ids = DevblocksPlatform::importVar($params['public_key_ids'] ?? null, 'array', []);
		$public_key_template = $tpl_builder->build($params['public_key_template'] ?? '', $dict);
		$message = $tpl_builder->build($params['message'] ?? '', $dict);
		$object_placeholder = ($params['object_placeholder'] ?? null) ?: '_results';

		if($public_key_template) {
			$public_key_ids = array_merge(
				$public_key_ids,
				DevblocksPlatform::parseCsvString($public_key_template)
			);
		}
		
		if(!$public_key_ids)
			return "[ERROR] At least one public key ID is required.";
		
		if(!$message)
			return "[ERROR] A message to encrypt is required.";
		
		$out = sprintf(">>> Executing PGP encrypt:\n");
		
		$this->run($token, $trigger, $params, $dict);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving results to {{%s}}\n%s".
				"\n",
				$object_placeholder,
				$dict->get($object_placeholder)
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$gpg = DevblocksPlatform::services()->gpg();
		
		$public_key_ids = DevblocksPlatform::importVar($params['public_key_ids'] ?? null, 'array', []);
		$public_key_template = $tpl_builder->build($params['public_key_template'] ?? '', $dict);
		$message = $tpl_builder->build($params['message'] ?? '', $dict);
		$object_placeholder = ($params['object_placeholder'] ?? null) ?: '_results';
		
		if($public_key_template) {
			$public_key_ids = array_merge(
				$public_key_ids,
				DevblocksPlatform::parseCsvString($public_key_template)
			);
		}
		
		if(!$public_key_ids || !$message)
			return;
		
		$public_keys = DAO_GpgPublicKey::getIds($public_key_ids);
		$public_key_fingerprints = array_column($public_keys, 'fingerprint', 'id');
		
		$encrypted_text = $gpg->encrypt($message, $public_key_fingerprints);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->set($object_placeholder, $encrypted_text);
		}
	}
};