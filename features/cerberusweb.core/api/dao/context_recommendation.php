<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_ContextRecommendation {
	static function add($context, $context_id, $worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("REPLACE INTO context_recommendation (context, context_id, worker_id) ".
			"VALUES (%s, %d, %d)",
			$db->qstr($context),
			$context_id,
			$worker_id
		);
		$db->ExecuteMaster($sql);
	}
	
	static function remove($context, $context_id, $worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM context_recommendation WHERE context = %s AND context_id = %d AND worker_id = %d",
			$db->qstr($context),
			$context_id,
			$worker_id
		);
		$db->ExecuteMaster($sql);
	}
	
	static function removeAll($context, $context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM context_recommendation WHERE context = %s AND context_id = %d",
			$db->qstr($context),
			$context_id
		);
		$db->ExecuteMaster($sql);
	}
	
	static function get($context, $context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$workers = DAO_Worker::getAll();
		$recommendations = array();
		
		$results = $db->GetArray(sprintf("SELECT worker_id FROM context_recommendation WHERE context = %s AND context_id = %d",
			$db->qstr($context),
			$context_id
		));
		
		foreach($results as $row) {
			$worker_id = $row['worker_id'];
			
			if(!isset($workers[$worker_id]))
				continue;
			
			$recommendations[$worker_id] = $workers[$worker_id];
		}
		
		return $recommendations;
	}
	
	static function getByContexts($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$recommendations = array();
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int', array('nonzero', 'unique'));
		
		if(!empty($context_ids)) {
			$results = $db->GetArray(sprintf("SELECT context_id, worker_id FROM context_recommendation WHERE context = %s AND context_id IN (%s)",
				$db->qstr($context),
				implode(',', $context_ids)
			));
			
			if(is_array($results))
			foreach($results as $row) {
				if(!isset($recommendations[$row['context_id']]))
					$recommendations[$row['context_id']] = array();
				
				$recommendations[$row['context_id']][] = $row['worker_id'];
			}
		}
		
		return $recommendations;
	}
	
	private static function _computeSkillQualification($required_competencies, $competencies) {
		
		if(empty($required_competencies))
			return 0;
		
		// Compare required to actual competencies
		
		$weights = array();
		$is_qualified = true;
		$score = 0.0;
		
		foreach($required_competencies as $skill_id => $required_level) {
			@$level = intval($competencies[$skill_id]);
			
			$diff = ($level - $required_level) / 100;
			
			if($diff < 0)
				$is_qualified = false;
			
			$weights[$skill_id] = $diff;
		}
		
		if(!$is_qualified) {
			// Remove all positive qualifications for scoring
			$weights = array_filter($weights, function($w) {
				return $w < 0;
			});
			
			$score = array_sum($weights)/count($required_competencies);
			
		} else {
			$score = array_sum($weights)/count($required_competencies);
		}
		
		return $score;
	}
	
	private static function _getWorkerQualificationsFor($context, $context_id) {
		$required_competencies = DAO_Skill::getContextSkillLevels($context, $context_id);
		
		$workers = DAO_Worker::getAllActive();
		$worker_scores = array();
		
		foreach($workers as $worker_id => $worker) {
			$competencies = DAO_Skill::getContextSkillLevels(CerberusContexts::CONTEXT_WORKER, $worker_id);
			$score = self::_computeSkillQualification($required_competencies, $competencies);
			$worker_scores[$worker_id] = $score;
		}
		
		// Return sorted by distance to 0, all positive first
		uasort($worker_scores, function($a, $b) {
			if($a == $b)
				return 0;
			
			if($a >= 0 && $b < 0)
				return -1;
			
			if($b >= 0 && $a < 0)
				return 1;
			
			if($a < 0 && $b < 0)
				return ($a < $b) ? 1 : -1;
			
			return (0 - $a > 0 - $b) ? -1 : 1;
		});
		
		return $worker_scores;
	}
	
	private static function _computeApproachability($worker_qualifications) {
		$count = count($worker_qualifications);
		$qualified = 0;
		
		foreach($worker_qualifications as $score) {
			if($score >= 0)
				$qualified++;
		}
		
		// Create a score from -1 to 1, with 0 as median
		return ($qualified/($count/2))-1;
	}
	
	private static function _computeInvolvementForTicket(Model_Ticket $ticket) {
		$participants = $ticket->getParticipants();
		$worker_participants = @$participants[CerberusContexts::CONTEXT_WORKER] ?: array();
		$involvements = array();
		$total_interactions = 0;
		
		// We only care about worker participants
		if(is_array($worker_participants)) {
			foreach($worker_participants as $worker_id => $hits) {
				$total_interactions += $hits;
			}
			
			foreach($worker_participants as $worker_id => $hits) {
				// Create a score from -1 to 1, with 0 as median
				$involvements[$worker_id] = ($hits / ($total_interactions/2)) - 1;
			}
		}
		
		return $involvements;
	}
	
	// [TODO] This could be implemented per context later, with NN
	static function nominate($group_id, $bucket_id, $workers=null) {
		return self::_generateRecommendations($group_id, $bucket_id, $workers);
	}
	
	// [TODO] This could be implemented per context later, with NN
	static function prioritize($group_id, $bucket_id, $workers=null) {
		return self::_generateRecommendations($group_id, $bucket_id, $workers);
	}
	
	private static function _generateRecommendations($group_id, $bucket_id, $workers=null) {
		if(!is_array($workers))
			$workers = DAO_Worker::getAllActive();
		
		$ranked = array();

		$group_responsibilities = DAO_Group::getResponsibilities($group_id);
		
		if(empty($group_responsibilities) || !is_array($group_responsibilities))
			return $ranked;
		
		if(isset($group_responsibilities[$bucket_id]))
		foreach($group_responsibilities[$bucket_id] as $worker_id => $responsibility_level) {
			$ranked[$worker_id] = array(
				'score' => $responsibility_level,
				'bits' => array(),
				'metrics' => array(
					'responsibility' => ($responsibility_level/50)-1,
				),
			);
		}
		
		uasort($ranked, function($a, $b) {
			if($a['score'] == $b['score'])
				return 0;
			
			return ($a['score'] < $b['score']) ? 1 : -1;
		});
		
		return $ranked;
	}
	
}