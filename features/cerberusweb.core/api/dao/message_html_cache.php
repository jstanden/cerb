<?php
class DAO_MessageHtmlCache {
	public static function get(int $id) : ?string {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT html_content FROM message_html_cache WHERE message_id = %d", $id));
	}
	
	public static function set(int $id, string $html) : void {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT IGNORE INTO message_html_cache (message_id, html_content, expires_at) VALUES (%d, %s, %d)",
			$id,
			$db->qstr($html),
			time() + 14400 // 4 hrs
		);
		
		$db->ExecuteMaster($sql);
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		$db->ExecuteMaster(sprintf("DELETE FROM message_html_cache WHERE expires_at < %d", time()));
	}
}