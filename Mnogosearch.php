<?php  
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Bertrand Mansion <bmansion@mamasam.com>                      |
// |         Gerrit Goetsch <goetsch@cross-solution.de>                   |
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * This File contains the class Search_Mnogosearch.
 * @package  Search_Mnogosearch
 * @author   Gerrit Goetsch <goetsch@cross-solution.de>
 */
 
/**
 * Requires PEAR
 */
require_once 'PEAR.php';

/**
 * Mnogosearch parameters mapping
 * @global array $GLOBALS['_SEARCH_MNOGOSEARCH_PARAMETERS'] 
 * @name $_SEARCH_MNOGOSEARCH_PARAMETERS
 */
$GLOBALS['_SEARCH_MNOGOSEARCH_PARAMETERS'] = array(
    'pagesize'        => 20, 
    'pagenumber'      => 0,
    'queryParameter'  => 'q', 
    'mode'            => array (
        'any'     => 'UDM_MODE_ANY', 
        'bool'    => 'UDM_MODE_BOOL', 
        'phrase'  => 'UDM_MODE_PHRASE', 
        'all'     => 'UDM_MODE_ALL'
    ), 
    'wordmatch'       => array (
        'wrd'     => 'UDM_MATCH_WORD', 
        'beg'     => 'UDM_MATCH_BEGIN', 
        'end'     => 'UDM_MATCH_END', 
        'sub'     => 'UDM_MATCH_SUBSTR'
    ), 
    'phrasemode'      => array (
        'yes'     => 'UDM_ENABLED', 
        'no'      => 'UDM_DISABLED'
    ), 
    'groupbysite'     => array (
        'yes'     => 'UDM_ENABLED', 
        'no'      => 'UDM_DISABLED'
    )        
);

/**
 * mnogosearch search limits mapping
 * @global array $GLOBALS['_SEARCH_MNOGOSEARCH_LIMITS'] 
 * @name $_SEARCH_MNOGOSEARCH_LIMITS
 */
$GLOBALS['_SEARCH_MNOGOSEARCH_LIMITS'] = array (
    'url'      => 'setLimitUrl', 
    'tag'      => 'setLimit', 
    'category' => 'setLimit', 
    'language' => 'setLimit', 
    'type'     => 'setLimit', 
    'date'     => 'setLimitDate'
);

/** error code */
define('SEARCH_MNOGOSEARCH_ERROR', -1);
/** error code for db error */
define('SEARCH_MNOGOSEARCH_ERROR_DB', -2);
/** error code for no query */
define('SEARCH_MNOGOSEARCH_ERROR_NOQUERY', -3);
/** error code for not found */
define('SEARCH_MNOGOSEARCH_ERROR_NOTFOUND', -4);

/** result info for the number of rows */
define('SEARCH_MNOGOSEARCH_INFO_NUMROW', 'numrow');
/** result info for the word info */
define('SEARCH_MNOGOSEARCH_INFO_WORDINFO', 'wordinfo');
/** result info for the search time */
define('SEARCH_MNOGOSEARCH_INFO_SEARCHTIME', 'searchtime');
/** result info for first doc */
define('SEARCH_MNOGOSEARCH_INFO_FIRST_DOC', 'first_doc');
/** result info for last doc*/
define('SEARCH_MNOGOSEARCH_INFO_LAST_DOC', 'last_doc');
/** result info for found */
define('SEARCH_MNOGOSEARCH_FOUND', 'found');

/** missing udm parameter */
define('UDM_PARAM_DATE_FORMAT', 'DateFormat');

/**
 * MnogoSearch Wrapper.
 *
 * This class has been tested with PHP 4.3 and MnogoSearch 3.2.18+
 * with MnogoSearch PHP Extension 1.91.
 *
 * It is intended to be used in applications
 * where data needs to be separated from template and display.
 *
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @author   Gerrit Goetsch <goetsch@cross-solution.de>
 * @author   Carsten Bleek <bleek@cross-solution.de> 
 * @credits  Sergey Kartashoff <gluke@sky.net.ua>
 * @license  http://www.php.net/license/2_02.txt PHP License 2.02
 * @package  Search_Mnogosearch
 * @category Search
 * @version  $Revision$
 */
class Search_Mnogosearch {

    /**
    * DSN style DB address
    */
    var $DBAddr = '';

    /**
    * DB Mode (single, multi, crc, crc-multi...)
    */
    var $DBMode = 'single';

    /**
    * Add wildcards automatically to search limits by url
    * @access private
    */
    var $_autoWild = true;

    /**
    * Should the query be parsed automatically
    * @access private
    */
    var $_autoParse = true;
    /**
    * Number of rows to display per page
    * @access private
    */
    var $_resultsPerPage = 20;
    /**
    * Current page number
    * @access private
    */
    var $_pageNumber = 0;

    /**
    * Array of words from the query
    */
    var $words = array ();

    /**
    * Beginning HTML tag for word highlight
    * @access private
    */
    var $_hlbeg = '<strong>';

    /**
    * Ending HTML tag for word highlight
    * @access private
    */
    var $_hlend = '</strong>';

    /**
    * Additional fields
    * @access private
    */
    var $_fields = array();
    
    /**
    * Is the search mode set already ?
    */
    var $searchModeFlag = false;

    /**
    * Query words to search from $queryParameterName form variable
    */
    var $query = '';

    /**
    * Mnogosearch Agent object
    */
    var $agent;

    /** 
    * mapping for the UDM parameters 
    * @access private
    */
    var $_udm_parameter_mapping = array (
        'pagenumber'       => UDM_PARAM_PAGE_NUM, 
        'synonym'          => UDM_PARAM_SYNONYM, 
        'detectclones'     => UDM_PARAM_DETECT_CLONES, 
        'cachemode'        => UDM_PARAM_CACHE_MODE, 
        'crosswords'       => UDM_PARAM_CROSS_WORDS, 
        'minwordlength'    => UDM_PARAM_MIN_WORD_LEN, 
        'charset'          => UDM_PARAM_CHARSET, 
        'dateformat'       => UDM_PARAM_DATE_FORMAT, 
        'groupbysite'      => UDM_PARAM_GROUPBYSITE, 
        'excerptsize'      => UDM_PARAM_EXCERPT_SIZE, 
        'excerptpadding'   => UDM_PARAM_EXCERPT_PADDING, 
        'pagesize'         => UDM_PARAM_PAGE_SIZE, 
        'mode'             => UDM_PARAM_SEARCH_MODE, 
        'sortorder'        => UDM_PARAM_SORT_ORDER, 
        'trackmode'        => UDM_PARAM_TRACK_MODE, 
        'phrasemode'       => UDM_PARAM_PHRASE_MODE, 
        'localcharset'     => UDM_PARAM_LOCAL_CHARSET, 
        'remotecharset'    => UDM_PARAM_BROWSER_CHARSET, 
        'stoptable'        => UDM_PARAM_STOP_TABLE, 
        'stopfile'         => UDM_PARAM_STOP_FILE, 
        'weightfactor'     => UDM_PARAM_WEIGHT_FACTOR, 
        'wordmatch'        => UDM_PARAM_WORD_MATCH, 
        'maxwordlength'    => UDM_PARAM_MAX_WORD_LEN, 
        'ispellprifix'     => UDM_PARAM_ISPELL_PREFIX, 
        'vardir'           => UDM_PARAM_VARDIR, 
        'datadir'          => UDM_PARAM_DATADIR, 
        'hlbeg'            => UDM_PARAM_HLBEG, 
        'hlend'            => UDM_PARAM_HLEND, 
        'stored'           => UDM_PARAM_STORED, 
        'querystring'      => UDM_PARAM_QSTRING, 
        'remoteaddress'    => UDM_PARAM_REMOTE_ADDR, 
        'query'            => UDM_PARAM_QUERY, 
        'siteid'           => UDM_PARAM_SITEID, 
        'resultlimits'     => UDM_PARAM_RESULTS_LIMIT, 
        'found'            => UDM_PARAM_FOUND, 
        'numberrows'       => UDM_PARAM_NUM_ROWS, 
        'wordinfo'         => UDM_PARAM_WORDINFO, 
        'wordinfoall'      => UDM_PARAM_WORDINFO_ALL, 
        'searchtime'       => UDM_PARAM_SEARCH_TIME, 
        'firstdoc'         => UDM_PARAM_FIRST_DOC, 
        'lastdoc'          => UDM_PARAM_LAST_DOC
    );

    /** 
    * mapping for the UDM limit parameters 
    * @access private
    */
    var $_udm_limit_mapping = array (
        'category' => UDM_LIMIT_CAT, 
        'tag'      => UDM_LIMIT_TAG, 
        'url'      => UDM_LIMIT_URL, 
        'date'     => UDM_LIMIT_DATE, 
        'language' => UDM_LIMIT_LANG, 
        'type'     => UDM_LIMIT_TYPE
    );
    
    /** 
    * The http parameters 
    * @access private
    */
    var $_http_parameters = array (
        'query'    => 'query', 
        'page'     => 'page', 
        'group'    => 'group', 
        'url'      => 'url', 
        'language' => 'language',
        'siteid'   => 'siteid',
        'tag'      => 'tag',
        'pagesize' => 'pagesize',
        'mode'     => 'mode',
        'wordmatch'=> 'wordmatch',
        'type'     => 'type',
        'sortorder'=> 'sortorder',
        'weightfactor' => 'weightfactor'
    );
    
    /**
    * Replacments of logical operators
    * @access private
    */                                   
    var $_query_replacements = array(
        'and' => array('and','And','AND','&&'),
        'or'  => array('or','Or','OR','||'),
        'not' => array('not','Not','NOT','~~')
    );
    
    /**
     * Constructor
     *
     * @return void
     * @access public
     */
    function Search_Mnogosearch() 
    {
        
    } // end constructor

    /**
    * Creates a new instance of this class.
    *
    * @param  string                DSN to your database
    * @param  string               (optional) Fill this one if you 
    *    want to keep the old API style
    * @param  array                 Presets for mnogosearch agent
    * @return Search_Mnogosearch    new object of this class.
    * @access public
    */
    function connect($DBAddr, $DBMode = '', $params = array ()) 
    {
        @ $obj = & new Search_Mnogosearch();
        if (preg_match(
                '/(.*)\?dbmode=(single|multi|blob|crc|crc-multi|cache)$/', 
                $DBAddr, $match)) {
            $obj->DBAddr = $match[1];
            $obj->DBMode = $match[2];
        }
        elseif ($DBAddr != '' && $DBMode != '') {
            $obj->DBAddr = $DBAddr;
            $obj->DBMode = $DBMode;
        }
        else {
            return PEAR::raiseError(
                'Missing parameters DBAddr or DBMode to start search.', 
                SEARCH_MNOGOSEARCH_ERROR, PEAR_ERROR_RETURN);
        }

        $obj->agent = udm_alloc_agent($obj->DBAddr.'?dbmode='.$obj->DBMode);

        // Set agent parameters
        foreach ($params as $key => $value) {
            $obj->setParameter($key, $value);
        }
        return $obj;
    } // end func connect

    /**
    * Sets all parameters in the given array.
    * @param array             the parameters to set.
    * @return void
    * @access public
    */
    function setParameters($params = array ()) 
    {
        foreach ($params as $key => $value) {
            $this->setParameter($key, $value);
        }
    } // end func setParameters

    /**
    * Sets all limit parameters in the given array.
    * @param array limits the limit parameters to set.
    * @return void
    * @access public
    */
    function setLimits($limits = array ()) 
    {
        foreach ($limits as $key => $value) {
            $this->setLimit($key, $value);
        }
    } // end func setLimits

    /**
    * Maps the given parameter to the right 
    * UDM parameter.
    * @param string       the parameter
    * @return the UDM parameter
    * @access private
    */
    function _getUdmParameter($param) 
    {
        foreach ($this->_udm_parameter_mapping as $key => $value) {
            if ($key == $param) {
                return $value;
            }
        }
        return $param;
    } // end func getUdmParameter

    /**
    * Maps the given limit parameter to the right 
    * UDM limit parameter.
    * @param string param the limit parameter
    * @return the UDM limit parameter
    * @access private
    */
    function _getUdmLimit($param) 
    {
        foreach ($this->_udm_limit_mapping as $key => $value) {
            if ($key == $param) {
                return $value;
            }
        }
        return $param;
    } // end func getUdmLimit

    /**
     * Set autocompletion of url on/off
     *
     * @param  bool   Set this to true if you want 
     * autocompletion in url limit search mode
     * @return void
     * @access public
     */
    function setAutowild($bool = true) 
    {
        $this->_autoWild = $bool;
    } // end func setAutowild

    /**
     * Change a single http parameter name
     * 
     * @param string    name   Parameter name
     * @param string    value  The http parameter name
     * @access public
     * @return void
     */
    function setHttpParameter($name, $value) 
    {
        $this->_http_parameters[$name] = $value;
    } // end func setHttpParameter

    /**
     * Set additional result fields.
     * Additional fields can be defined by the "Section" Parameter
     * in the indexer.conf of mnoGoSearch. 
     * 
     * @param array     The names of the additional result fields.
     * @access public
     * @return void
     */
    function setResultFields($fields = array ()) 
    {
        if (is_array($fields)) {
            $this->_fields = $fields;
        }
    } // end func setResultFields
    
    /**
     * Set the weight factors for Additional fields,  
     * which can be defined by the "Section" Parameter
     * in the indexer.conf of mnoGoSearch. 
     * 
     * e.g.: 
     * $search->setSectionWeights(array(
     *     1 => '1',   // body
     *     2 => '2',   // title
     *     3 => '2',   // keywords
     *     5 => 'A'    // Organization (custom) 
     * ));
     *
     * The factor value is a string of hex digits [0-F].
     * 
     * @param array     The weight factors for the 
     *                  additional result fields.
     * @access public
     * @return void
     */
    function setSectionWeights($weights = array()) {
        if (is_array($weights)) {
            $wf = array();
            foreach ($weights as $section => $weight) {
                $wf[(int) $section] = $weight;
            }
            $str = '';
            for ($index = 255; $index > 0; $index--) {
                if (isset($wf[$index])) {
                    $str .= $wf[$index];
                } else {
                    $str .= '0';
                }
            }
            $this->setParameter(UDM_PARAM_WEIGHT_FACTOR,$str);
        }
    } // end func setSectionWeights
    
    /**
     * Change the http parameter names
     * 
     * @param array     The names of the http parameters.
     * @access public
     * @return void
     */
    function setHttpParameters($params = array ()) 
    {
        foreach ($params as $name => $value) {
            $this->setHttpParameter($name, $value);
        }
    } // end func setHttpParameters
    
    /**
     * Set an option for this object
     *
     * @param  string   name of the directive to set
     * @param  mixed    value of the directive to set
     * @return bool     returns true if parameter was set
     * @access public
     */
    function setParameter($param, $value) 
    {
        $name = $this->_getUdmParameter($param);
        switch ($name) {
            case UDM_PARAM_PAGE_SIZE :
                $this->_resultsPerPage = (int) $value;
                break;
            case UDM_PARAM_PAGE_NUM :
                $this->_pageNumber = (int) $value;
                break;
            case UDM_PARAM_HLBEG :
                $this->_hlbeg = $value;
                break;
            case UDM_PARAM_HLEND :
                $this->_hlend = $value;
                break;
            case UDM_PARAM_SEARCH_MODE :
                $this->searchModeFlag = true;
                break;
            case UDM_PARAM_DATE_FORMAT :
                return $this->_setParameter_ex($name, $value);
                break;
        }
        $constant = $name;
        if (defined($value)) {
            $value = constant($value);
        }
        $error = udm_set_agent_param($this->agent, $constant, $value);
        if (!$error) {
            return PEAR::raiseError(
                "Error while setting '$constant' parameter.", 
                SEARCH_MNOGOSEARCH_ERROR, PEAR_ERROR_RETURN);
        }
        return true;
    } // end func setParameter

    /**
     * Set an extended option for this object
     *
     * @param  string   Name of the directive to set
     * @param  mixed    Value of the directive to set
     * @return returns true if parameter was set
     * @access private
     */
    function _setParameter_ex($name, $value) 
    {
        $constant = $name;
        if (defined($value)) {
            $value = constant($value);
        }
        $error = udm_set_agent_param_ex($this->agent, $constant, $value);
        if (!$error) {
            return PEAR::raiseError(
                "Error while setting '$constant' parameter.", 
                SEARCH_MNOGOSEARCH_ERROR, PEAR_ERROR_RETURN);
        }
        return true;
    } // end func setParameter_ex

    /**
     * Get query result
     *
     * @param  string                             A query string.
     * @return Search_Mnogosearch_Result|false    The result object.
     * @access public
     */
    function query($query = '') 
    {
        if ($query == '') {
            // if no query, try to get 'q' variable from request
            if (isset ($_POST[$this->_http_parameters['query']])) {
                $query = $_POST[$this->_http_parameters['query']];
            }
            elseif (isset ($_GET[$this->_http_parameters['query']])) {
                $query = $_GET[$this->_http_parameters['query']];
            }
            else {
                PEAR::raiseError(
                    "Query is empty.", 
                    SEARCH_MNOGOSEARCH_ERROR_NOQUERY, PEAR_ERROR_RETURN);
                return false;
            }
        }

        $this->query = $this->_parseQuery($query);
        udm_set_agent_param($this->agent, UDM_PARAM_QUERY, $this->query);
        $queryString = '';
        if ($queryString == '') {
            if (isset ($_SERVER['QUERY_STRING'])) {
                $queryString = $_SERVER['QUERY_STRING'];
            }
            elseif (isset ($_SERVER['argv'][0])) {
                $queryString = $_SERVER['argv'][0];
            }
            udm_set_agent_param($this->agent, UDM_PARAM_QSTRING, $queryString);
        }

        if (!$this->searchModeFlag) {
            // Default search mode is ANY
            udm_set_agent_param(
                $this->agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_ANY);
        }

        $res = udm_find($this->agent, $query);
        if (($error = udm_error($this->agent)) != '') {
            udm_free_res($res);
            return PEAR::raiseError(
                $error, SEARCH_MNOGOSEARCH_ERROR_DB, PEAR_ERROR_RETURN);
        }
        $found = udm_get_res_param($res, UDM_PARAM_FOUND);
        $result = array ();
        if (!$found) {
            udm_free_res($res);
            return false;
        }
        else {
            $result[SEARCH_MNOGOSEARCH_FOUND] = $found;
        }

        // Global info

        $result[SEARCH_MNOGOSEARCH_INFO_NUMROW] = 
            udm_get_res_param($res, UDM_PARAM_NUM_ROWS);
        $result[SEARCH_MNOGOSEARCH_INFO_WORDINFO] = 
            udm_get_res_param($res, UDM_PARAM_WORDINFO_ALL);
        $result[SEARCH_MNOGOSEARCH_INFO_SEARCHTIME] = 
            udm_get_res_param($res, UDM_PARAM_SEARCHTIME);
        $result[SEARCH_MNOGOSEARCH_INFO_FIRST_DOC] = 
            udm_get_res_param($res, UDM_PARAM_FIRST_DOC);
        $result[SEARCH_MNOGOSEARCH_INFO_LAST_DOC] = 
            udm_get_res_param($res, UDM_PARAM_LAST_DOC);
        $result['query'] = $query;
        $result['queryString'] = $queryString;

        // Row specific info

        for ($i = 0; $i < $result[SEARCH_MNOGOSEARCH_INFO_NUMROW]; $i ++) {
            $row = array ();
            $row['rec_id']  = udm_get_res_field($res, $i, UDM_FIELD_URLID);
            $row['ndoc']    = udm_get_res_field($res, $i, UDM_FIELD_ORDER);
            $row['rating']  = udm_get_res_field($res, $i, UDM_FIELD_RATING);
            $row['url']     = udm_get_res_field($res, $i, UDM_FIELD_URL);
            $row['contype'] = udm_get_res_field($res, $i, UDM_FIELD_CONTENT);
            $row['docsize'] = udm_get_res_field($res, $i, UDM_FIELD_SIZE);
            $row['score']   = udm_get_res_field($res, $i, UDM_FIELD_SCORE);
            $row['lastmod'] = udm_get_res_field($res, $i, UDM_FIELD_MODIFIED);
            $row['title']   = 
                ($title = udm_get_res_field($res, $i, UDM_FIELD_TITLE)) ? 
                    htmlspecialchars($title) : basename($row['url']);            
            $row['text']    = udm_get_res_field($res, $i, UDM_FIELD_TEXT);
            $row['keyw']    = udm_get_res_field($res, $i, UDM_FIELD_KEYWORDS);
            $row['desc']    = udm_get_res_field($res, $i, UDM_FIELD_DESC);
            $row['crc']     = udm_get_res_field($res, $i, UDM_FIELD_CRC);
            $row['siteid']  = udm_get_res_field($res, $i, UDM_FIELD_SITEID);
            $row['persite'] = udm_get_res_field_ex($res, $i, "PerSite"); 
            if (!empty($this->_fields)) {
                foreach ($this->_fields as $field) { 
                 $row[$field] = udm_get_res_field_ex($res, $i, $field);
                }
            }  
            $result['rows'][] = $row;
            unset ($row);
        }
        udm_free_res($res);
        include_once 'Search/Mnogosearch/Result.php';
        $resultSet = new Search_Mnogosearch_Result($result);
        $resultSet->resultsPerPage = $this->_resultsPerPage;
        $resultSet->pageNumber = $this->_pageNumber + 1;
        return $resultSet;
    } // end func getResult

    /**
     * Set options from the submitted form
     * NOT TESTED YET!
     *
     * @param  array                Submitted values ($_GET or $_POST) 
     *                              array or custom array
     * @return void
     * @access public
     */
    function setFormOptions($options) 
    {
        // Set all search parameters

        foreach ($GLOBALS['_SEARCH_MNOGOSEARCH_PARAMETERS'] as $key => $value) {

            $paramName = $key;
            $defaultValue = $value[$paramName];

            if (isset ($options[$key])) {
                $submitVal = $options[$key];
                if (is_array($defaultValue)) {
                    if (isset ($defaultValue[$submitVal])) {
                        $this->setParameter(
                            $paramName, $defaultValue[$submitVal]);
                    }
                    else {
                        $this->setParameter(
                            $paramName, $defaultValue[0]); // default
                    }
                }
                else {
                    $this->setParameter($paramName, $submitVal);
                }
            }
        }

        // Set all search limits

        foreach ($GLOBALS['_SEARCH_MNOGOSEARCH_LIMITS'] as $key => $value) {

            $limitName = $key;
            $methodName = $value[$limitName];

            if (isset ($options[$key])) {
                $submitVal = $options[$key];
                if ($methodeName = 'setLimit') {
                    $this-> $methodName ($limitName, $submitVal);
                }
                else {
                    $this-> $methodName ($submitVal);
                }
            }
        }

    } // end func setFormOptions

    /**
     * Set the date search limit
     *
     * NOT TESTED YET !
     *
     * This is very specific and might need to be changed to fit your needs.
     * You can also transform the submitted values (from $_GET or $_POST)
     * in order to make a range like this: array('begin' => xxx, 'end' => xxx).
     * Don't set 'end' if you don't need it.
     *
     * @param  mixed   Value(s) for limit
     * @return void
     * @access public
     */
    function setLimitDate($value) 
    {
        $constant = UDM_LIMIT_DATE;
        if (is_array($value)) {
            if (isset ($value['begin'])) {
                udm_add_search_limit(
                    $this->agent, $constant, '>'.$value['begin']);
            }
            if (isset ($value['end'])) {
                udm_add_search_limit(
                    $this->agent, $constant, '<'.$value['end']);
            }
        }
    } // end func setLimitDate

    /**
     * Set the url search limit
     * If autowild is set, then url is autocompleted
     *
     * @param  mixed    Value(s) for limit
     * @return void
     * @access public
     */
    function setLimitUrl($value) 
    {
        $constant = UDM_LIMIT_URL;
        if (is_array($value)) {
            if ($this->_autoWild) {
                foreach ($value as $url) {
                    if (preg_match('/^(http|https|news|ftp):\/\/(.*)/i', $url, $match)) {
                        $url = strtolower($match[1]).'://'.$match[2].'%';
                        udm_add_search_limit($this->agent, $constant, $url);
                    }
                    else {
                        udm_add_search_limit($this->agent, $constant, '%'.$url.'%');
                    }
                }
            }
            else {
                udm_add_search_limit($this->agent, $constant, $url);
            }
        }
        else {
            udm_add_search_limit($this->agent, $constant, $value);
        }
    } // end func setLimitUrl

    /**
     * Set the search limits in a generic fashion
     *
     * @param  string   Name of limit tag
     * @param  mixed    Value(s) for limit
     * @return void
     * @access public
     */
    function setLimit($name, $value) 
    {
        $constant = $this->_getUdmLimit($name);
        if (is_array($value)) {
            foreach ($value as $limit) {
                udm_add_search_limit($this->agent, $constant, $limit);
            }
        }
        else {
            udm_add_search_limit($this->agent, $constant, $value);
        }
    } // end func setLimit

    /**
     * Parses the query to make it usable by mnogosearch
     * Converts boolean operators and clean the query
     *
     * @param  string   Query words from the 'q' form variable
     * @return string   clean query
     * @access private
     */
    function _parseQuery($query) 
    {
        $query = urldecode($query);
        if ($this->_autoParse) {
            foreach($this->_query_replacements['and'] as $name) {
                $query = str_replace(' '.$name.' ', '&', $query);
            }
            $query = str_replace('&', ' & ', $query);
            foreach($this->_query_replacements['or'] as $name) {
                $query = str_replace(' '.$name.' ', '|', $query);
            }
            $query = str_replace('|', ' | ', $query);
            $query = preg_replace('/\s-(.*)/', '~\1', $query);
            $query = preg_replace('/^-(.*)/', '~\1', $query);
            foreach($this->_query_replacements['not'] as $name) {
                $query = str_replace(' '.$name.' ', '~', $query);
            }
            $query = str_replace('~', ' ~ ', $query);            
            $query = str_replace('  ', ' ', $query);
            $query = trim($query);

            if (preg_match('/\s[&\|~]\s/', $query)) {
                $this->setParameter(UDM_PARAM_SEARCH_MODE, 'UDM_MODE_BOOL');
            }
        }
        $this->words = preg_split('/\s(&|\||~|)\s?/', $query);
        return $query;
    } // end func _parseQuery

    /**
     * Sets whether to parse the query automatically
     * This will modify the query in order to accomodate boolean operators
     *
     * @param  bool     true to set _autoParse on
     * @return void
     * @access public
     */
    function setAutoParse($bool) 
    {
        $this->_autoParse = $bool;
    } // end func setAutoParse

    /**
    * Free the search agent. After you called this
    * methode you can't use this object any more. 
    * @access public
    */
    function disconnect() {
        udm_free_agent($this->agent);
    } // end func disconnect

    /**
     * Process required http parameter
     * @access private
     */
    function _processParameters() 
    {
        // pagenumber
        $this->_processParameter('pagenumber', 'page');
        // groupby
        if (!isset ($_POST[$this->_http_parameters['siteid']]) &&
            !isset ($_GET[$this->_http_parameters['siteid']]) &&
            !isset ($_POST[$this->_http_parameters['tag']]) &&
            !isset ($_GET[$this->_http_parameters['tag']])) {
            $this->_processParameter('groupbysite', 'group');
        }
        
        // url
        $this->_processLimit('url', 'url');
        // siteid
        $this->_processParameter('siteid', 'siteid');
        // results per page
        $this->_processParameter('pagesize', 'pagesize');
        // search mode
        $this->_processParameter('mode', 'mode');
        // wordmatch
        $this->_processParameter('wordmatch', 'wordmatch');
        // tag
        $this->_processLimit('tag', 'tag');
        // type
        $this->_processLimit('type', 'type');
        // sort order
        $this->_processParameter('sortorder', 'sortorder');
        // weightfactor
        $this->_processParameter('weightfactor', 'weightfactor');
    } // end func processParameters

    /**
     * Process a http parameter
     *
     * @param  string  Search parameter name
     * @param  string  Http parameter index name
     * @return void
     * @access private
     */
    function _processParameter($param, $name) 
    {
        if (isset ($_POST[$this->_http_parameters[$name]])) {
            $value = $_POST[$this->_http_parameters[$name]];
        }
        elseif (isset ($_GET[$this->_http_parameters[$name]])) {
            $value = $_GET[$this->_http_parameters[$name]];
        }
        if (isset ($value)) {
            // Pager fix
            if ($name == 'page') {
                // Mnogosearch use 0 as first page
                // and the pager 1 as first page.
                $value --;
            }
            if (isset($GLOBALS['_SEARCH_MNOGOSEARCH_PARAMETERS'][$param][$value])) {
                $value = $GLOBALS['_SEARCH_MNOGOSEARCH_PARAMETERS'][$param][$value];
            }
            $this->setParameter($param, $value);
        }
    } // end func processParameter

    /**
     * Process a http parameter
     *
     * @param  string  Search limit name
     * @param  string  Http parameter index name
     * @return void
     * @access private
     */
    function _processLimit($param, $name) 
    {
        if (isset ($_POST[$this->_http_parameters[$name]])) {
            $value = $_POST[$this->_http_parameters[$name]];
        }
        elseif (isset ($_GET[$this->_http_parameters[$name]])) {
            $value = $_GET[$this->_http_parameters[$name]];
        }
        if (isset ($value)) {
            $this->setLimit($param, $value);
        }
    } // end func processLimit

    /**
     * Accepts a renderer
     *
     * @param Search_Mnogosearch_Renderer     An Search_Mnogosearch_Renderer object
     * @access public
     * @return void
     */
    function accept(& $renderer) 
    {
        $this->_processParameters();
        $renderer->startForm($this);
        $result = $this->query();
        if (PEAR::isError($result)) {
            print $result->getMessage();
        } elseif ($result) {
            $result->accept($renderer);
        }
        $renderer->finishForm($this);
    } // end func accept

    /**
     * Returns an HTML version of the result
     *
     * @return   string     Html version of the form
     * @access   public
     */
    function toHtml() 
    {
        $renderer = & $this->defaultRenderer();
        $renderer->hlbeg=$this->_hlbeg;
        $renderer->hlend=$this->_hlend;
        $this->accept($renderer);
        return $renderer->toHtml();
    } // end func toHtml

    /**
     * Returns a reference to default renderer object
     *
     * @access public
     * @return Search_Mnogosearch_Renderer a default renderer object
     */
    function defaultRenderer() 
    {
        if (!isset ($GLOBALS['_Search_Mnogosearch_default_renderer'])) {
            include_once 'Search/Mnogosearch/Renderer/Default.php';
            $GLOBALS['_Search_Mnogosearch_default_renderer'] = 
                & new Search_Mnogosearch_Renderer_Default();
        }
        return $GLOBALS['_Search_Mnogosearch_default_renderer'];
    } // end func defaultRenderer

    /**
     * Add logic operators which are used by the auto parse feature.
     * 
     * @access public
     * @return void
     */
    function addLogicOperators($operators = array()) {
        foreach($operators['and'] as $op) {
            $this->_query_replacements['and'][] = $op;            
        }
        foreach($operators['or'] as $op) {
            $this->_query_replacements['or'][] = $op;            
        }
        foreach($operators['not'] as $op) {
            $this->_query_replacements['not'][] = $op;            
        }
    } // end func addLogicOperators
    
} // end class Search_Mnogosearch

?>
