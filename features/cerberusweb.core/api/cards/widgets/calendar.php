<?php
class CardWidget_Calendar extends Extension_CardWidget {
	const ID = 'cerb.card.widget.calendar';

	function invoke(string $action, Model_CardWidget $model) {
		// Nav + event fetching are client-side (CerbUI.Calendar → c=ui&a=calendarEventsJson).
		return false;
	}

	function render(Model_CardWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		$target_calendar_id = $model->extension_params['calendar_id'] ?? null;

		if(!$target_calendar_id) {
			echo "A calendar isn't linked to this widget. Configure it to select one.";
			return;
		}

		// Resolve the configured calendar id (may contain {{placeholders}} against the host record).
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker ? $active_worker->id : 0,
		]);

		$calendar_id = intval($tpl_builder->build($target_calendar_id, $dict));

		if(!($calendar = DAO_Calendar::get($calendar_id))) {
			echo "The linked calendar could not be found.";
			return;
		}

		if(!Context_Calendar::isReadableByActor($calendar, $active_worker))
			return;

		$default_view = $model->extension_params['default_view'] ?? 'month';

		$calendar->displayWidget($tpl, $active_worker, $default_view);
	}

	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/calendar/config.tpl');
	}

	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}
