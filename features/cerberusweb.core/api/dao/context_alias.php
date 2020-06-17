<?php
class DAO_ContextAlias extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const ID = 'id';
	const IS_PRIMARY = 'is_primary';
	const NAME = 'name';
	const TERMS = 'terms';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_PRIMARY)
			->bit()
			;
		$validation
			->addField(self::NAME)
			->string()
			;
		$validation
			->addField(self::TERMS)
			->string()
			;
			
		return $validation->getFields();
	}
	
	static function get($context, $id, $with_primary=false) {
		$db = DevblocksPlatform::services()->database();
		
		$results = $db->GetArrayReader(sprintf("SELECT name FROM context_alias WHERE context = %s AND id = %d %s",
			$db->qstr($context),
			$id,
			($with_primary ? 'ORDER BY is_primary DESC' : 'AND is_primary = 0')
		));
		
		return array_column($results, 'name');
	}
	
	static function set($context, $id, array $aliases) {
		if(empty($context) || empty($id) || empty($aliases))
			return false;
		
		$aliases = array_unique($aliases);
		
		$db = DevblocksPlatform::services()->database();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		self::delete($context, $id);
		
		$values = [];
		
		foreach($aliases as $idx => $alias) {
			$terms = DevblocksPlatform::strAlphaNum($alias, ' ', '');
			$tokens = $bayes::preprocessWordsPad($bayes::tokenizeWords($terms), 4);
			
			$values[] = sprintf("(%s,%d,%s,%s,%d)",
				$db->qstr($context),
				$id,
				$db->qstr($alias),
				$db->qstr(implode(' ', $tokens)),
				$idx == 0 ? 1 : 0
			);
		}
		
		if(!empty($values)) {
			$sql = sprintf("INSERT IGNORE INTO context_alias (context,id,name,terms,is_primary) VALUES %s",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
		}
	}
	
	static function prepare($terms) {
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		if(!is_array($terms))
			$terms = [$terms];
		
		$wheres = [];
		
		foreach($terms as $term) {
			$term = DevblocksPlatform::strAlphaNum($term, ' ', '');
			$tokens = $bayes::preprocessWordsPad($bayes::tokenizeWords($term), 4);
			$wheres[] = implode(' ', $tokens);
		}
		
		if(empty($wheres))
			return [];
		
		return $wheres;
	}
	
	static function query($terms, $context) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($terms))
			$terms = [$terms];
		
		$wheres = self::prepare($terms);
		
		$results = $db->GetArrayReader(sprintf("SELECT id FROM context_alias WHERE context = %s AND terms IN (%s)",
			$db->qstr($context),
			implode(',', $db->qstrArray($wheres))
		));
		
		return array_column($results, 'id');
	}
	
	/*
	static function upsert($context, $id, array $aliases) {
		if(empty($context) || empty($id) || empty($aliases))
			return false;
		
		$aliases = array_unique($aliases);
		
		$db = DevblocksPlatform::services()->database();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		$values = [];
		
		foreach($aliases as $idx => $alias) {
			$terms = DevblocksPlatform::strAlphaNum($alias, ' ', '');
			$tokens = $bayes::preprocessWordsPad($bayes::tokenizeWords($terms), 4);
			
			$values[] = sprintf("(%s,%d,%s,%s,%d)",
				$db->qstr($context),
				$id,
				$db->qstr($alias),
				$db->qstr(implode(' ', $tokens)),
				$idx == 0 ? 1 : 0
			);
		}
		
		if(!empty($values)) {
			$sql = sprintf("REPLACE IGNORE INTO context_alias (context,id,name,terms,is_primary) VALUES %s",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
		}
	}
	*/
	
	static function delete($context, $ids) {
		if(!is_array($ids)) {
			if(!is_numeric($ids))
				return false;
			
			$ids = [$ids];
		}
		
		$ids_string = implode(',', DevblocksPlatform::sanitizeArray($ids, 'int'));
		
		if(empty($ids_string))
			return false;
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM context_alias WHERE context = %s AND id IN (%s)",
			$db->qstr($context),
			$ids_string
		);
		return $db->ExecuteMaster($sql);
	}
};