<?php
class UmScHomeController extends Extension_UmScController {
	const PARAM_HOME_MARKDOWN = 'home.markdown';

	function isVisible() {
		return true;
	}

	public function invoke(string $action, ?DevblocksHttpRequest $request=null) {
		return false;
	}

	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();

		$home_markdown = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_HOME_MARKDOWN, '');
		$home_html = $home_markdown ? DevblocksPlatform::parseMarkdown($home_markdown) : '';
		$tpl->assign('home_html', $home_html);

		$tpl->display("devblocks:cerberusweb.support_center:portal_" . ChPortalHelper::getCode() .":support_center/home/index.tpl");
	}

	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();

		$home_markdown = DAO_CommunityToolProperty::get($instance->code, self::PARAM_HOME_MARKDOWN, '');
		$tpl->assign('home_markdown', $home_markdown);

		$tpl->assign('portal', $instance);
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/home.tpl");
	}

	function saveConfiguration(Model_CommunityTool $instance) {
		$home_markdown = DevblocksPlatform::importGPC($_POST['home_markdown'] ?? null, 'string', '');
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_HOME_MARKDOWN, $home_markdown);
	}
};
