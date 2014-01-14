<?php
	namespace Decoder;

	class DecoderElement{
		public $identifier;
		public $order_by;
		public $catch_line;
		public $raw_content;
		public $children = array();
		public $prefixes;
		protected $debug = false;

		public function __construct($identifier = null, $order_by = null, $catch_line = null, $raw_content = null, $children = null){
			$this->identifier = $identifier;
			$this->order_by = $order_by;
			$this->catch_line = $catch_line;
			$this->raw_content = $raw_content;
			$this->children = $children;
		}

		public function setDebug($debugBoolean){
			$this->debug = $debugBoolean;
		}

		public function debug($msg){
			if($this->debug === true){
				echo "\n------------------------------------\n";
				print_r($msg);
				echo "\n------------------------------------\n";
			}
		}

		public function setIdentifier($identifier){
			$this->identifier = trim($identifier);
		}

		public function setCatchLine($catch_line){
			$this->catch_line = trim($catch_line);
		}

		public function setOrderBy($order_by){
			$this->order_by = trim($order_by);
		}
	}


