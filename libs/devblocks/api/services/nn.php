<?php
class DevblocksNeuralNetworkNode {
	public $value = 0.0;
	public $weights = array();
	public $threshold = 0.0;
}

class DevblocksNeuralNetwork {
	const E = 2.71828;

	private $_learning_rate = 0.1;
	
	private $_inputs = array();
	private $_hiddens = array();
	private $_outputs = array();

	private $_expectedValues = array();
	
	function __construct($inputs, $hiddens, $outputs, $learning_rate) {
		$this->_learning_rate = $learning_rate;
		
		for($x = 0; $x < $inputs; $x++)
			$this->_inputs[] = new DevblocksNeuralNetworkNode();
		
		for($x = 0; $x < $hiddens; $x++)
			$this->_hiddens[] = new DevblocksNeuralNetworkNode();
		
		for($x = 0; $x < $outputs; $x++)
			$this->_outputs[] = new DevblocksNeuralNetworkNode();
	}
	
	public function connectNodes() {
		$num_inputs = count($this->_inputs);
		$num_hiddens = count($this->_hiddens);
		$num_outputs = count($this->_outputs);
		
		// Inputs
		
		foreach($this->_inputs as &$input) { /* @var $input DevblocksNeuralNetworkNode */
			$weights = array();
			
			for($x = 0; $x < $num_hiddens; $x++)
				$weights[$x] = 1.0 - ((rand() % 200) / 100);
			
			$input->weights = $weights;
		}
		
		// Hiddens
		
		foreach($this->_hiddens as &$hidden) { /* @var $hidden DevblocksNeuralNetworkNode */
			$weights = array();
			
			for($x = 0; $x < $num_outputs; $x++)
				$weights[$x] = 1.0 - ((rand() % 200) / 100);
			
			$hidden->weights = $weights;
			$hidden->threshold = rand() / rand();
		}
		
		// Outputs
		
		foreach($this->_outputs as &$output) { /* @var $output DevblocksNeuralNetworkNode */
			$output->threshold = rand() / rand();
		}
		
	}
	
	private function _sigmoid($input) {
		// Standard logistic
		return 1.0 / (1.0 + pow(self::E, -$input));
	}
	
	private function _dsigmoid($input) {
		return $input * (1.0-$input);
	}
	
	public function activateNetwork() {
		$weights = array();
		
		foreach($this->_hiddens as $h => &$hidden) {
			$weightedInput = 0.0;
			
			foreach($this->_inputs as &$input) {
				$weightedInput += $input->weights[$h] * $input->value;
			}
			
			$weightedInput += (-$hidden->threshold);
			
			$hidden->value = $this->_sigmoid($weightedInput);
		}
		
		foreach($this->_outputs as $o => &$output) {
			$weightedInput = 0.0;
			
			foreach($this->_hiddens as &$hidden) {
				$weightedInput += $hidden->weights[$o] * $hidden->value;
				//$inputWeights[$o][] = $input->weights[$o];
				//$weights[$o][] = $input->weights[$o] * $input->value;
			}
			
			$weightedInput += (-$output->threshold);
			
			$weights[] = $weightedInput;
			
			$output->value = $this->_sigmoid($weightedInput);
		}
		
		return $weights;
	}
	
	public function train($expectedOutputs) {
		$sumOfSquaredErrors = 0.0;

		foreach($this->_outputs as $o => &$output) {
			$absoluteError = $expectedOutputs[$o] - $output->value;
			$sumOfSquaredErrors += pow($absoluteError, 2);
			$outputErrorGradient = $this->_dsigmoid($output->value) * $absoluteError;

			foreach($this->_hiddens as $h => &$hidden) {
				$delta = $this->_learning_rate * $hidden->value * $outputErrorGradient;
				$hidden->weights[$o] += $delta;
				
				$hiddenErrorGradient = $this->_dsigmoid($hidden->value) * $outputErrorGradient * $hidden->weights[$o];
				
				foreach($this->_inputs as $i => &$input) {
					$delta = $this->_learning_rate * $input->value * $hiddenErrorGradient;
					$input->weights[$h] += $delta;
				}
				
				$thresholdData = $this->_learning_rate * -$hiddenErrorGradient;
				$hidden->threshold += $thresholdData;
			}
			
			$delta = $this->_learning_rate * -$outputErrorGradient;
			$output->threshold += $delta;
		}
		
		return $sumOfSquaredErrors;
	}
	
	public function setInputs($values) {
		foreach(array_values($values) as $idx => $v)
			$this->_inputs[$idx]->value = $v;
		
		return true;
	}
	
	public function getOutputs() {
		$values = array();
		
		foreach($this->_outputs as &$output)
			$values[] = $output->value;
		
		return $values;
	}
	
	/*
 	// Not secure for the web
	public function export($filename) {
		$object = array(
			'inputs' => array(
				'weights' => array(),
			),
			'hiddens' => array(
				'weights' => array(),
				'thresholds' => array(),
			),
			'outputs' => array(
				'thresholds' => array(),
			),
		);
		
		foreach($this->_inputs as $input) {
			$object['inputs']['weights'][] = $input->weights;
		}
		
		foreach($this->_hiddens as $hidden) {
			$object['hiddens']['weights'][] = $hidden->weights;
			$object['hiddens']['thresholds'][] = $hidden->threshold;
		}
		
		foreach($this->_outputs as $output) {
			$object['outputs']['thresholds'][] = $output->threshold;
		}
		
		file_put_contents($filename, json_encode($object));
	}
	
	// Not secure for the web
	public function import($filename) {
		$json = json_decode(file_get_contents($filename), true);
		
		foreach($this->_inputs as $idx => $input) {
			$input->weights = $json['inputs']['weights'][$idx];
		}
		
		foreach($this->_hiddens as $idx => $hidden) {
			$hidden->weights = $json['hiddens']['weights'][$idx];
			$hidden->threshold = $json['hiddens']['thresholds'][$idx];
		}
		
		foreach($this->_outputs as $idx => $output) {
			$output->threshold = $json['outputs']['thresholds'][$idx];
		}
	}
	*/
}

class _DevblocksNeuralNetworkService {
	private function __construct() {}
	
	static function createNeuralNetwork($inputs, $hiddens, $outputs, $learning_rate) {
		return new DevblocksNeuralNetwork($inputs, $hiddens, $outputs, $learning_rate);
	}
};


