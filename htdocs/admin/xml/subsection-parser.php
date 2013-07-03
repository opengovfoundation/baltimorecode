<?php

/*
 * Define all five possible section prefixes via via PCRE strings.
 */
$prefix_candidates = array	('/[A-Z]{1,2}\. /',
							'/[0-9]{1,2}\. /',
							'/[a-z]{1,2}\. /',
							'/\([0-9]{1,2}\) /',
							'/\([a-z]{1,2}\) /');

/*
 * Establish a blank prefix structure. We'll build this up and continually modify it to keep track
 * of our current complete section number as we iterate through the code.
 */
$prefixes = array();

/*
 * Establish a blank variable to accrue the full text.
 */
$code->text = '';

/*
 * Deal with each subsection, one at a time.
 */
for ($i=1; $i<count($xml->section); $i++)
{
	
	/*
	 * Detect the presence of a subsection prefix -- that is, a letter, number, or series of letters
	 * that defines an individual subsection of a law in a hierarchical fashion. The subsection
	 * letter can be in one of five formats, listed here from most to least important:
	 * 
	 * 		A. -> 1. -> a. -> (1) -> (a)
	 *
	 * ...or, rather, this is *often* the order in which they appear. But not always! So we can't
	 * rely on this order.
	 *
	 * When the capital letters run out, they increment like hex: "AB." "AC." etc.
	 *
	 * When the lowercase letters run out, they double: "aa." "bb." etc.
	 *
	 * Set aside the first five characters in this section of text. That's the maximum number
	 * of characters that a prefix can occupy.
	 */
	$section_fragment = substr($xml->section->$i, 0, 5);
	
	/*
	 * Iterate through our PCRE candidates until we find one that matches (if, indeed, one does at
	 * all).
	 */
	foreach ($prefix_candidates as $prefix)
	{
			
		/*
	 	 * If this prefix isn't found in this section fragment, then proceed to the next prefix.
		 */
		preg_match($prefix, $section_fragment, $matches);
		if (count($matches) == 0)
		{
			continue;
		}
		
		/*
	 	 * Great, we've successfully made a match -- we now know that this is the beginning of a new
		 * numbered section. First, let's save a platonic ideal of this match.
		 */
		$match = trim($matches[0]);
		
		/*
	 	 * Now we need to figure out what the entire section number is, only the very end of which
		 * is our actual prefix. To start with, we need to modify our subsection structure array
		 * to include our current prefix.
		 * 
		 * If this is our first time through, then this is easy -- our entire structure consists
		 * of the current prefix.
		 */
		if (count($prefixes) == 0)
		{
			$prefixes[] = $match;
		}
		
		/*
	 	 * But if we already have a prefix stored in our array of prefixes for this section, then
		 * we need to iterate through and see if there's a match.
		 */
		else
		{
			
			/*
	 		 * We must figure out where in the structure our current prefix lives. Iterate through
			 * the prefix structure and look for anything that matches the regex that matched our
			 * prefix.
			 */
			foreach ($prefixes as $key => &$prefix_component)
			{
				/*
	 			 * We include a space after $prefix_component because this regex is looking for a
				 * space after the prefix, something that would be there when finding this match in
				 * the context of a section, but of course we've already trimmed that out of
				 * $prefix_component.
			 	 */
				preg_match($prefix, $prefix_component.' ', $matches);
				if (count($matches) == 0)
				{
					continue;
				}
				
				/*
	 		 	 * We've found a match! Update our array to reflect the current section
				 * number, by modifying the relevant prefix component.
				 */	
				$prefix_component = $match;
				
				/*
	 		 	* Also, set a flag so that we know that we made a match.
				 */
				$match_made = true;
				
				/*
	 			 * If there are more elements in the array after this one, we need to zero them out.
				 * That is, if we're in A4(c), and our last section was A4(b)6, then we need to lop
				 * off that "6." So kill everything in the array after this.
				 */
				if (count($prefixes) > $key)
				{
					$prefixes = array_slice($prefixes, 0, ($key+1));
				}
			}
			
			/*
	 		 * If the $match_made flag hasn't been set, then we know that this is a new prefix
			 * component, and we can append it to the prefix array.
			 */
			if (!isset($match_made))
			{
				$prefixes[] = $match;
			}
			else
			{
				unset($match_made);
			}
		}		
		
		/*
	  	 * Iterate through the prefix structure and store each prefix section in our code object.
		 * While we're at it, eliminate any periods.
		 */
		for ($j=0; $j<count($prefixes); $j++)
		{
			$code->section->$i->prefix_hierarchy->$j = str_replace('.', '', $prefixes[$j]);
		}
		
		/*
	 	 * And store the prefix list as a single string.
	 	 */
		$code->section->$i->prefix = implode('', $prefixes);

	}
	
	/*
	 * Hack off the prefix at the beginning of the text and save what remains to $code.
	 */
	if (isset($code->section->$i->prefix))
	{
		$tmp2 = explode(' ', $xml->section->$i);
		unset($tmp2[0]);
		$code->section->$i->text = implode(' ', $tmp2);
	}
	
	/*
	 * If no prefix was identified for this section, then it's a continuation of the prior section
	 * (in reality, they're probably just paragraphs, not actually "sections"). Reuse the same
	 * section identifier and append the text as-is.
	 */
	if (!isset($code->section->$i->prefix) || empty($code->section->$i->prefix))
	{
		$code->section->$i->text = $xml->section->$i;
		$code->section->$i->prefix = $code->section->{$i-1}->prefix;
		$code->section->$i->prefix_hierarchy = $code->section->{$i-1}->prefix_hierarchy;
	}
	
	/*
	 * We want to eliminate our matched prefix now, so that we don't mistakenly believe that we've
	 * successfully made a match on our next loop through.
	 */
	unset($match);
}

?>