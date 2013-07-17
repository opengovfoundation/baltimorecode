<?php
	namespace Decoder;

	class Section extends DecoderElement{
		public $content;
		public $patterns = array();
		public $parents = array();
		public $xml;
		
		public function __construct($identifier = null, $content = null, $patterns = null){
			$this->identifier = $identifier;
			$this->content = $content;
			$this->patterns = $patterns;
		}
		
		public function addParent($level, $label, $identifier, $title){
			if(!isset($level) || !isset($label) || !isset($identifier) || !isset($title)){
				throw new Exception("Unable to create parent!\nLevel: $level\nLabel: $label\nIdentifier: $identifier\nTitle: $title\n");
			}
			
			//Create associative parent array
			$parent = array(
				'level'			=> trim($level),
				'label'			=> trim($label),
				'identifier'	=> trim($identifier),
				'title'			=> trim($title)
			);
			
			//Add parent item to the parents array
			array_push($this->parents, $parent);
		}
		
		public function setContent($content){
			$this->content = $content;
		}
		
		public function setPatterns($patterns){
			$this->patterns = $patterns;
		}
		
		/**
		 * 	Create SimpleXML string from this Section object
		 */
		public function toXML(){
			//Create the simple xml object
			$law = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><law></law>');
			
			//If this section has parents, add them to the structure element
			if(isset($this->parents) && count($this->parents) > 0){
				$structureNode = $law->addChild("structure");
				//$this->debug($this->parents);
				foreach($this->parents as $parent){
					$parent['title'] = str_replace('&', '&amp;', $parent['title']);
					$unit = $structureNode->addChild("unit", $parent['title']);
					$unit->addAttribute('label', $parent['label']);
					$unit->addAttribute('identifier', $parent['identifier']);
					$unit->addAttribute('order_by', $parent['identifier']);
					$unit->addAttribute('level', $parent['level']);
				}
			}
			
			//Add section information
			$catch_line = $law->addChild('catch_line', $this->catch_line);
			$section_number = $law->addChild('section_number', $this->identifier);
			
			//Split identifier and pop last element
			$identifier_parts = explode('-', $this->identifier);
			$order_by_value = array_pop($identifier_parts);
			$order_by = $law->addChild('order_by', $order_by_value);
			
			//Add section content
			$text = $law->addChild('text', $this->content);
			
			//Set this section's xml as the simpleXML law object
			$this->xml = $law;
			
			//TODO: build children sections as well
			// if(empty($children)){
			// 				return "<section prefix=\"$identifier\">$content</section>";
			// 			}
			// 			else{
			// 				//Build children xml as well
			// 			}
		}
		
		/**
		 * 	Save this Section's xml attribute as the given filename or its identifier
		 */
		public function saveXML($filename = null){
			if($filename == null){
				$filename = $this->identifier . '.xml';
			}
			$this->xml->asXML($filename);
			exec("xmllint -format $filename -output $filename");
		}
		
		public function parseChildren(){
			
			if(!isset($patterns)){
				throw new Exception("Necessary patterns aren't set");
			}
			
			if(preg_match($pattern, $content)){
				preg_match_all($pattern, $content, $matches);
				$childrenContent = preg_split($pattern, $content, -1, PREG_SPLIT_OFFSET_CAPTURE);
			}
			
			foreach($childrenContent as $index => $childContent){
				$this->debug($childContent);
				
				
				
			}
		}
	}
