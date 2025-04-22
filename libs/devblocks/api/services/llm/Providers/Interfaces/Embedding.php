<?php
namespace Cerb\LLM\Providers\Interfaces;

interface Embedding {
	function embed(array $texts) : array;
}
