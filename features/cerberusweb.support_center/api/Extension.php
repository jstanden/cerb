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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

abstract class Extension_UmScRssController extends DevblocksExtension {
	const POINT = 'usermeet.sc.rss.controller';
	
	function handleRequest(DevblocksHttpRequest $request) {
		//$path = $request->path;
	}
};

abstract class Extension_UmScController extends DevblocksExtension implements DevblocksHttpRequestHandler {
	const POINT = 'usermeet.sc.controller';
	
	private $portal = '';
	
	/**
	 * @internal
	 */
	public static function getAll($as_instances=false, $with_options=null) {
		$controllers = DevblocksPlatform::getExtensions(self::POINT, $as_instances);
		
		if($as_instances)
			DevblocksPlatform::sortObjects($controllers, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($controllers, 'name');
		
		if(!empty($with_options)) {
			if(!is_array($with_options))
				$with_options = array($with_options);

			foreach($controllers as $k => $controller) {
				$options = $controller->params['options'][0] ?? [];

				if(!is_array($options) || empty($options)) {
					unset($controllers[$k]);
					continue;
				}

				if(count(array_intersect(array_keys($options), $with_options)) != count($with_options))
					unset($controllers[$k]);
			}
		}
		
		return $controllers;
	}

	/**
	 * @internal
	 */
	public static function get($id, $as_instance=true) {
		$controllers = self::getAll(false);
		
		if(!isset($controllers[$id]))
			return null;
		
		$manifest = $controllers[$id]; /* @var $manifest DevblocksExtensionManifest */

		if($as_instance) {
			return $manifest->createInstance();
		} else {
			return $controllers[$id];
		}
		
		return null;
	}
	
	/**
	 * @internal
	 */
	public static function getByUri($uri, $as_instance=true) {
		$controllers = self::getAll(false);
		
		foreach($controllers as $controller) {
			if(0==strcasecmp($controller->params['uri'], $uri)) {
				return DevblocksPlatform::getExtension($controller->id, $as_instance);
			}
		}
		
		return null;
	}
	
	/*
	 * Site Key
	 * Site Name
	 * Site URL
	 */
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path;
		array_shift($path); // controller
		
		$a = DevblocksPlatform::importGPC($_REQUEST['a'] ?? null, 'string');

		$action = strval($a ?: array_shift($path));

		if(!$action)
			return;
		
		// Default action, call arg as a method suffixed with Action
		if(false === $this->invoke($action, new DevblocksHttpRequest($path))) {
			// Not found
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		/* Expect Overload */
	}
	
	public function renderSidebar(DevblocksHttpResponse $response) {
		/* Expect Overload */
		return;
	}
	
	abstract public function isVisible();
	abstract public function invoke(string $action, DevblocksHttpRequest $request=null);
	
	public function configure(Model_CommunityTool $instance) {
		// [TODO] Translate
		echo "This module has no configuration options.<br><br>";
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		/* Expect Overload */
	}
	
};

abstract class Extension_ScLoginAuthenticator extends DevblocksExtension {
	const POINT = 'usermeet.login.authenticator';
	
	abstract function invoke(string $action);
	
	/**
	 * draws html form for adding necessary settings (host, port, etc) to be stored in the db
	 */
	function renderConfigForm(Model_CommunityTool $instance) {
		echo "(no configuration options)";
	}
	
	/**
	 * Receives posted config form, saves to manifest
	 */
	function saveConfiguration(Model_CommunityTool $instance) {
	}
	
	abstract function writeResponse(DevblocksHttpResponse $response);
	
	/**
	 * release any resources tied up by the authenticate process, if necessary
	 */
	function signoff() {
		$umsession = ChPortalHelper::getSession();
		$umsession->logout();
	}
};
