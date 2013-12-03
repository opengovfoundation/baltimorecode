<?php
	namespace Decoder;
	
	use Decoder\Article;

	class Decoder{
		public $filenames;
		public $errorLog;
		
		public function setLog($filename){
			$this->errorLog = $filename;
		}
		
		public function customLog($logItem){
			
		}
		
	}