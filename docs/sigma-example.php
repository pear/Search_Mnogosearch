<?php 
/**
* Example usage for Search_Mnogosearch renderer
* with Sigma Templates and Pager.
*
* @author Gerrit Goetsch <goetsch@cross-solution.de>
* 
* $Id$
*/
require_once 'Search/Mnogosearch.php';
require_once 'Search/Mnogosearch/Renderer/Sigma.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/ITStatic.php';
require_once 'HTML/Template/Sigma.php';
require_once 'Pager/Pager.php';

// define the DNS to mnogosearch
// define('DSN_MNOGOSEARCH', 'mysql://user:password@localhost/database');
include 'config.php';

$tpl = & new HTML_Template_Sigma(dirname(__FILE__).'/renderers/templates/');
$tpl->loadTemplateFile('search.html', false, true);
$tpl->setVariable("subject", "Search_Mnogosearch renderer example for the sigma renderer");

$perPage = 10;
// some parameters
$params = array ();
$params['excerptsize']      = "400";
$params['excerptpadding']   = "64";
$params['pagesize']         = $perPage;
$params['mode']             = 'UDM_MODE_ALL';
$params['sortorder']        = 'DRP';
$params['detectclones']     = 1; 
$params['cachemode']        = 0; 
$params['crosswords']       = 0; 
$params['minwordlength']    = 2;
$params['charset']          = "iso-8859-1";
$params['dateformat']       = "%d-%m-%y";

// Limits
$limits = array ();
// create a new Search_Mnogosearch Object    
$search = Search_Mnogosearch::connect(DSN_MNOGOSEARCH."/?dbmode=multi");
// set the http parameters if you want.
$search->setHttpParameters(array (
    'page'    => 'page', 
    'group'   => 'group',   
    'query'   => 'words'
));

// set the parameters
$search->setParameters($params);
$search->setLimits($limits);

$sigma = new Search_Mnogosearch_Renderer_Sigma($tpl);
$sigma->setVariableNames(array (
    'contentmain'   => 'contentmain',
    'query'         => 'query',
    'date'          => 'date'
));

// set the template to use
$sigma->setTemplates(array (
    'groupbysite'   => 'bygroup.html', 
    'single'        => 'result.html', 
    'noresult'      => 'noresult.html'
));

// set the pager options
$sigma->setPagerOptions(array (
    'firstPageText' => '<< ', 
    'lastPageText'  => ' >>', 
    'nextImg'       => ' >', 
    'prevImg'       => '< '
));

// set section weight factors
$search->setSectionWeights(array(
    1 => '1',   // body
    2 => '2',   // title
    3 => '2',   // keywords
    5 => '4'    // Organization (custom) 
));

$sigma->setHighlightTags(array(
    'begin'  => '<font color="#003300"><b>',
    'end'    => '</b></font>'));
    
$search->accept($sigma);

$search->disconnect();

print $tpl->get();

?>
