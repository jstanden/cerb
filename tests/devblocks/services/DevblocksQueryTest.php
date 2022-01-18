<?php
use PHPUnit\Framework\TestCase;

class DevblocksQueryTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testTokensTextoQuery() {
		// Params
		
		$query = <<< EOD
			query:(
			  cerb
			)
			EOD;
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		$this->assertArrayHasKey(0, $fields);
		
		$actual = CerbQuickSearchLexer::getTokensAsQuery($fields[0]->tokens);
		$expected = '(cerb)';
		
		$this->assertEquals($expected, $actual);
	}
	
	function testTokensTextoQueryWithBindings() {
		// Params
		
		$query = <<< EOD
			query:(
			  \${text}
			)
			EOD;
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($query, [
			'text' => 'cerb'
		]);
		
		$this->assertArrayHasKey(0, $fields);
		
		$actual = CerbQuickSearchLexer::getTokensAsQuery($fields[0]->tokens);
		$expected = '("cerb")';
		
		$this->assertEquals($expected, $actual);
	}
	
	function testTokensToQuery() {
		// Params
		
		$query = <<< EOD
			query:(group.id:[1] group.id:[2])
			EOD;
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		$this->assertArrayHasKey(0, $fields);
		
		$actual = CerbQuickSearchLexer::getTokensAsQuery($fields[0]->tokens);
		$expected = '(group.id:[1] group.id:[2])';
		
		$this->assertEquals($expected, $actual);
		
		// Indented params
		
		$query = <<< EOD
			query:(
			  status:open limit:10 sort:-updated
			)
			EOD;
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		$this->assertArrayHasKey(0, $fields);
		
		$actual = CerbQuickSearchLexer::getTokensAsQuery($fields[0]->tokens);
		$expected = '(status:open limit:10 sort:-updated)';
		
		$this->assertEquals($expected, $actual);
	}
	
	function testTokensToQueryNestedGroupAnd() {
		$data_query = <<< EOD
			type:worklist.subtotals
			of:ticket
			by:[group~100]
			query:(
			  (group.id:[1] AND group.id:[2])
			)
			format:pie		
			EOD;
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($data_query);
		
		$this->assertArrayHasKey(3, $fields);
		
		$actual = CerbQuickSearchLexer::getTokensAsQuery($fields[3]->tokens);
		$expected = '((group.id:[1] group.id:[2]))';
		
		$this->assertEquals($expected, $actual);
	}
	
	function testTokensToQueryNestedGroupOr() {
		$data_query = <<< EOD
			type:worklist.subtotals
			of:ticket
			by:[group~100]
			query:(
			  ((group.id:[1]) OR (group.id:[2] status:o))
			)
			format:pie		
			EOD;
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($data_query);
		
		$this->assertArrayHasKey(3, $fields);
		
		$actual = CerbQuickSearchLexer::getTokensAsQuery($fields[3]->tokens);
		$expected = '(((group.id:[1]) OR (group.id:[2] status:o)))';
		
		$this->assertEquals($expected, $actual);
	}
}