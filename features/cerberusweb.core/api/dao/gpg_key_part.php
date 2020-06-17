<?php
class DAO_GpgKeyPart {
	static function getPublicKeysByPart($part_name, $part_value) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT DISTINCT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value %s %s",
			$db->qstr(Context_GpgPublicKey::ID),
			$db->qstr($part_name),
			is_array($part_value) ? 'in' : '=',
			is_array($part_value) ? ('(' . implode(',', $db->qstrArray($part_value)) . ')') : $db->qstr($part_value)
		);
		
		$results = $db->GetArrayReader($sql);
		
		return DAO_GpgPublicKey::getIds(array_column($results, 'key_id'));
	}
	
	static function getPrivateKeysByPart($part_name, $part_value) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT DISTINCT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value %s %s",
			$db->qstr(Context_GpgPrivateKey::ID),
			$db->qstr($part_name),
			is_array($part_value) ? 'in' : '=',
			is_array($part_value) ? ('(' . implode(',', $db->qstrArray($part_value)) . ')') : $db->qstr($part_value)
		);
		
		$results = $db->GetArrayReader($sql);
		
		return DAO_GpgPrivateKey::getIds(array_column($results, 'key_id'));
	}
	
	static function upsert($key_context, $key_id, array $keyinfo) {
		$db = DevblocksPlatform::services()->database();
		
		$key_context_escaped = $db->qstr($key_context);
		
		// Clear existing references to this key
		$db->ExecuteMaster(sprintf("DELETE FROM gpg_key_part WHERE key_context = %s AND key_id = %d",
			$key_context_escaped,
			$key_id
		));
		
		$values = [];
		
		if(array_key_exists('uids', $keyinfo)) {
			foreach($keyinfo['uids'] as $uid) {
				$values[] = sprintf('(%s,%d,%s,%s)',
					$key_context_escaped,
					$key_id,
					$db->qstr('uid'),
					$db->qstr($uid['uid'])
				);
				$values[] = sprintf('(%s,%d,%s,%s)',
					$key_context_escaped,
					$key_id,
					$db->qstr('name'),
					$db->qstr($uid['name'])
				);
				$values[] = sprintf('(%s,%d,%s,%s)',
					$key_context_escaped,
					$key_id,
					$db->qstr('email'),
					$db->qstr($uid['email'])
				);
			}
		}
		
		if(array_key_exists('subkeys', $keyinfo) && is_array($keyinfo['subkeys'])) {
			foreach($keyinfo['subkeys'] as $subkey) {
				$values[] = sprintf('(%s,%d,%s,%s)',
					$key_context_escaped,
					$key_id,
					$db->qstr('fingerprint'),
					$db->qstr($subkey['fingerprint'])
				);
				
				$values[] = sprintf('(%s,%d,%s,%s)',
					$key_context_escaped,
					$key_id,
					$db->qstr('fingerprint16'),
					$db->qstr(substr($subkey['fingerprint'], -16))
				);
			}
		}
		
		if($values) {
			$sql = sprintf("INSERT INTO gpg_key_part (key_context, key_id, part_name, part_value) VALUES %s",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
		}
	}
}