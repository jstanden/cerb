<?php
$yaml = file_get_contents('./demo-tickets.yaml');
$tickets_data = yaml_parse($yaml, -1);

$records_json = [];

// Generate a random time series over a given range

$now_ts = strtotime('now');
$from_ts = strtotime('-6 days 8 hours 00:00');
$to_ts = $now_ts;
$timestamps = [];
for($x=0;$x<count($tickets_data);$x++) {
	$timestamps[] = mt_rand($from_ts, $to_ts);
}
sort($timestamps);

// Iterate ticket meta

foreach($tickets_data as $ticket_idx => $ticket) {
	$ticket_idx++;
	$ticket_uid = sprintf('ticket_demo_%03d', $ticket_idx);
	$ticket_created_ts = $timestamps[$ticket_idx-1];
	$ticket_created_ts_offset = $ticket_created_ts - $now_ts;
	
	$ticket_json = [
		'uid' => $ticket_uid,
		'_context' => 'ticket',
		'group_id' => sprintf('{{{uid.%s}}}', $ticket['group']),
		'bucket_id' => sprintf('{{{uid.%s}}}', $ticket['bucket']),
		'mask' => sprintf('DEMO-%03d', $ticket_idx),
		'subject' => $ticket['subject'],
		'importance' => 50,
		'status' => 'open',
		'org_id' => sprintf('{{{uid.%s}}}', $ticket['org']),
		'spam_score' => 0.0001,
		'spam_training' => 'N',
		'created' => sprintf("{{{'%d seconds'|date('U')}}}", $ticket_created_ts_offset),
		'updated' => sprintf("{{{'%d seconds'|date('U')}}}", $ticket_created_ts_offset),
		'participants' => [],
	];
	
	$records_json[$ticket_uid] = $ticket_json;
	
	$message_created_ts = $ticket_created_ts;
	$message_created_ts_offset = $ticket_created_ts_offset;
	$response_time_secs = 0;
	
	if(array_key_exists('messages', $ticket))
	foreach($ticket['messages'] as $msg_idx => $message) {
		$msg_idx++;
		$message_uid = sprintf('message_demo_%03d_%03d', $ticket_idx, $msg_idx);
		$message_id_header = sprintf('demo%d.msg%d@cerb.example', $ticket_idx, $msg_idx);
		
		$is_outgoing = array_key_exists('worker', $message);
		
		if($msg_idx > 1) {
			$response_time_secs = mt_rand(150,7200);
			$message_created_ts_offset = $message_created_ts_offset + $response_time_secs;
			$records_json[$ticket_uid]['updated'] = sprintf("{{{'%d seconds'|date('U')}}}", $message_created_ts_offset);
		}
		
		$message_json = [
			'uid' => $message_uid,
			'_context' => 'message',
			'ticket_id' => sprintf('{{{uid.%s}}}', $ticket_uid),
			'created' => sprintf("{{{'%d seconds'|date('U')}}}", $message_created_ts_offset),
			'response_time' => $is_outgoing ? $response_time_secs : 0,
			'is_outgoing' => $is_outgoing ? 1 : 0,
			'hash_header_message_id' => sha1($message_id_header),
		];
		
		if($is_outgoing) {
			$message_json['sender_id'] = '{{{default.replyto_id}}}';
			$message_json['worker_id'] = sprintf('{{{uid.worker_%s}}}', $message['worker']);
			
			// Set the owner to the first replying worker
			if(!array_key_exists('owner_id', $records_json[$ticket_uid]))
				$records_json[$ticket_uid]['owner_id'] = $message_json['worker_id'];
			
		} else {
			$sender_uid = 'address_' . str_replace(['.','@'],['_dot_','_at_'],$message['sender']);
			$message_json['sender_id'] = sprintf('{{{uid.%s}}}', $sender_uid);
			$records_json[$ticket_uid]['participants'] = 
				array_unique(
					array_merge(
						$records_json[$ticket_uid]['participants'],
						[$message['sender']]
					)
				);
		}
		
		$message_json['headers'] = sprintf("To: %s\r\n".
			"From: %s\r\n".
			"Subject: %s\r\n".
			"Date: {{{'%d seconds'|date('r')}}}\r\n".
			"Content-Type: text/plain; charset=utf-8\r\n".
			"Message-Id: <%s>\r\n",
			$is_outgoing ? implode(',', $records_json[$ticket_uid]['participants']) : '{{{default.replyto_email}}}',
			$is_outgoing ? '{{{default.replyto_email}}}' : $message['sender'],
			($msg_idx > 1 ? 'Re: ' : '') . $ticket['subject'],
			$message_created_ts_offset,
			$message_id_header
		);
		
		$message_json['content'] = $message['content'] ?: '(no content)';
		
		$records_json[$message_uid] = $message_json;
	}
	
	$records_json[$ticket_uid]['participants'] = implode(',', $records_json[$ticket_uid]['participants']);
}

echo json_encode(array_values($records_json), JSON_PRETTY_PRINT);