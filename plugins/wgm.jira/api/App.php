<?php
class WgmJira_API {
	private $_base_url = '';
	private $_user = '';
	private $_password = '';
	private $_errors = [];
	
	public function setBaseUrl($url) {
		$this->_base_url = rtrim($url,'/');
	}
	
	public function setAuth($user, $password) {
		$this->_user = $user;
		$this->_password = $password;
	}
	
	function getMyself() {
		return $this->_get('/rest/api/2/myself');
	}
	
	function getServerInfo() {
		return $this->_get('/rest/api/2/serverInfo');
	}
	
	function getStatuses() {
		return $this->_get('/rest/api/2/status');
	}
	
	function getProjects() {
		return $this->_get('/rest/api/2/project');
	}
	
	function getProject($key) {
		return $this->_get(sprintf("/rest/api/2/project/%s", $key));
	}
	
	function getIssues($jql, $maxResults=100, $fields=null, $startAt=0) {
		$params = array(
			'jql' => $jql,
			'maxResults' => $maxResults,
			'startAt' => $startAt,
		);
		
		if(!empty($fields) && is_string($fields))
			$params['fields'] = $fields;
		
		return $this->_get('/rest/api/2/search', $params);
	}
	
	function getIssueByKey($key) {
		$response = $this->getIssues(
			sprintf("key='%s'", $key),
			1,
			'summary,created,updated,description,status,issuetype,fixVersions,project,comment',
			0
		);
		
		if($response['issues'])
			return current($response['issues']);
		
		return false;
	}
	
	function getIssueCreateMeta() {
		$params = array(
			'expand' => 'projects.issuetypes.fields',
		);
		return $this->_get(sprintf("/rest/api/2/issue/createmeta"), $params);
	}
	
	function postCreateIssueJson($json) {
		return $this->_postJson('/rest/api/2/issue', null, $json);
	}
	
	function postCommentIssueJson($key, $json) {
		return $this->_postJson(sprintf('/rest/api/2/issue/%s/comment', $key), null, $json);
	}
	
	function getLastError() {
		return current($this->_errors);
	}
	
	function execute($verb, $path, $params=[], $json=null) {
		$response = null;
		
		switch($verb) {
			case 'get':
				$response = $this->_get($path, $params);
				break;
				
			case 'post':
			case 'put':
				$response = $this->_postJson($path, $params, $json, $verb);
				break;
				
			case 'delete':
				// [TODO]
				break;
		}
		
		$this->_reimportApiChanges($verb, $path, $response);
		
		return $response;
	}
	
	private function _postJson($path, $params=[], $json=null, $verb='post') {
		if(empty($this->_base_url))
			return false;
		
		$url = $this->_base_url . $path;
		
		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		$headers = [];
		
		$headers[] = 'Content-Type: application/json';
		
		if(!empty($this->_user)) {
			$headers[]= 'Authorization: Basic ' . base64_encode(sprintf("%s:%s", $this->_user, $this->_password));
		}
		
		switch($verb) {
			case 'post':
				curl_setopt($ch, CURLOPT_POST, 1);
				break;
				
			case 'put':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POST, 1);
				//curl_setopt($ch, CURLOPT_PUT, 1);
				//$headers[] = 'X-HTTP-Method-Override: PUT';
				//$headers[] = 'Content-Length: ' . strlen($json);
				break;
		}
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		
		if(!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$out = DevblocksPlatform::curlExec($ch);
		
		//$info = curl_getinfo($ch);

		// [TODO] This can fail without HTTPS
		
		if(curl_errno($ch)) {
			$this->_errors = array(curl_error($ch));
			$json = false;
			
		} elseif(!empty($out) && false == ($json = json_decode($out, true))) {
			$this->_errors = array('Error decoding JSON response');
			$json = false;
			
		} else {
			$this->_errors = [];
		}
		
		curl_close($ch);
		return $json;
	}
	
	private function _get($path, $params=[]) {
		if(empty($this->_base_url))
			return false;
		
		$url = $this->_base_url . $path;

		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		if(!empty($this->_user)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Basic ' . base64_encode(sprintf("%s:%s", $this->_user, $this->_password)),
			));
		}
		
		$out = DevblocksPlatform::curlExec($ch);

		//$info = curl_getinfo($ch);
		
		// [TODO] This can fail without HTTPS
		
		if(curl_errno($ch)) {
			$this->_errors = array(curl_error($ch));
			$json = false;
			
		} elseif(!empty($out) && false == ($json = json_decode($out, true))) {
			$this->_errors = array('Error decoding JSON response');
			$json = false;
			
		} else {
			$this->_errors = [];
		}
		
		curl_close($ch);
		return $json;
	}
	
	// Special handling for API responses (e.g. recache)
	private function _reimportApiChanges($verb, $path, $response) {
		$matches = [];
		
		// Create issue
		if($verb == 'post' && $path == '/rest/api/2/issue') {
			if(isset($response['key'])) {
				if(false !== ($issue = WgmJira_API::getIssueByKey($response['key'])))
					WgmJira_API::importIssue($issue);
			}

		// Change issue
		} elseif(in_array($verb, array('post', 'put')) && preg_match('#^/rest/api/2/issue/(.*?)(/.*)*$#', $path, $matches)) {
			if(false !== ($issue = WgmJira_API::getIssueByKey($matches[1])))
				WgmJira_API::importIssue($issue);
			
		}
	}
	
	static public function importIssue($object, Model_JiraProject $project=null) {
		$is_new = false;
		
		if(is_null($project)) {
			$project = DAO_JiraProject::getByJiraId($object['fields']['project']['id']);
		}
		
		// Fix versions
		
		$fix_versions = [];
		
		if(is_array($object['fields']['fixVersions']))
		foreach($object['fields']['fixVersions'] as $fix_version) {
			$fix_versions[$fix_version['id']] = $fix_version['name'];
		}
		
		$local_issue = DAO_JiraIssue::getByJiraIdForBackend($object['id'], $project->connected_account_id);
		
		// Fields
		
		$fields = [
			DAO_JiraIssue::JIRA_ID => $object['id'],
			DAO_JiraIssue::JIRA_KEY => $object['key'],
			DAO_JiraIssue::JIRA_VERSIONS => implode(', ', $fix_versions),
			DAO_JiraIssue::STATUS => $object['fields']['status']['name'],
			DAO_JiraIssue::TYPE => $object['fields']['issuetype']['name'],
			DAO_JiraIssue::JIRA_PROJECT_ID => $project->jira_id,
			DAO_JiraIssue::PROJECT_ID => $project->id,
			DAO_JiraIssue::SUMMARY => $object['fields']['summary'],
			DAO_JiraIssue::DESCRIPTION => $object['fields']['description'],
			DAO_JiraIssue::CREATED => strtotime($object['fields']['created']),
			DAO_JiraIssue::UPDATED => strtotime($object['fields']['updated']),
		];
		
		if(!empty($local_issue)) {
			$local_issue_id = $local_issue->id;
			DAO_JiraIssue::update($local_issue_id, $fields);

		} else {
			$local_issue_id = DAO_JiraIssue::create($fields);
			$is_new = true;
		}

		// Comments
		
		if(isset($object['fields']['comment']['comments']) && is_array($object['fields']['comment']['comments']))
		foreach($object['fields']['comment']['comments'] as $comment) {
			DAO_JiraIssue::saveComment(
				$comment['id'],
				$object['id'],
				$local_issue_id,
				@strtotime($comment['created']),
				$comment['author']['displayName'],
				$comment['body']
			);
		}
		
		// Trigger 'New JIRA issue created' event
		if($is_new) {
			Event_JiraIssueCreated::trigger($local_issue_id);
		}
		
		return $local_issue_id;
	}
};

class WgmJira_Cron extends CerberusCronPageExtension {
	const ID = 'wgmjira.cron';

	public function run() {
		$logger = DevblocksPlatform::services()->log("JIRA");
		$logger->info("Started");
		
		$this->_synchronize();

		$logger->info("Finished");
	}

	function _synchronize() {
		@$max_projects = DevblocksPlatform::importGPC($_REQUEST['max_projects'],'integer', 20);
		@$max_issues = DevblocksPlatform::importGPC($_REQUEST['max_issues'],'integer', 20);
		
		$jira_projects = DAO_JiraProject::getWhere(
			sprintf("%s > 0",
				DAO_JiraProject::CONNECTED_ACCOUNT_ID
			),
			DAO_JiraProject::LAST_CHECKED_AT,
			true,
			$max_projects
		);
		
		$logger = DevblocksPlatform::services()->log("JIRA");
		
		foreach($jira_projects as $jira_project) {
			DAO_JiraProject::update($jira_project->id, [ DAO_JiraProject::LAST_CHECKED_AT => time() ]);
			
			if(false == ($connected_account = $jira_project->getConnectedAccount()))
				continue;
			
			$account_params = $connected_account->decryptParams();
			
			$service = $connected_account->getService();
			$service_params = $service->decryptParams();
			
			$jira = new WgmJira_API();
			$jira->setBaseUrl($service_params['base_url']);
			$jira->setAuth($account_params['username'], $account_params['password']);
			
			if(false == ($json = $jira->getMyself()) || !isset($json['displayName'])) {
				$logger->error('Failed to connect to JIRA API using account: '. $connected_account->name);
				continue;
			}
			
			// Update the local JIRA record if we don't have an internal ID
			if(!$jira_project->jira_id) {
				// Pull the full record for each project and merge with createmeta
				if(false == ($project = $jira->getProject($jira_project->jira_key)) || array_key_exists('errors', $project)) {
					$logger->info(sprintf("Couldn't find project with key '%s'", $jira_project->jira_key));
					continue;
				}
				
				$logger->info(sprintf("Updating local project record for %s [%s]", $project['name'], $project['key']));
				
				$fields = [
					DAO_JiraProject::JIRA_ID => $project['id'],
					DAO_JiraProject::NAME => $project['name'],
				];
				
				if(empty($jira_project->url))
					$fields[DAO_JiraProject::URL] = isset($project['url']) ? $project['url'] : '';
				
				DAO_JiraProject::update($jira_project->id, $fields, false);
			}
			
			$logger->info(sprintf("Syncing issues for project %s [%s]", $jira_project->name, $jira_project->jira_key));
			
			$startAt = 0;
			$maxResults = $max_issues;
			
			$last_synced_at = $jira_project->last_synced_at;
			$last_synced_checkpoint = $jira_project->last_synced_checkpoint;
			
			$jql = sprintf("project='%s' AND ((updated = %d000 AND created > %d000) OR (updated > %d000)) ORDER BY updated ASC, created ASC",
				$jira_project->jira_key,
				$last_synced_at,
				$last_synced_checkpoint,
				$last_synced_at
			);
			
			$logger->info(sprintf("JQL: %s", $jql));
			
			if(false != ($response = $jira->getIssues(
				$jql,
				$maxResults,
				'summary,created,updated,description,status,issuetype,fixVersions,project,comment',
				$startAt
			))) {
				if(isset($response['issues']))
				foreach($response['issues'] as $object) {
					WgmJira_API::importIssue($object, $jira_project);
					
					$last_synced_at = strtotime($object['fields']['updated']);
					$last_synced_checkpoint = strtotime($object['fields']['created']);
				}
			}
			
			// Set the last updated date on the project
			DAO_JiraProject::update($jira_project->id, [
				DAO_JiraProject::LAST_SYNCED_AT => $last_synced_at,
				DAO_JiraProject::LAST_SYNCED_CHECKPOINT => $last_synced_checkpoint,
			]);
		}
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->cache_lifetime = "0";

		// Load settings
		/*
		$clients_updated_from = $this->getParam('clients.updated_from', 0);
		if(empty($clients_updated_from))
			$clients_updated_from = gmmktime(0,0,0,1,1,2000);

		$tpl->assign('clients_updated_from', $clients_updated_from);

		$tpl->display('devblocks:wgm.freshbooks::config/cron.tpl');
		*/
	}

	public function saveConfigurationAction() {
		/*
		@$clients_updated_from = DevblocksPlatform::importGPC($_POST['clients_updated_from'], 'string', '');

		// Save settings
		$clients_timestamp = intval(@strtotime($clients_updated_from));
		if(!empty($clients_timestamp))
			$this->setParam('clients.updated_from', $clients_timestamp);
		*/
	}
};