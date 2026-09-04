<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

use Cerb\Agent\Pane\Components;

/**
 * Setup > AI -- one page of worklist tabs over the records an install's AI is built from, the same
 * shape as Setup > Team. There are no AI settings here yet; when there are, they get a `Configure`
 * tab in front of these, which is why the page is a tab set rather than five menu entries.
 */
class PageSection_SetupAi extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'ai');
		
		$stack = $response->path;
		array_shift($stack); // config
		array_shift($stack); // ai
		$tpl->assign('tab', array_shift($stack));
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/ai/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			return match ($action) {
				'renderTabModels' => $this->_configAction_renderTabModels(),
				'renderTabFiles' => $this->_configAction_renderTabFiles(),
				'renderTabFilesystems' => $this->_configAction_renderTabFilesystems(),
				'renderTabTools' => $this->_configAction_renderTabTools(),
				'renderTabSurfaces' => $this->_configAction_renderTabSurfaces(),
				default => $this->_configAction_renderTabAgents(),
			};
		}
		return false;
	}
	
	/**
	 * Every tab is the same worklist render with a different class, so they share one, rather than the
	 * five copies of it this page would otherwise be.
	 */
	private function _renderWorklistTab(string $view_class, string $view_id, string $name, ?string $query_required = null) : void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass($view_class);
		$defaults->id = $view_id;
		$defaults->name = $name;
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			// Re-locked on every render rather than only when the view is created: a stored worklist
			// outlives this code, so a filter that shipped later would never reach one already saved.
			if(!is_null($query_required))
				$view->setParamsRequiredQuery($query_required);
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	private function _configAction_renderTabAgents() {
		// An agent IS a worker row -- the Search menu's `agents` entry is a facet over `worker` with a
		// locked query, and this tab is the same subset. Read from the manifest rather than repeating
		// `isAi:y` here, so the Setup tab and the Search entry cannot come to mean different things.
		$query_required = 'isAi:y';
		
		if($context_mft = Extension_DevblocksContext::getByAlias('agents', false)) {
			$facets = Extension_DevblocksContext::getSearchFacetsForContext($context_mft);
			$query_required = $facets['agents']['query_required'] ?? $query_required;
		}
		
		$this->_renderWorklistTab('View_Worker', 'config_ai_agents', 'Agents', $query_required);
	}
	
	private function _configAction_renderTabModels() {
		$this->_renderWorklistTab('View_AgentModel', 'config_ai_models', 'Agent Models');
	}
	
	private function _configAction_renderTabFiles() {
		$this->_renderWorklistTab('View_AgentFile', 'config_ai_files', 'Agent Files');
	}
	
	private function _configAction_renderTabFilesystems() {
		$this->_renderWorklistTab('View_AgentFilesystem', 'config_ai_filesystems', 'Agent Filesystems');
	}
	
	private function _configAction_renderTabTools() {
		$this->_renderWorklistTab('View_AgentTool', 'config_ai_tools', 'Agent Tools');
	}
	
	/**
	 * The surface catalog as a reference, the same shape as Setup's Records / Toolbars / Automation Events
	 * pages: a filterable rail over one card per surface.
	 *
	 * `Cerb\Agent\Pane\Components` is the only source. It already warns that it duplicates the hosts by
	 * hand, so a second description of a surface written here would be a third copy of the same facts.
	 */
	private function _configAction_renderTabSurfaces() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('surfaces', $this->_buildSurfaces());
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/ai/tab_surfaces.tpl');
	}
	
	private function _buildSurfaces() : array {
		$surfaces = [];
		
		foreach(Components::getAll() as $key => $meta) {
			$skills_required = [];
			$skills_reference = [];
			
			// A bare entry is lookup material; a keyed one names the work it gates. Components::getAll()
			// documents the split, and the prompt reads them differently, so the page has to as well.
			foreach($meta['skills'] ?? [] as $skill => $gate) {
				if(is_int($skill)) {
					$skills_reference[] = $gate;
				} else {
					$skills_required[] = ['skill' => $skill, 'gate' => $gate];
				}
			}
			
			$tools = [];
			
			foreach($meta['commands'] ?? [] as $bridge => $command)
				$tools[] = $this->_buildSurfaceTool($command, $command['tool'], 'Browser', $bridge);
			
			foreach($meta['server_tools'] ?? [] as $tool_name => $command)
				$tools[] = $this->_buildSurfaceTool($command, $tool_name, 'Server', null);
			
			// Not a path test: Components owns where a role asset lives, and repeating that here is the
			// drift its own header warns about. A role identical to the catalog's copy IS the fallback.
			$role = Components::getRoleFor($key);
			
			$surfaces[] = [
				'key' => $key,
				'slug' => 'surface_' . $key,
				'label' => strval($meta['label'] ?? $key),
				'icon' => strval($meta['icon'] ?? 'bot'),
				'description' => strval($meta['description'] ?? ''),
				'tagline' => strval($meta['tagline'] ?? ''),
				'skills_required' => $skills_required,
				'skills_reference' => $skills_reference,
				'docs' => $meta['docs'] ?? [],
				'tools' => $tools,
				'role' => $role,
				'role_is_fallback' => ($role === trim(strval($meta['instructions'] ?? ''))),
			];
		}
		
		// By label, matching Components::getSurfaceCatalog(), so the rail reads the same on every install.
		usort($surfaces, fn($a, $b) => strcasecmp($a['label'], $b['label']));
		
		return $surfaces;
	}
	
	private function _buildSurfaceTool(array $command, string $tool_name, string $answered_by, ?string $bridge) : array {
		$params = [];
		
		foreach($command['parameters'] ?? [] as $name => $param) {
			$params[] = [
				'name' => $name,
				'description' => strval($param['description'] ?? ''),
				'enum' => $param['enum'] ?? [],
				'required' => boolval($param['required'] ?? false),
			];
		}
		
		$pinned = [];
		
		// Shown because the model never sees them and they win over anything it sends -- which is exactly
		// why an author reading this page needs to know they are there.
		foreach($command['command_params'] ?? [] as $name => $value)
			$pinned[] = ['name' => $name, 'value' => strval($value)];
		
		return [
			'tool' => $tool_name,
			'bridge' => $bridge,
			'answered_by' => $answered_by,
			'icon' => strval($command['icon'] ?? ''),
			'description' => strval($command['description'] ?? ''),
			'label_active' => strval($command['labels']['active'] ?? ''),
			'label_summary' => strval($command['labels']['summary'] ?? ''),
			'parameters' => $params,
			'pinned' => $pinned,
		];
	}
}
