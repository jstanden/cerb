<?php
class ProfileWidget_Calendar extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.calendar';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// Nav + event fetching are client-side now (CerbUI.Calendar → c=ui&a=calendarEventsJson).
		return false;
	}

	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		$target_context_id = $model->extension_params['context_id'] ?? null;

		if(!($context_ext = Extension_DevblocksContext::get($context)))
			return;

		$dao_class = $context_ext->getDaoClass();

		if(!($record = $dao_class::get($context_id)))
			return;

		// Resolve the configured calendar id (may contain {{placeholders}} against the host record).

		if(!$target_context_id) {
			echo "A calendar isn't linked to this widget. Configure it to select one.";
			return;
		}

		$labels = $values = $merge_token_labels = $merge_token_values = [];

		CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);

		CerberusContexts::merge(
			'record_',
			'Record:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);

		CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);

		CerberusContexts::merge(
			'widget_',
			'Widget:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);

		$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
		$values['widget_id'] = $model->id;
		$dict = DevblocksDictionaryDelegate::instance($values);

		$calendar_id = intval($tpl_builder->build($target_context_id, $dict));

		if(!($calendar = DAO_Calendar::get($calendar_id))) {
			echo "The linked calendar could not be found.";
			return;
		}

		if(!Context_Calendar::isReadableByActor($calendar, $active_worker))
			return;

		$default_view = $model->extension_params['default_view'] ?? 'month';

		$calendar->displayWidget($tpl, $active_worker, $default_view);
	}

	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);

		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar/config.tpl');
	}

	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
