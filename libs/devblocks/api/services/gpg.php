<?php
abstract class Extension_DevblocksGpgEngine {
	abstract function isEnabled();
	abstract function importPublicKey($ascii_key);
	abstract function importPrivateKey($ascii_key, $passphrase=null);
	abstract function exportPublicKey($fingerprint);
	abstract function exportPrivateKey($fingerprint);
	abstract function deletePublicKey($fingerprint);
	abstract function deletePrivateKey($fingerprint);
	abstract function keyinfoPublic($fingerprint);
	abstract function keyinfoPrivate($fingerprint);
	abstract function keyinfo($ascii_key, $passphrase=null);
	abstract function encrypt($plaintext, $key_fingerprints);
	abstract function decrypt($encrypted_content);
	abstract function sign($plaintext, $key_fingerprint, $is_detached=true);
	abstract function verify($signed_content, $signature=false);
	abstract function keygen(array $uids, int $key_length, string $passphrase=null);
}

class DevblocksGpgEngine_OpenPGP extends Extension_DevblocksGpgEngine {
	function isEnabled() {
		return true;
	}
	
	/**
	 * @param array $uids
	 * @param int $key_length
	 * @param string|null $passphrase
	 * @return array|false
	 */
	function keygen(array $uids, int $key_length=2048, string $passphrase=null) {
		// [TODO] Passphrases
		
		// [TODO] Exceptions
		if(!in_array($key_length,[512,1024,2048,3072,4096]))
			return false;
		
		$rsa = new \phpseclib\Crypt\RSA();
		$k = $rsa->createKey($key_length);
		$rsa->loadKey($k['privatekey']);
		
		$nkey = new OpenPGP_SecretKeyPacket([
			'n' => $rsa->modulus->toBytes(),
			'e' => $rsa->publicExponent->toBytes(),
			'd' => $rsa->exponent->toBytes(),
			'p' => $rsa->primes[2]->toBytes(),
			'q' => $rsa->primes[1]->toBytes(),
			'u' => $rsa->coefficients[2]->toBytes()
		]);
		
		$packets = [$nkey];
		
		$wkey = new OpenPGP_Crypt_RSA($nkey);
		$fingerprint = $wkey->key()->fingerprint;
		$key = $wkey->private_key();
		$key->setHash('sha256');
		$keyid = substr($fingerprint, -16);
		
		foreach($uids as $uid_data) {
			$uid_name = $uid_data['name'] ?? null;
			$uid_email = $uid_data['email'] ?? null;
			
			if(!$uid_name || !$uid_email)
				continue;
			
			$uid = new OpenPGP_UserIDPacket($uid_name, '', $uid_email);
			$packets[] = $uid;
			
			$sig = new OpenPGP_SignaturePacket(new OpenPGP_Message([$nkey, $uid]), 'RSA', 'SHA256');
			$sig->signature_type = 0x13;
			$sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyFlagsPacket(array(0x01 | 0x02)); // Certify + sign
			$sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket($keyid);
			$m = $wkey->sign_key_userid([$nkey, $uid, $sig]);
			
			$packets[] = $m->packets[2];
		}
		
		$rsa_subkey = new \phpseclib\Crypt\RSA();
		$sub_k = $rsa_subkey->createKey($key_length);
		$rsa_subkey->loadKey($sub_k['privatekey']);
		
		$subkey = new OpenPGP_SecretSubkeyPacket([
			'n' => $rsa_subkey->modulus->toBytes(),
			'e' => $rsa_subkey->publicExponent->toBytes(),
			'd' => $rsa_subkey->exponent->toBytes(),
			'p' => $rsa_subkey->primes[2]->toBytes(),
			'q' => $rsa_subkey->primes[1]->toBytes(),
			'u' => $rsa_subkey->coefficients[2]->toBytes()
		]);
		
		$packets[] = $subkey;
		
		$sub_sig = new OpenPGP_SignaturePacket(null, 'RSA', 'SHA256');
		$sub_sig->signature_type = 0x18;
		$sub_sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_SignatureCreationTimePacket(time());
		$sub_sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyFlagsPacket(array(0x0C)); // Encrypt
		$sub_sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket($keyid);
		$sub_sig->data = implode('', $nkey->fingerprint_material()) . implode('', $subkey->fingerprint_material());
		$sub_sig->sign_data(array('RSA' => array('SHA256' => function($data) use($key) {return array($key->sign($data));})));
		
		$packets[] = $sub_sig;
		
		$m = new OpenPGP_Message($packets);
		
		// Serialize public key message
		$pubm = clone($m);
		
		foreach($pubm as $idx => $p) {
			if($p instanceof OpenPGP_SecretSubkeyPacket) {
				$pubm[$idx] = new OpenPGP_PublicSubkeyPacket($p);
			} else if($p instanceof OpenPGP_SecretKeyPacket) {
				$pubm[$idx] = new OpenPGP_PublicKeyPacket($p);
			}
		}
		
		$privkey_bytes = $m->to_bytes();
		$privkey_ascii = OpenPGP::enarmor($privkey_bytes, 'PGP PRIVATE KEY BLOCK');
		
		$pubkey_bytes = $pubm->to_bytes();
		$pubkey_ascii = OpenPGP::enarmor($pubkey_bytes, 'PGP PUBLIC KEY BLOCK');
		
		return [
			'public_key' => $pubkey_ascii,
			'private_key' => $privkey_ascii,
		];
	}
	
	function importPublicKey($ascii_key) {
		if(false == ($keyinfo = $this->keyinfo($ascii_key)))
			return false;
		
		return $keyinfo;
	}
	
	function importPrivateKey($ascii_key, $passphrase=null) {
		if(false == ($keyinfo = $this->keyinfo($ascii_key, $passphrase)))
			return false;
		
		return $keyinfo;
	}
	
	function exportPublicKey($fingerprint) {
		if(false == $public_key = DAO_GpgPublicKey::getByFingerprint($fingerprint))
			return false;

		return $public_key->key_text;
	}
	
	function exportPrivateKey($fingerprint) {
		if(false == $private_key = DAO_GpgPrivateKey::getByFingerprint($fingerprint))
			return false;

		return $private_key->key_text;
	}
	
	function deletePublicKey($fingerprint) {
		return true;
	}
	
	function deletePrivateKey($fingerprint) {
		return true;
	}
	
	// [TODO] Throw exceptions
	public function keyinfo($key_text, $passphrase=null) {
		if(false !== strpos($key_text, 'PGP PUBLIC KEY BLOCK')) {
			$header = 'PGP PUBLIC KEY BLOCK';
		} else if(false !== strpos($key_text, 'PGP PRIVATE KEY BLOCK')) {
			$header = 'PGP PRIVATE KEY BLOCK';
		} else {
			return [];
		}
		
		// [TODO] Check return type
		$key_bytes = OpenPGP::unarmor($key_text, $header);
		
		// [TODO] If password protected
		$m = OpenPGP_Message::parse($key_bytes);
		
		$keyinfo = [
			'disabled' => false,
			'expired' => false,
			'revoked' => false,
			'is_secret' => false,
			'can_sign' => false,
			'can_encrypt' => false,
			'uids' => [],
			'subkeys' => [],
		];

		$id16_ptrs = [];
		$last_key = null;
		
		foreach($m->packets as $p) {
			$keydata = [
				'fingerprint' => null,
				'keyid' => null,
				'timestamp' => 0,
				'expires' => 0,
				'is_secret' => false,
				'invalid' => false,
				'can_encrypt' => false,
				'can_sign' => false,
				'disabled' => false, // [TODO]
				'expired' => false,
				'revoked' => false, // [TODO]
			];
			
			if ($p instanceof OpenPGP_SecretKeyPacket) {
				/* @var $p OpenPGP_SecretKeyPacket */
				
				$keydata['fingerprint'] = $p->fingerprint;
				$keydata['keyid'] = $p->key_id;
				$keydata['timestamp'] = $p->timestamp;
				$keydata['is_secret'] = true;
				$id16 = substr($p->fingerprint, -16);
				$id16_ptrs[$id16] = $keydata;
				$last_key = $id16;
				
			} else if ($p instanceof OpenPGP_PublicKeyPacket) {
				/* @var $p OpenPGP_PublicKeyPacket */
				
				$keydata['fingerprint'] = $p->fingerprint;
				$keydata['keyid'] = $p->key_id;
				$keydata['timestamp'] = $p->timestamp;
				$id16 = substr($p->fingerprint, -16);
				$id16_ptrs[$id16] = $keydata;
				$last_key = $id16;
				
			} else if($p instanceof OpenPGP_SignaturePacket) {
				/* @var $p OpenPGP_SignaturePacket */
				
				$id16_issuer = $p->issuer();
				
				// [TODO] Verify signatures
				if(!array_key_exists($id16_issuer, $id16_ptrs))
					continue;
				
				$ptr =& $id16_ptrs[$last_key];
				
				if(in_array($p->signature_type,[16,17,18,19])) {
					// Public key signature
				} else if(in_array($p->signature_type, [24,25])) {
					// Subkey signature
				} else {
					continue;
				}
				
				foreach(array_merge($p->hashed_subpackets, $p->unhashed_subpackets) as $pp) {
					if ($pp instanceof OpenPGP_SignaturePacket_KeyExpirationTimePacket) {
						$ptr['expires'] = $ptr['timestamp'] + $pp->data;
						$ptr['expired'] = $ptr['expires'] < time();
					} else if ($pp instanceof OpenPGP_SignaturePacket_FeaturesPacket) {
						// This is a subclass of OpenPGP_SignaturePacket_KeyFlagsPacket
					} else if ($pp instanceof OpenPGP_SignaturePacket_KeyFlagsPacket) {
						$ptr['can_sign'] = boolval($pp->flags[0] & 2);
						$ptr['can_encrypt'] = boolval($pp->flags[0] & 4);
					}
				}
			
			} else if($p instanceof OpenPGP_UserIDPacket) {
				/* @var $p OpenPGP_UserIDPacket */
				
				$keyinfo['uids'][] = [
					'name' => $p->name,
					'comment' => $p->comment,
					'email' => $p->email,
					'uid' => $p->data,
					'revoked' => false, // [TODO]
					'invalid' => false, // [TODO]
				];
			}
		}
		
		$keyinfo['subkeys'] = array_values(array_filter(
			$id16_ptrs,
			function($id16) {
				return !($id16['expired'] || $id16['revoked'] || $id16['disabled']);
			}
		));
		
		$keyinfo['is_secret'] = false !== array_search(true, array_column($keyinfo['subkeys'], 'is_secret'));
		$keyinfo['can_sign'] = false !== array_search(true, array_column($keyinfo['subkeys'], 'can_sign'));
		$keyinfo['can_encrypt'] = false !== array_search(true, array_column($keyinfo['subkeys'], 'can_encrypt'));

		return $keyinfo;
	}
	
	function keyinfoPublic($fingerprint) {
		// If the fingerprint is `<>` then we need to check email addys instead
		if(DevblocksPlatform::strStartsWith($fingerprint,'<')) {
			$email_string = trim($fingerprint,'<> ');
			$public_keys = DAO_GpgKeyPart::getPublicKeysByPart('email', $email_string);
			$results = [];
			
			foreach($public_keys as $public_key) {
				if(false != ($keyinfo = $this->keyinfo($public_key->key_text)))
					$results[] = $keyinfo;
			}
			
			return $results;
			
		} else {
			if(false == $public_key = DAO_GpgPublicKey::getByFingerprint($fingerprint))
				return [];
			
			$keyinfo = $this->keyinfo($public_key->key_text);
			return [$keyinfo];
		}
	}
	
	function keyinfoPrivate($fingerprint) {
		// If the fingerprint is `<>` then we need to check email addys instead
		if(DevblocksPlatform::strStartsWith($fingerprint,'<')) {
			$email_string = trim($fingerprint,'<> ');
			$private_keys = DAO_GpgKeyPart::getPrivateKeysByPart('email', $email_string);
			$results = [];
			
			foreach($private_keys as $private_key) {
				if(false != ($keyinfo = $this->keyinfo($private_key->key_text)))
					$results[] = $keyinfo;
			}
			
			return $results;
			
		} else {
			if (false == $private_key = DAO_GpgPrivateKey::getByFingerprint($fingerprint))
				return [];
			
			$keyinfo = $this->keyinfo($private_key->key_text);
			return [$keyinfo];
		}
	}
	
	function encrypt($plaintext, $key_fingerprints) {
		if(!is_array($key_fingerprints))
			return false;
		
		$pub_keys = [];
		
		$data = new OpenPGP_LiteralDataPacket(
			$plaintext,
			[
				'format' => 'u',
				'filename' => 'encrypted.asc',
			]
		);
		
		// [TODO] Get all fingerprints at once
		foreach($key_fingerprints as $key_fingerprint) {
			if(is_numeric($key_fingerprint) && strlen($key_fingerprint) < 16) {
				if(false == ($public_key = DAO_GpgPublicKey::get($key_fingerprint)))
					continue;
				
			} else {
				if(false == ($public_key = DAO_GpgPublicKey::getByFingerprint($key_fingerprint)))
					continue;
			}
			
			if(false == ($pub_key = OpenPGP_Message::parse(OpenPGP::unarmor($public_key->key_text, 'PGP PUBLIC KEY BLOCK'))))
				continue;
			
			$pub_keys[] = $pub_key->packets[0];
		}
		
		$msg_encrypted = OpenPGP_Crypt_Symmetric::encrypt($pub_keys, new OpenPGP_Message([$data]));
		return OpenPGP::enarmor($msg_encrypted->to_bytes(), 'PGP MESSAGE');
	}
	
	/**
	 * @param $encrypted_content
	 * @return array|false
	 * @throws Exception
	 */
	function decrypt($encrypted_content) {
		$msg_encrypted = OpenPGP::unarmor($encrypted_content, 'PGP MESSAGE');
		$msg = OpenPGP_Message::parse($msg_encrypted);
		
		if(is_object($msg))
		foreach($msg->packets as $p) {
			if($p instanceof OpenPGP_AsymmetricSessionKeyPacket) { /* @var $p OpenPGP_AsymmetricSessionKeyPacket */
				if(false == ($private_key = DAO_GpgPrivateKey::getByFingerprint($p->keyid)))
					continue;
				
				// [TODO] Cache the raw bytes?
				$priv_key = OpenPGP_Message::parse(OpenPGP::unarmor($private_key->key_text, 'PGP PRIVATE KEY BLOCK'));
				
				foreach($priv_key as $key_packet) {
					if(!($key_packet instanceof OpenPGP_SecretKeyPacket))
						continue;
					
					// Get the encrypted password from the private key record
					if($key_packet->encrypted_data) {
						$key_passphrase = DevblocksPlatform::services()->encryption()->decrypt($private_key->passphrase_encrypted);
						$key_packet = OpenPGP_Crypt_Symmetric::decryptSecretKey($key_passphrase, $key_packet);
					}
					
					// Make sure we're looking at the right subkey
					if(!DevblocksPlatform::strEndsWith($key_packet->fingerprint(), $p->keyid))
						continue;
					
					$decryptor = new OpenPGP_Crypt_RSA($key_packet);
					$decrypted = $decryptor->decrypt($msg);
					
					if(false == $decrypted)
						return false;
					
					$decrypted_data = '';
					$verified_signatures = [];
					
					while($decrypted[0] instanceof OpenPGP_CompressedDataPacket)
						$decrypted = $decrypted[0]->data;
					
					list($content, $signatures) = $decrypted->signatures()[0];
					
					if($content instanceof OpenPGP_LiteralDataPacket) {
						$decrypted_data = $content->data;
					}
					
					if(is_array($signatures))
					foreach($signatures as $signature) {
						if($signature instanceof OpenPGP_SignaturePacket) {
							if(false != ($result = $this->verify($decrypted, $signature))) {
								if (array_key_exists('fingerprint', $result)) {
									$verified_signatures = $result;
								}
							}
						}
					}
					
					$result = [
						'data' => $decrypted_data,
						'encrypted_for' => [
							'key_context' => Context_GpgPrivateKey::ID,
							'key_id' => $private_key->id,
							'fingerprint' => $key_packet->fingerprint(),
						],
						'verified_signatures' => $verified_signatures,
					];
					
					return $result;
				}
			}
		}
		
		return false;
	}
	
	function sign($plaintext, $key_fingerprint, $is_detached = true) {
		if(false == ($private_key = DAO_GpgPrivateKey::getByFingerprint($key_fingerprint)))
			return false;
		
		$priv_key = OpenPGP_Message::parse(OpenPGP::unarmor($private_key->key_text, 'PGP PRIVATE KEY BLOCK'));
		
		foreach($priv_key as $key_packet) {
			if (!($key_packet instanceof OpenPGP_SecretKeyPacket))
				continue;
			
			// Get the encrypted password from the private key record
			if ($key_packet->encrypted_data) {
				$key_passphrase = DevblocksPlatform::services()->encryption()->decrypt($private_key->passphrase_encrypted);
				$key_packet = OpenPGP_Crypt_Symmetric::decryptSecretKey($key_passphrase, $key_packet);
			}
			
			$data = new OpenPGP_LiteralDataPacket($plaintext, ['format' => 'u', 'filename' => 'stuff.txt']);
			$data->normalize(false);
			
			$sign = new OpenPGP_Crypt_RSA($key_packet);
			$m = $sign->sign($data);
			$packets = $m->signatures()[0];
			
			return OpenPGP::enarmor($packets[1][0]->to_bytes(), "PGP SIGNATURE");
		}
	}
	
	function verify($signed_content, $signature = false) {
		if($signature instanceof OpenPGP_SignaturePacket) {
			$signature = new OpenPGP_Message([$signature]);
		} else {
			$signature = OpenPGP_Message::parse(OpenPGP::unarmor($signature, 'PGP SIGNATURE'));
		}
		
		if($signed_content instanceof OpenPGP_Message) {
			$msg = $signed_content;
		
		} else {
			$data = new OpenPGP_LiteralDataPacket($signed_content, ['format'=>'u','filename'=>'stuff.txt']);
			$data->normalize(false);
			
			$msg = new OpenPGP_Message([
				$signature->packets[0],
				$data,
			]);
		}
		
		foreach($signature->packets as $s) {
			if($s instanceof OpenPGP_SignaturePacket) { /* @var OpenPGP_SignaturePacket $s */
				if (false == ($public_key = DAO_GpgPublicKey::getByFingerprint($s->issuer())))
					continue;
				
				$pub_key = OpenPGP_Message::parse(OpenPGP::unarmor($public_key->key_text, 'PGP PUBLIC KEY BLOCK'));
				
				if(!($pub_key->packets[0] instanceof OpenPGP_PublicKeyPacket))
					continue;
				
				$pub_key = $pub_key->packets[0]; /* @var OpenPGP_PublicKeyPacket $pub_key */
				
				$verify = new OpenPGP_Crypt_RSA($pub_key);
				
				$results = $verify->verify($msg);
				
				if(false == ($verified_signatures = @$results[0][1]) || !is_array($verified_signatures) || 1 != count($verified_signatures))
					return false;
				
				$verified_signature = $verified_signatures[0]; /* @var OpenPGP_SignaturePacket $verified_signature */
				
				$signed_at = 0;
				
				foreach(array_merge($verified_signature->hashed_subpackets,$verified_signature->unhashed_subpackets) as $uhp) {
					if($uhp instanceof OpenPGP_SignaturePacket_SignatureCreationTimePacket) { /* @var OpenPGP_SignaturePacket_SignatureCreationTimePacket $uhp */
						$signed_at = $uhp->data;
					}
				}
				
				return [
					'key_context' => Context_GpgPublicKey::ID,
					'key_id' => $public_key->id,
					'fingerprint' => $pub_key->fingerprint(),
					'signed_at' => $signed_at,
					'signed_uid' => $public_key->name,
				];
			}
		}
		
		return false;
	}
}

class _DevblocksGPGService {
	static $instance = null;
	
	private function __construct() {}
	
	/**
	 * @return Extension_DevblocksGpgEngine
	 */
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new DevblocksGpgEngine_OpenPGP();
		}
		
		return self::$instance;
	}
};
