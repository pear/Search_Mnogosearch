<?php 
/**
* Example usage for Pear class Search_Mnogosearch
*
* @author Gerrit Goetsch <goetsch@cross-solution.de>
* 
* $Id$
*/
require_once 'Search/Mnogosearch.php';

// define the DNS to mnogosearch
// define('DSN_MYSQL_MNOGOSEARCH', 'mysql://user:password@localhost/database');
include '../../config.php';

// some parameters
$params = array ();
$params['excerptsize']      = "400";
$params['excerptpadding']   = "64";
$params['detectclones']     = 1; // enabled
$params['cachemode']        = 0; // disabled
$params['crosswords']       = 1; // disabled
$params['minwordlength']    = 2;
$params['charset']          = "iso-8859-1";
$params['dateformat']       = "%d-%m-%y";
$params['hlbeg']            = '<font color="#003300"><b>';
$params['hlend']            = '</b></font>';

$search = Search_Mnogosearch::connect(DSN_MYSQL_MNOGOSEARCH."/?dbmode=multi");

// set the parameters
$search->setParameters($params);

// set the http parameters if you want.
$search->setHttpParameters(array (
    'page'    => 'page', 
    'group'   => 'group',   
    'query'   => 'words'
));
     
$search->addLogicOperators(array(
    'and' => array('und','Und','UND'),
    'or'  => array('oder','Oder','ODER'),
    'not' => array('nicht','Nicht','NICHT')
));

                           
print $search->toHtml();

?>
