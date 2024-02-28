<?php
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Validator;

class ApiCommand_CerbOAuth2TokenValidate extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.oauth2.token.validate';
	
	function run(array $params=[], &$error=null) : array|false {
		$jwt_token = $params['token'] ?? '';
		
		$oauth = DevblocksPlatform::services()->oauth();
		$jwt_parser = new Parser(new JoseEncoder());
		$jwt_validator = new Validator();
		
		try {
			$token = $jwt_parser->parse($jwt_token);
			
			if(!($jwt_validator->validate(
				$token,
				new Lcobucci\JWT\Validation\Constraint\SignedWith(new Sha256(), InMemory::plainText($oauth->getServerPublicKey()->getKeyContents())),
				new Lcobucci\JWT\Validation\Constraint\StrictValidAt(new SystemClock(new DateTimeZone(\date_default_timezone_get()))),
			))) {
				$error = 'Invalid token';
				return false;
			}
			
		} catch(CannotDecodeContent | UnsupportedHeaderFound | InvalidTokenStructure $e) {
			$error = (new \ReflectionClass($e))->getShortName();
			return false;
			
		} catch(Throwable) {
			$error = 'Unable to parse token';
			return false;
		}
		
		$token_id = $token->claims()->get('jti');
		
		if(!($oauth_token = DAO_OAuthToken::getAccessToken($token_id)))
			return [];
		
		$scopes = $token->claims()->get('scopes') ?? [];
		
		$dict = DevblocksDictionaryDelegate::instance([
			'token_id' => $oauth_token->token,
			'token_type' => $oauth_token->token_type,
			'token_created_at' => $oauth_token->created_at,
			'token_expires_at' => $oauth_token->expires_at,
			'token_scopes' => $scopes,
			'app__context' => Context_OAuthApp::ID,
			'app_id' => 0,
			'worker__context' => Context_Worker::ID,
			'worker_id' => 0,
		]);
		
		$app = DAO_OAuthApp::get($oauth_token->app_id);
		$dict->mergeKeys('app_', DevblocksDictionaryDelegate::getDictionaryFromModel($app, Context_OAuthApp::ID));
		
		$worker = DAO_Worker::get($oauth_token->worker_id);
		$dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($worker, CerberusContexts::CONTEXT_WORKER));
		
		return $dict->getDictionary();
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		return match ($key_path) {
			'' => [
				'token:',
			],
			default => [],
		};
	}
}