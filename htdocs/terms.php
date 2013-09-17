<?php

/**
 * The page that displays all terms.
 *
 * PHP version 5
 *
 * @author		Waldo Jaquith <waldo at jaquith.org>
 * @copyright	2010-2013 Waldo Jaquith
 * @license		http://www.gnu.org/licenses/gpl.html GPL 3
 * @version		0.7
 * @link		http://www.statedecoded.com/
 * @since		0.1
*/

/*
 * Include the PHP declarations that drive this page.
 */
require '../includes/page-head.inc.php';

/*
 * Filter the query params.
 */
$filter_args = array(
	'term' => FILTER_SANITIZE_STRING,
	'query' => FILTER_SANITIZE_STRING,
	'parent_id' => FILTER_VALIDATE_INT,
	'page' => FILTER_VALIDATE_INT,
	'per_page' => FILTER_VALIDATE_INT
);

$query = filter_input_array(INPUT_GET, $filter_args);

if(!isset($query['page']))
{
	$query['page'] = 1;
}

/*
 * Let's just override the per_page to avoid abuse.
 */
$query['per_page'] = 50;


/*
 * Create a new instance of Term.
 */
$term = new Term();

$terms = $term->get_terms_nested($query);

$count_terms = $term->get_total_count($query);

$last_page = ceil($count_terms / $query['per_page']);


/*
 * Fire up our templating engine.
 */
$template = new Page;

/*
 * Make some section information available globally to JavaScript.
 */

$template->field->javascript_files = '
	<script src="/js/jquery.qtip.min.js"></script>
	<script src="/js/jquery.slideto.min.js"></script>
	<script src="/js/jquery.color-2.1.1.min.js"></script>
	<script src="/js/mousetrap.min.js"></script>
	<script src="/js/jquery.zclip.min.js"></script>
	<script src="/js/functions.js"></script>';

/*
 * Define the browser title.
 */
$template->field->browser_title ='Term Index - ' . SITE_TITLE;

/*
 * Define the page title.
 */
$template->field->page_title = 'Term Index';


/*
 * Start assembling the body of this page by indicating the beginning of the text of the section.
 */
$body .= '<form method="GET" id="term_search">
		<input type="text" name="query" value="'.$query['query'].'" />
		<input type="submit" name="submit" value="Search" />
	</form>';
$body .= '<article id="term-list">';

foreach($terms as $current_term)
{
	$body .= show_term($current_term);
}

$body .= '</article>';

if($last_page > 1)
{
	$url_args = $query;



	$body .= '<nav class="pagination"><ul>';

	if($query['page'] > 1)
	{
		$url_args['page'] = 1;
		$body .= '<li><a href="?' . http_build_query($url_args) . '">First Page</a></li>';

		$url_args['page'] = (int) $query['page'] - 1;
		$body .= '<li><a href="?' . http_build_query($url_args) . '">Previous Page</a></li>';
	}
	if($query['page'] < $last_page)
	{
		$url_args['page'] = (int) $query['page'] + 1;
		$body .= '<li><a href="?' . http_build_query($url_args) . '">Next Page</a></li>';

		$url_args['page'] = $last_page;
		$body .= '<li><a href="?' . http_build_query($url_args) . '">Last Page</a></li>';
	}
	$body .= '</ul></nav>';
}

/*
 * Establish the $sidebar variable, so that we can append to it in conditionals.
 */
$sidebar = '
<section id="share">
	<h1>Share This List</h1>
	<!-- AddThis Button BEGIN -->
	<div class="addthis_toolbox addthis_default_style ">
	<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
	<a class="addthis_button_tweet"></a>
	<a class="addthis_button_pinterest_pinit"></a>
	<a class="addthis_counter addthis_pill_style"></a>
	</div>
	<script type="text/javascript">var addthis_config = {"data_track_addressbar":false};</script>
	<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-518a87af289b1ef3"></script>
	<!-- AddThis Button END -->
</section>';


/*
 * Commenting functionality.
 */
if (defined('DISQUS_SHORTNAME') === TRUE)
{
	$body .= <<<EOD
	<section id="comments">
		<h2>Comments</h2>
		<div id="disqus_thread"></div>
		<script>
			var disqus_shortname = 'vacode'; // required: replace example with your forum shortname

			/* * * DON'T EDIT BELOW THIS LINE * * */
			(function() {
				var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
				dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
				(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
			})();
		</script>
	</section>
EOD;
}





$sidebar .= '<section id="elsewhere">
				<h1>Trust, But Verify</h1>
				<p>If you’re reading this for anything important, you should double-check its
				accuracy';
$sidebar .= ' on the official '.LAWS_NAME.' website</a>.
			</section>';

//$sidebar .= '<section id="keyboard-guide"><a id="keyhelp">'.$help->get_text('keyboard')->title.'</a></section>';

/*
 * Put the shorthand $body variable into its proper place.
 */
$template->field->body = $body;
unset($body);

/*
 * Put the shorthand $sidebar variable into its proper place.
 */
$template->field->sidebar = $sidebar;
unset($sidebar);

/*
 * Parse the template, which is a shortcut for a few steps that culminate in sending the content
 * to the browser.
 */
$template->parse();

function show_term($term)
{
	$text .= '<li>';

	if (strlen($term['term']))
	{
		// TODO: Roll this into the proper CSS file.  We need a class for "matched" here.
		$text .= '<h4 style="display: inline-block; ';
		if (isset($term['match']))
		{
			$text .= 'background-color: #ffd; padding: 3px 5px;';
		}
		$text .= '"><a href="/terms/#' . $term['id'] . '" id="' .$term['id'] . '"
			>'.$term['term'].'</a></h4> - ';
	}
	if (strlen($term['section']))
	{
		if ($term['laws_section'])
		{
			// TODO: replace this with data from the permalinks table when it becomes
			// available.
			$ancestors = array();
			foreach($term as $field=>$value)
			{
				// If we have a structure field
				if (preg_match('/^s[0-9]_identifier/', $field))
				{
					$ancestors[] = $value;
				}
			}
			$url = '/' . join('/', array_reverse($ancestors)) . '/' .
				$term['laws_section'] . '/';

			$text .= '<span class="law-lookup">
				<a href="'.$url.'">' .
				$term['laws_section'] . '</a></span>';
		}
		else
		{
			$text .= '<span class="law-lookup">' . $term['article'] . ' ' .
				$term['section'] . '</span>';
		}
	}

	if (count($term['children']))
	{
		$text .= '<ul class="child-terms">';
		foreach ($term['children'] as $child)
		{
			$text .= show_term($child);
		}
		$text .= '</ul>';
	}

	if (strlen($term['see']))
	{
		$text .= '<div class="see">See: <span class="see-terms">' . $term['see'] . '</span></div>';
	}
	if (strlen($term['see_also']))
	{
		$text .= '<div class="see-also">See Also: <span class="see-terms">' . $term['see_also'] . '</span></div>';
	}
	$text .= '</li>';

	return $text;
}
