<?php
class AutomationTrigger_PortalInteraction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.portal.interaction';
	
	function renderConfig(Model_Automation $model) {
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	// [TODO] Load from the script
	function getInputsMeta() {
		return;
		
		if(!array_key_exists('inputs_kata', $params))
			return [];
		
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		$inputs_kata = $kata->parse($params['inputs_kata'], $error);
		
		$dict = DevblocksDictionaryDelegate::instance([]);
		$inputs = $kata->formatTree($inputs_kata, $dict);
		
		$results = [];
		
		// [TODO] Sanitize
		if(is_array($inputs))
			foreach($inputs as $input_key => $input_data) {
				list($input_type, $input_key) = explode('/', $input_key);
				
				$input = [
					'key' => $input_key,
					'type' => $input_type,
					'required' => @$input_data['required'] ?? false,
					'params' => @$input_data['params'] ?? [],
					'notes' => @$input_data['notes'] ?? '',
				];
				
				$results[] = $input;
			}
		
		return $results;
	}
	
	function getOutputsMeta() {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
//					'reset:',
//					'continue:',
					[
						'caption' => 'prompt_choice:',
						'snippet' => "prompt_choice/\${1:key}:\n  label: \${2:Make a choice:}\n  #style: radios\n  #orientation: horizontal\n  #default: Option 1\n  required@bool: no\n  options@list:\n    \${3:Option 1}\n",
					],
					[
						'caption' => 'prompt_choices:',
						'snippet' => "prompt_choices/\${1:key}:\n  label: \${2:Make choices:}\n  #default: Option 1\n  required@bool: no\n  options@list:\n    \${3:Option 1}\n",
					],
					[
						'caption' => 'prompt_text:',
						'snippet' => "prompt_text/\${1:key}:\n  label: \${2:Enter some text:}\n  #mode: multiple\n  #default: Text\n  required@bool: no\n",
					],
				],
			]
		];
	}
}

/*
- "respond:"

event/start::await:reset::
	- "yes"
	- "no"

event/start::await:continue::
- "yes"
- "no"

event/start::await:prompt_text::
	- "set:"
	- "label:"
	- "default:"
	- "placeholder:"
	- "mode:"
	- "validate:"

event/start::await:prompt_text:mode::
	- "multiple"
	- "single"

event/start::await:prompt_text:validate::
	- "text:"
	- "number:"

event/start::await:prompt_text:validate:text::
	- caption: "required:"
  snippet: "required: yes\n"
- "min_length:"
- "max_length:"
- "possible_values:"

event/start::await:prompt_text:validate:number::
	- caption: "required:"
  snippet: "required: yes\n"
- "min:"
- "max:"

event/start::await:prompt_choice::
	- "set:"
	- "label:"
	- "options:"
	- "default:"
	- "style:"
	- "orientation:"

event/start::await:prompt_choice:style::
	- radios
	- buttons
	- picklist

event/start::await:prompt_choice:orientation::
	- horizontal
	- vertical

event/start::await:prompt_choices::
	- "set:"
	- "label:"
	- "options:"

event/start::await:respond::
	- "message:"
	- caption: "format:"
  snippet: |-
format: markdown

event/start::return::
- caption: "respond:"
  snippet: |-
respond/${1:label}:
      message: ${2:Hello!}
      #format: markdown

event/start::return:respond::
	- caption: "message:"
  snippet: |-
message: ${1:Hello!}
- caption: "format:"
  snippet: |-
format: markdown
*/