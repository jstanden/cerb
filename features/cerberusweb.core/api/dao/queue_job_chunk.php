<?php
class DAO_QueueJobChunk {
	static function put(int $job_id, int $chunk_idx, string $data) : bool {
		$db = DevblocksPlatform::services()->database();

		return false !== $db->ExecuteWriter(sprintf(
			"REPLACE INTO queue_job_chunk (job_id, chunk_idx, data, created_at) VALUES (%d, %d, %s, %d)",
			$job_id,
			$chunk_idx,
			$db->qstr($data),
			time()
		));
	}

	/**
	 * Stream chunks for a job in `chunk_idx` order, invoking $writer($data, $chunk_idx) per chunk.
	 */
	static function streamByJobId(int $job_id, callable $writer) : int {
		$db = DevblocksPlatform::services()->database();

		$rs = $db->QueryReader(sprintf(
			"SELECT chunk_idx, data FROM queue_job_chunk WHERE job_id = %d ORDER BY chunk_idx ASC",
			$job_id
		));

		if(!($rs instanceof mysqli_result))
			return 0;

		$count = 0;
		while($row = mysqli_fetch_assoc($rs)) {
			$writer($row['data'], intval($row['chunk_idx']));
			$count++;
		}
		mysqli_free_result($rs);

		return $count;
	}

	static function deleteByJobIds(array $job_ids) : void {
		$db = DevblocksPlatform::services()->database();

		$job_ids = DevblocksPlatform::sanitizeArray($job_ids, 'int');

		if(!$job_ids) return;

		$db->ExecuteWriter(sprintf("DELETE FROM queue_job_chunk WHERE job_id IN (%s)",
			implode(',', $job_ids)
		));
	}
}
