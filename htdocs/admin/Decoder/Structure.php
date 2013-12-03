<?php
	namespace Decoder;
	
	use Decoder\Section;

	/**
	 * 	Article class for the top-most structure element
	 */
	class StructureElement extends DecoderElement{
		public $level;
		public $type;
		
		/**
		 * 	Constructor 
		 */
		public function __construct($identifier = null, $type = null, $order_by = null, $catch_line = null, $children = null, $level = null){
			parent::__construct($identifier, $order_by, $catch_line, $children);
			$this->level = $level;
		}
		
		public function setLevel($level){
			$this->level = $level;
		}
		
		//Save each child section as its own file
		public function toXML(){
			foreach($children as $child){
				$child->toXML();
			}
		}
		
	}

