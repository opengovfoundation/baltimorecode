<?php

/**
 * The "About" page, explaining this State Decoded website.
 *
 * PHP version 5
 *
 * @license		http://www.gnu.org/licenses/gpl.html GPL 3
 * @version		0.8
 * @link		http://www.statedecoded.com/
 * @since		0.1
 *
 */

/*
 * Create a container for our content.
 */
$content = new Content();

/*
 * Define some page elements.
 */
$content->set('browser_title', 'About Baltimore Decoded');
$content->set('page_title', 'About');

$body = '
<h2>Introduction</h2>
<p>Baltimore Decoded is a non-profit, non-governmental, non-partisan implementation of <a href="http://www.statedecoded.com/" target="_blank">The State Decoded</a> brought to you by the folks at the <a href="http://opengovfoundation.org/" target="_blank">OpenGov Foundation</a>. It’s a collaborative software project that costs Baltimore citizens $0 tax dollars, and that provides a platform to display city-level legal information in a friendly, accessible, modern fashion. Baltimore City is the first municipality to deploy the software, with more coming soon.</p>
<iframe width="560" height="315" style="margin:50px auto 0 auto; width:560px; display:block;" src="//www.youtube.com/embed/Nl6xncNzavc" frameborder="0" allowfullscreen></iframe>
<h2>Beta Testing</h2>
<p>Baltimore Decoded is currently in public beta, which is to say that the site is under active development, with known shortcomings, but it has reached a point where it would benefit from being used by the general public (who one hopes will likewise benefit from it).  While every effort is made to ensure that the data provided on Baltimore Decoded is accurate and up-to-date, it would be gravely unwise to rely on it for any matter of importance while it is in this beta testing phase.</p>
<p>Many more features are under development, including calculations of the importance of given laws, inclusion of attorney generals’ opinions, court rulings, extensive explanatory text, social media integration, significant navigation enhancements, a vastly expanded built-in glossary of legal terms, scholarly article citations, and much more.</p>
<h2>Data Sources</h2>
<p>Data Sources The information that makes up Baltimore Decoded comes entirely from public sources. All of the sections of the code are straight from the <a href="http://www.baltimorecity.gov/Government/AgenciesDepartments/LegislativeReference.aspx" target="_blank">Baltimore City Department of Legislative Reference</a>, who provided XML of the Code. Any further included legislative data is scraped from the Baltimore City Council website. Term definitions, where included, are from within the Baltimore City code itself. Throughout the site, links are provided to original data sources, whenever possible.  <a href="http://www.baltimorecity.gov/Government/AgenciesDepartments/LegislativeReference.aspx" target="_blank">Click here to visit the Baltimore City Department of Legislative Reference</a>, and click here to visit its <a href="http://www.baltimorecity.gov/Government/CityCharterCodes.aspx" target="_blank">production of the Baltimore City laws.</a></p>
<h2>API</h2>
<p>The site has a RESTful, JSON-based API. <a href="/api-key/">Register for an API key</a> and <a href="https://github.com/statedecoded/statedecoded/wiki/API-Documentation" target="_blank">read the documentation</a> for details.</p>
<h2>Thanks</h2>
<p>Baltimore Decoded wouldn’t be possible without the contributions and years of work by <a href="http://waldo.jaquith.org/" target="_blank">Waldo Jaquith</a>, and the many dozens of people who participated in private alpha and beta testing of <a href="http://vacode.org/about/" target="_blank">Virginia Decoded</a>, the first <a href="http://www.statedecoded.com/" target="_blank">State Decoded</a> site, over the course of a year and a half, beginning in 2010. Specific thanks must be extended the good people of the Baltimore City Department of Legislative Reference.  This platform on which this site is based, <a href="http://www.statedecoded.com/" target="_blank">The State Decoded</a>, was expanded to function beyond Virginia thanks to a generous grant by the <a href="http://knightfoundation.org/" target="_blank">John S. and James L. Knight Foundation.</a></p>
<h2>Colophon</h2>
<p>Hosted on <a href="http://www.centos.org/">CentOS</a>, driven by <a href="http://httpd.apache.org/">Apache</a>, <a href="http://www.mysql.com/">MySQL</a>, and <a href="http://www.php.net/">PHP</a>. Hosting by Rackspace. Search by <a href="http://lucene.apache.org/solr/">Solr</a>. Comments by <a href="http://disqus.com/">Disqus</a></p>
<h2>Disclaimer</h2>
<p>This is not an official copy of the Code of Baltimore. It is in no way authorized by the City of Baltimore. No information that is found on Baltimore Decoded constitutes legal advice on any subject matter. Do not take action (or fail to take action) on a legal matter without consulting proper legal counsel. The contents of this website are provided as-is, with no warranty of any kind, including merchantability, non-infringement, or fitness for a particular purpose. This website is not your lawyer, and neither is the OpenGov Foundation.</p>
';


$sidebar =
'
<section>
<h1>Contact</h1>
<ul>
<li>
<a href="http://twitter.com/foundopengov" target="_blank">Follow OpenGov on Twitter</a>
</li>
<li>
<a href="mailto:sayhello@opengovfoundation.org">Email OpenGov\'s Seamus Kraft &amp; Chris Birk</a>
</li>
</ul>
</section>
<section>
<h1>Tell your Friends</h1>
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
</section>
';

/*
 * Put the shorthand $body variable into its proper place.
 */
$content->set('body', $body);
unset($body);

/*
 * Put the shorthand $sidebar variable into its proper place.
 */
$content->set('sidebar', $sidebar);
unset($sidebar);

/*
 * Add the custom classes to the body.
 */
$content->set('body_class', 'law inside');


/*
 * Fire up our templating engine.
 */
$template = Template::create();

/*
 * Parse the template, which is a shortcut for a few steps that culminate in sending the content
 * to the browser.
 */
$template->parse($content);
