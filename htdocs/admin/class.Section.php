<?php
	class Section{
		public $identifier;
		public $content;
		public $children = array();
		public $childrenPattern;
		public $currentPattern;
		public $totalPatterns = array();
		protected $debug = false;
		
		public function __construct($identifier = null, $content = null, $currentPattern = null, $childrenPattern = null, $totalPatterns = null){
			$this->identifier = $identifier;
			$this->content = $content;
			$this->currentPattern = $currentPattern;
			$this->childrenPattern = $childrenPattern;
			$this->totalPatterns = $totalPatterns;
		}
		
		public function setIdentifier($identifier){
			$this->identifier = $identifier;
		}
		
		public function setContent($content){
			$this->content = $content;
		}
		
		public function setChildrenPattern($childrenPattern){
			$this->childrenPattern = $childrenPattern;
		}
		
		public function setCurrentPattern($currentPattern){
			$this->currentPattern = $currentPattern;
		}
		
		public function setTotalPatterns($totalPatterns){
			$this->totalPatterns = $totalPatterns;
		}
		
		public function debug($debugBoolean){
			$this->debug = $debugBoolean;
		}
		
		public function toXML(){
			if(empty($children)){
				return "<section prefix=\"$identifier\">$content</section>";
			}
			else{
				//Build children xml as well
			}
		}
		
		public function parseChildren(){
			
			if(!isset($childrenPattern) || !(isset($currentPattern) && isset($totalPatterns))){
				throw new Exception("Necessary patterns aren't set");
			}
			
			if(preg_match($pattern, $content)){
				preg_match_all($pattern, $content, $matches);
				$childrenContent = preg_split($pattern, $content, -1, PREG_SPLIT_OFFSET_CAPTURE);
			}
			
			//print_debug($matches);
			print_debug($childrenContent);
			
			foreach($childrenContent as $index => $childContent){
				//$child = new Section();
				
				
				
			}
		}
		
		protected function print_debug($toDebug){
			if($this->debug){
				print_r($toDebug);
			}
		}
		
	}
