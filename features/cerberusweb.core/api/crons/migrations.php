<?php
class Cron_Migrations extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log('Migrations');
		$runtime = microtime(true);

		$stop_time = time() + 20;

		$logger->info("Starting...");
		
		do {
			if(false == $this->checkQueueAndProcess())
				break;
			
		} while($stop_time > time());
		
		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
	
	function checkQueueAndProcess() : bool {
		$queue = DevblocksPlatform::services()->queue();
		$logger = DevblocksPlatform::services()->log('Migrations');
		
		$consumer_id = null;
		$limit = 1;
		
		$messages = $queue->dequeue('cerb.update.migrations', $limit, $consumer_id);
		
		if(empty($messages))
			return false;
		
		$results = $this->_processMessages($messages);
		
		$queue->reportSuccess($results['success'] ?? []);
		$queue->reportFailure($results['fail'] ?? []);
		
		$processed = count($results['success'] ?? []);
		
		if($processed)
			$logger->info(sprintf("Processed %d migration jobs", $processed));
		
		return true;
	}
	
	private function _processMessages(array $messages) : array {
		$results = [
			'success' => [],
			'fail' => [],
		];
		
		foreach($messages as $message) { /* @var Model_QueueMessage $message */
			$job = $message->message['job'] ?? null;
			
			if(!$job) {
				$results['fail'][] = $message->uuid;
				continue;
			}
			
			$result = false;
			
			switch($job) {
				case 'dao.ticket.rebuild.elapsed_status_open':
					$result = $this->_updateTicketsTimeSpentOpen($message);
					break;
			}
			
			if($result) {
				$results['success'][] = $message->uuid;
			} else {
				$results['fail'][] = $message->uuid;
			}
		}
		
		return $results;
	}
	
	private function _updateTicketsTimeSpentOpen(Model_QueueMessage $message) {
		$db = DevblocksPlatform::services()->database();
		
		if(
			($message->message['job'] ?? null) != 'dao.ticket.rebuild.elapsed_status_open'
			|| !array_key_exists('params', $message->message)
			|| !array_key_exists('from_id', $message->message['params'])
			|| !array_key_exists('to_id', $message->message['params'])
		) {
			return false;
		}
		
		$from_id = $message->message['params']['from_id'] ?? 0;
		$to_id = $message->message['params']['to_id'] ?? 0;
		
		$sql = <<< EOD
		CREATE TEMPORARY TABLE _tmp_tickets_spent_open
		SELECT
			t.id,
			_join.`for`
		FROM
			ticket t
			INNER JOIN (
				SELECT
					context_activity_log.target_context_id,
					SUM(
						context_activity_log.created - IFNULL(
							(
								SELECT
									IF(
										activity_point = 'ticket.status.open',
										cl.created,
										context_activity_log.created
									)
								FROM
									context_activity_log cl
								WHERE
									cl.target_context = context_activity_log.target_context
									AND cl.target_context_id = context_activity_log.target_context_id
									AND activity_point IN (
										'ticket.status.open',
										'ticket.status.waiting',
										'ticket.status.closed',
										'ticket.status.deleted'
									)
									AND cl.created < context_activity_log.created
								ORDER BY
									cl.id DESC
								LIMIT
									1
							), (
								SELECT
									origin.created
								FROM
									context_activity_log origin
								WHERE
									origin.target_context = context_activity_log.target_context
									AND origin.target_context_id = context_activity_log.target_context_id
								ORDER BY
									origin.id
								LIMIT
									1
							)
						)
					) AS `for`
				FROM
					context_activity_log
				WHERE
					target_context = 'cerberusweb.contexts.ticket'
					AND activity_point IN (
						'ticket.status.waiting',
						'ticket.status.closed',
						'ticket.status.deleted'
					)
				GROUP BY
					target_context_id
				HAVING
					`for` > 0
			) AS _join ON (_join.target_context_id = t.id)
		WHERE
			t.id BETWEEN %d AND %d
		EOD;
		
		if(!$db->ExecuteMaster(sprintf($sql, $from_id, $to_id)))
			return false;
		
		/** @noinspection SqlResolve */
		if(!$db->ExecuteMaster("UPDATE ticket INNER JOIN _tmp_tickets_spent_open tse on (tse.id=ticket.id) SET ticket.elapsed_status_open=tse.for"))
			return false;
		
		/** @noinspection SqlResolve */
		if(!$db->ExecuteMaster('DROP TABLE _tmp_tickets_spent_open'))
			return false;
		
		return true;
	}
};