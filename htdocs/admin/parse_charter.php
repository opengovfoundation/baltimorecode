<?php
/*
 * Charter Parser
 *
 * When running this script, it will occasionally fail.
 * So far, this seems to mainly happen due to it misinterpretting
 * the beginning of a new section (Line 40 below).  This happens
 * when a line begins with &#167 but is not actually the start of
 * a new section.  The easiest solution is to remove the newline
 * immediately before the &#167, bringing it back to the end of
 * the previous line.
 */

	ini_set("error_reporting", E_ALL);
	require_once('class.Section.php');

	file_put_contents('error.log', '');

	function customLog($msg){
		 file_put_contents('error.log', "\n------------------\n" . print_r($msg, true) . "\n------------------\n", FILE_APPEND);
	}

	/*
	 * Convert from Roman numerals to standard numerals.
	 * This is incomplete at best.
	 */
	function fromRoman($roman)
	{
		$romans = array(
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1,
		);

		$result = 0;

		foreach ($romans as $key => $value) {
			while (strpos($roman, $key) === 0) {
				$result += $value;
				$roman = substr($roman, strlen($key));
			}
		}

		return $result;
	}

	/*
	 * Convert other letters to numbers.
	 * We can't use ord() because it's not utf-8 friendly.
	 */
	function fromAlpha($letter)
	{
		var_dump($letter);

		$alphabet = array_flip(array(
			'A', 'B', 'C', 'D', 'E', 'F', 'G',
			'H', 'I', 'J', 'K', 'L', 'M', 'N',
			'O', 'P', 'Q', 'R', 'S', 'T', 'U',
			'V', 'W', 'X', 'Y', 'Z'
		));

		$letter = strtoupper($letter);

		return ((int) $alphabet[$letter]) + 1;
	}

	function idToOrder($number)
	{
		$parts = explode('-', $number);

		$total = fromRoman($parts[0]);

		if(count($parts) > 1)
		{
			if(preg_match('/([A-Z])$/', $parts[1], $matches))
			{
				$total += fromAlpha($matches[1]) / 10000;

				$parts[1] = substr($parts[1], 0, -1);
			}

			$total += ((int) $parts[1]) / 1000;
		}

		return $total;
	}

	$structure = array('@\n\s*(\([a-z]+\))\s.*@', '@\n\s*(\(\d+\)).*@', '@\n\s*(\([ixv]+\)).*@', '@\n\s*(\d+)\..*@');
	$lawTopPattern = '@^\s*\(?(\d+(?:\.\d)?[A-Z]?)\.?\)?\s(.*)@';

	try{
		$src = file_get_contents('./original/01 - Charter.xml');

		$articles = preg_split("/anchor id=\"Art.*\/>/", $src);

		foreach($articles as $index => $article){
			if($index == 0){continue;}
			$article = strip_tags($article);

			$isMatch = preg_match('/^Article ([IXV]{0,10})\s*(\S.*)/m', $article, $titleStuff);

			if($isMatch){
				$art_num = $titleStuff[1];
				$art_name = $titleStuff[2];

				//file_put_contents("Article_$art_num.txt", $article);
			}else{
				throw new Exception("Could not retrieve article info");
			}

			preg_match_all("@\n&#167;(.*)\n@", $article, $lawMetas);
			$lawStrings = preg_split("@\n&#167;.*\n@", $article, -1, PREG_SPLIT_DELIM_CAPTURE);


			foreach($lawStrings as $lawIndex => $lawString){
				if($lawIndex == 0){
					continue;
				}


				$law = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><law></law>');
				$structureNode = $law->addChild("structure");
				$unit = $structureNode->addChild("unit", $titleStuff[2]);
				$unit->addAttribute("label", "article");
				$unit->addAttribute("identifier", $titleStuff[1]);
				$unit->addAttribute("order_by", idToOrder($titleStuff[1]));

				$unit->addAttribute("level", '1');

				$lawInfo = $lawMetas[1][$lawIndex -1];

				//Only occurs with repealed laws
				if(!isset($lawString) || $lawString == '' || preg_match("@(\d+)?(?:\sto)?\s(\d+)\.\s+{(.*)}@", $lawInfo)){
					$repealedInfo = preg_match_all("@(\d+)?(?:\sto)?\s(\d+)\.\s+{(.*)}@", $lawInfo, $repealedLaws);

					//There was only one law repealed, not a set
					if($repealedLaws[1][0] == ''){
						$repealedNum = $repealedLaws[2][0];
						$repealedText = $repealedLaws[3][0];
						$filename = './xml/' . $art_num . '-' . $repealedNum . '.xml';
						$catch_title = $law->addChild('catch_line', $repealedText);
						$order_by = $law->addChild('order_by', idToOrder($art_num . '-' . $repealedNum));
						$section_number = $law->addChild('section_number', $art_num . '-' . $repealedNum);
						$text = $law->addChild('text', $repealedText);
						$metadata = $law->addChild('metadata');
						$repealedNode = $metadata->addChild('repealed', 'y');
					}else{//There is a range of repealed laws
						$repealedText = $repealedLaws[3][0];

						for($i = $repealedLaws[1][0]; $i <= $repealedLaws[2][0]; $i++){
							$repealedNum = $i;
							$filename = './xml/' . $art_num . '-' . $repealedNum . '.xml';
							$catch_title = $law->addChild('catch_line', $repealedText);
							$order_by = $law->addChild('order_by', idToOrder($art_num . '-' . $repealedNum));
							$section_number = $law->addChild('section_number', $art_num . '-' . $repealedNum);
							$text = $law->addChild('text', $repealedText);
							$metadata = $law->addChild('metadata');
							$repealedNode = $metadata->addChild('repealed', 'y');

							$law->asXml($filename);
							exec("xmllint -format $filename -output $filename");
						}

						continue;
					}

				}else{//Not repealed
					$oldInfo = $lawInfo;
					preg_match_all($lawTopPattern, $lawInfo, $lawInfo);
					if(!isset($lawInfo[1][0]) || $lawInfo[1][0] == ''){
						customLog("Old Info: " . print_r($oldInfo, true));
						customLog($lawInfo);
						customLog($lawString);
						customLog("Article: $art_num\nlawIndex: $lawIndex");
						throw new Exception('Incorrect match on the filename pattern');
					}

					$filename = './xml/' . $art_num . '-' . $lawInfo[1][0] . '.xml';
					$catch_title = $law->addChild('catch_line', trim($lawInfo[2][0]));
					$order_by = $law->addChild('order_by', idToOrder($art_num . '-' . $lawInfo[1][0]));
					$section_number = $law->addChild('section_number', $art_num . '-' . $lawInfo[1][0]);
					$text = $law->addChild('text');
					$section = $text->addChild('section', $lawString);
					$metadata = $law->addChild('metadata');
					$repealedNode = $metadata->addChild('repealed', 'n');
				}

				$law->asXml($filename);
				exec("xmllint -format $filename -output $filename");
			}

		}
	}catch(Exception $e){
		echo "*** ERROR ***\n";
		echo $e->getMessage();
		echo "*************\n";
		exit;
	}
