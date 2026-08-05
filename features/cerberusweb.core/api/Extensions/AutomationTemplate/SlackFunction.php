<?php
namespace Cerb\Extensions\AutomationTemplate;

use DAO_ConnectedAccount;
use DevblocksPlatform;
use Cerb\Extensions\Extension_AutomationTemplate;

// "Send a Slack message" — a reusable `automation.function` that posts to Slack (chat.postMessage). The wizard
// picks the Slack connected account (auth) + a default channel; build() bakes them into a function that other
// automations can call via `function:` with `channel` / `message` inputs. Targets the automation.function trigger.
class SlackFunction extends Extension_AutomationTemplate {
	const ID = 'cerb.automation.template.integrations.slack';

	function hasWizard() : bool {
		return true;
	}

	function renderWizard() : string {
		$tpl = DevblocksPlatform::services()->template();
		return $tpl->fetch('devblocks:cerberusweb.core::internal/automation/editor/wizard_slack_function.tpl');
	}

	function build(array $answers) : array {
		return [
			'extension_id' => $this->getTriggerId(),
			'script' => $this->_generateScript($answers),
			// Least-privilege: only allow this one Slack endpoint via POST (mirrors the shipped Slack integration).
			'policy_kata' => "commands:\n  http.request:\n    deny/url@bool: {{inputs.url != 'https://slack.com/api/chat.postMessage'}}\n    deny/method@bool: {{inputs.method != 'POST'}}\n    allow@bool: yes",
		];
	}

	private function _generateScript(array $answers) : string {
		$channel = trim(preg_replace('/[\r\n]+/', ' ', strval($answers['channel'] ?? '')));
		if('' === $channel)
			$channel = '#general';

		// Resolve the picked connected account to a readable cerb-uri (id → uri, else the id).
		$account_id = strval($answers['account_id'] ?? '');
		$account_ref = $account_id;
		if('' !== $account_id && ($account = DAO_ConnectedAccount::get($account_id)) && strlen(strval($account->uri ?? '')))
			$account_ref = $account->uri;

		$skeleton = <<<'KATA'
# A reusable function that posts a message to a Slack channel (chat.postMessage). Call it from another
# automation with `function:` — pass a `message` (and optionally override the `channel`).

#inputs:
#  text/channel:
#    default@text: __CHANNEL__
#  text/message:
#    required@bool: yes

start:
  set:
    payload:
      channel: {{inputs.channel ?: '__CHANNEL__'}}
      text: {{inputs.message}}

  http.request/postMessage:
    output: response
    inputs:
      # See: https://api.slack.com/methods/chat.postMessage
      url: https://slack.com/api/chat.postMessage
      method: POST
      authentication: cerb:connected_account:__ACCOUNT__
      headers@text:
        Content-Type: application/json; charset=utf8
      body: {{payload|json_encode}}

  return:
    ok: {{response.body.ok}}
KATA;

		$script = str_replace('__CHANNEL__', $channel, $skeleton);
		$script = str_replace('__ACCOUNT__', $account_ref, $script);

		return $script;
	}
}
