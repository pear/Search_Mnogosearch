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
// +----------------------------------------------------------------------+
//
// $Id$

/**
* MnogoSearch Wrapper
*
* This class has been tested with PHP 4.3 and MnogoSearch 3.2.7+
* with MnogoSearch PHP Extension 1.66.
*
* It is intended to be used in applications
* where data needs to be separated from template and display.
*
* @author   Bertrand Mansion <bmansion@mamasam.com>
* @credits  Sergey Kartashoff <gluke@sky.net.ua>
*/

require_once('PEAR.php');

// mnogosearch parameters mapping

$GLOBALS['SEARCH_MNOGOSEARCH_PARAMETERS'] = 
    array(
        'ps'    => array('UDM_PARAM_PAGE_SIZE' => 20),
        'np'    => array('UDM_PARAM_PAGE_NUM' => 0),
        'sm'    => array('UDM_PARAM_SEARCH_MODE' => array('any' => 'UDM_MODE_ANY',
                                                        'bool'  => 'UDM_MODE_BOOL',
                                                        'phrase'=> 'UDM_MODE_PHRASE',
                                                        'all'   => 'UDM_MODE_ALL')),
        'wm'    => array('UDM_PARAM_WORD_MATCH'  => array('wrd' => 'UDM_MATCH_WORD',
                                                        'beg'   => 'UDM_MATCH_BEGIN',
                                                        'end'   => 'UDM_MATCH_END',
                                                        'sub'   => 'UDM_MATCH_SUBSTR')),
        'phrase'=> array('UDM_PARAM_PHRASE_MODE' => array('yes' => 'UDM_ENABLED',
                                                        'no'    => 'UDM_DISABLED')),
        'group' => array('UDM_PARAM_GROUPBYSITE' => array('yes' => 'UDM_ENABLED',
                                                        'no'    => 'UDM_DISABLED'))
    );

// mnogosearch search limits mapping

$GLOBALS['SEARCH_MNOGOSEARCH_LIMITS'] = 
    array(
        'url'   => array('UDM_LIMIT_URL'  => 'setLimitUrl'),
        'tag'   => array('UDM_LIMIT_TAG'  => 'setLimit'),
        'cat'   => array('UDM_LIMIT_CAT'  => 'setLimit'),
        'lang'  => array('UDM_LIMIT_LANG' => 'setLimit'),
        'type'  => array('UDM_LIMIT_TYPE' => 'setLimit'),
        'date'  => array('UDM_LIMIT_DATE' => 'setLimitDate')
    );

// error codes

define('SEARCH_MNOGOSEARCH_ERROR', -1);
define('SEARCH_MNOGOSEARCH_ERROR_DB', -2);
define('SEARCH_MNOGOSEARCH_ERROR_NOQUERY', -3);
define('SEARCH_MNOGOSEARCH_ERROR_NOTFOUND', -4);

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
    * Number of rows to display per page
    */
    var $resultsPerPage = 20;

    /**
    * Current page number
    */
    var $pageNumber = 0;

    /**
    * Add wildcards automatically to search limits by url
    */
    var $_autoWild = true;

    /**
    * Should the query be parsed automatically
    */
    var $_autoParse = true;

    /**
    * Array of words from the query
    */
    var $words = array();

    /**
    * Beginning HTML tag for word highlight
    */
    var $hlbeg = '<strong>';

    /**
    * Ending HTML tag for word highlight
    */
    var $hlend = '</strong>';

    /**
    * Number of rows found in result
    */
    var $resultsFound = 0;

    /**
    * Is the search mode set already ?
    */
    var $searchModeFlag = false;

    /**
    * Query words to search from 'q' form variable
    */
    var $query = '';

    /**
    * Query string from server environment
    */
    var $queryString = '';

    /**
    * Mnogosearch API version
    */
    var $mnogoAPIVersion;

    /**
    * Mnogosearch Agent object
    */
    var $agent;

    /**
     * Constructor
     *
     * @param  string DBAddr  DSN to your database
     * @param  string DBMode  (optional)Fill this one if you want to keep the old API style
     * @param  array params  Presets for mnogosearch agent
     * @return void
     * @access public
     */
    function Search_Mnogosearch($DBAddr, $DBMode = '', $params = array())
    {
        if (preg_match('/(.*)\?dbmode=(single|multi|crc|crc-multi|cache)$/', $DBAddr, $match)) {
            $this->DBAddr = $match[1];
            $this->DBMode = $match[2];
        } elseif ($DBAddr != '' && $DBMode != '') {
            $this->DBAddr = $DBAddr;
            $this->DBMode = $DBMode;
        } else {
            return PEAR::raiseError('Missing parameters DBAddr or DBMode to start search.', SEARCH_MNOGOSEARCH_ERROR, PEAR_ERROR_RETURN);
        }

        $this->mnogoAPIVersion = udm_api_version();
        if ($this->mnogoAPIVersion >= 30204) {
            $this->agent = udm_alloc_agent($this->DBAddr.'?dbmode='.$this->DBMode);
        } else {
            $this->agent = udm_alloc_agent($this->DBAddr, $this->DBMode);   
        }

        // Set agent parameters

        foreach ($params as $key => $value) {
            $this->setParameter($key, $value);
        }

    } // end constructor

    /**
     * Set autocompletion of url on/off
     *
     * @param  bool bool  Set this to true if you want autocompletion in url limit search mode
     * @return void
     * @access public
     */
    function setAutowild($bool = true)
    {
        $this->_autoWild = $bool;
    } // end func setAutowild

    /**
     * Set an option for this object
     *
     * @param  string name  name of the directive to set
     * @param  mixed value  value of the directive to set
     * @return returns true if parameter was set
     * @access public
     */
    function setParameter($name, $value)
    {
        $name = strtoupper($name);
        switch ($name) {
            case 'UDM_PARAM_PAGE_SIZE':
                $this->resultsPerPage = (int)$value;
                break;
            case 'UDM_PARAM_PAGE_NUM':
                $this->pageNumber = (int)$value;
                break;
            case 'UDM_PARAM_HLBEG':
                $this->hlbeg = $value;
                break;
            case 'UDM_PARAM_HLEND':
                $this->hlend = $value;
                break;
            case 'UDM_PARAM_SEARCH_MODE':
                $this->searchModeFlag = true;
                break;
        }
        $constant = constant($name);
        if (defined($value)) {
            $value = constant($value);
        }
        $error = udm_set_agent_param($this->agent, $constant, $value);
        if (!$error) {
            return PEAR::raiseError("Error while setting '$constant' parameter.", SEARCH_MNOGOSEARCH_ERROR, PEAR_ERROR_RETURN);
        }
        return true;
    } // end func setParameter

    /**
     * Get query result
     *
     * @param  string   query   query string
     * @return array    result array
     * @access public
     */
    function getResult($query = '')
    {
        if ($query == '') {
            // if no query, try to get 'q' variable from request
            if (isset($_POST['q'])) {
                $query = $_POST['q'];
            } elseif (isset($_GET['q'])) {
                $query = $_GET['q'];
            } else {
                return PEAR::raiseError('Query is empty.', SEARCH_MNOGOSEARCH_ERROR_NOQUERY, PEAR_ERROR_RETURN);
            }
        }

        $this->query = $this->_parseQuery($query);
        udm_set_agent_param($this->agent, UDM_PARAM_QUERY, $this->query);

        if ($this->queryString == '') {
            if (isset($_SERVER['QUERY_STRING'])) {
                $this->queryString = $_SERVER['QUERY_STRING'];
            } elseif (isset($_SERVER['argv'][0])) {
                $this->queryString = $_SERVER['argv'][0];
            }
            udm_set_agent_param($this->agent, UDM_PARAM_QSTRING, $this->queryString);
        }

        if (!$this->searchModeFlag) {
            // Default search mode is ANY
            udm_set_agent_param($this->agent, UDM_PARAM_SEARCH_MODE, UDM_MODE_ANY);
        }
        $res = udm_find($this->agent, $query);
        if (($error = udm_error($this->agent)) != '') {
            udm_free_res($res);
            return PEAR::raiseError($error, SEARCH_MNOGOSEARCH_ERROR_DB, PEAR_ERROR_RETURN);
        }
        $found = udm_get_res_param($res, UDM_PARAM_FOUND);
        $result = array();
        if (!$found) {
            udm_free_res($res);
            return PEAR::raiseError('No result found.', SEARCH_MNOGOSEARCH_ERROR_NOTFOUND, PEAR_ERROR_RETURN);
        } else {
            $this->resultsFound = $found;
            $result['found'] = $found;
        }

        // Global info

        $result['numrows']    = udm_get_res_param($res, UDM_PARAM_NUM_ROWS);
        $result['wordinfo']   = udm_get_res_param($res, UDM_PARAM_WORDINFO_ALL);
        $result['searchtime'] = udm_get_res_param($res, UDM_PARAM_SEARCHTIME);
        $result['first_doc']  = udm_get_res_param($res, UDM_PARAM_FIRST_DOC);
        $result['last_doc']   = udm_get_res_param($res, UDM_PARAM_LAST_DOC);

        // Row specific info

        for ($i = 0; $i < $result['numrows']; $i++) {
            $row = array();
            $row['rec_id']      = udm_get_res_field($res, $i, UDM_FIELD_URLID);
            $row['ndoc']        = udm_get_res_field($res, $i, UDM_FIELD_ORDER);
            $row['rating']      = udm_get_res_field($res, $i, UDM_FIELD_RATING);
            $row['url']         = udm_get_res_field($res, $i, UDM_FIELD_URL);
            $row['contype']     = udm_get_res_field($res, $i, UDM_FIELD_CONTENT);
            $row['docsize']     = udm_get_res_field($res, $i, UDM_FIELD_SIZE);
            $row['score']       = udm_get_res_field($res, $i, UDM_FIELD_SCORE);
            $row['lastmod']     = udm_get_res_field($res, $i, UDM_FIELD_MODIFIED);
            $row['title']       = ($title = udm_get_res_field($res, $i, UDM_FIELD_TITLE)) ? htmlspecialchars($title) : basename($row['url']);
            $row['title']       = $this->_highlightText($row['title']);
            $row['text']        = $this->_highlightText(htmlspecialchars(udm_get_res_field($res, $i, UDM_FIELD_TEXT)));
            $row['keyw']        = $this->_highlightText(htmlspecialchars(udm_get_res_field($res, $i, UDM_FIELD_KEYWORDS)));
            $row['desc']        = $this->_highlightText(htmlspecialchars(udm_get_res_field($res, $i, UDM_FIELD_DESC)));
            $row['crc']         = udm_get_res_field($res, $i, UDM_FIELD_CRC);
            $result['rows'][] = $row;
            unset($row);
        }
        udm_free_res($res);
        udm_free_agent($this->agent);
        return $result;
    } // end func getResult

    /**
     * Returns results text with highlights
     *
     * @param  string text  Text to be highlighted
     * @return string   highlighted text
     * @access private
     */ 
    function _highlightText($text = '')
    {
        if ($text == '') return '';
        $str = $text;
    
        if ($this->mnogoAPIVersion < 30200) {
            foreach ($this->words as $word) {
                $str = preg_replace("/([\s\t\r\n\~\!\@\#\$\%\^\&\*\(\)\-\_\=\+\\\|\{\}\[\]\;\:\'\"\<\>\?\/\,\.]+)($word)/i", "\\1".$this->hlbeg."\\2".$this->hlend, $str);
                $str = preg_replace("/^($word)/i", $this->hlbeg."\\1".$this->hlend, $str);
            }
        } else {
            $str = str_replace("\2", $this->hlbeg, $str);
            $str = str_replace("\3", $this->hlend, $str);
        }
        return $str;
    } // end func _highlightText

    /*
    * Gets all the data needed to paginate results
    * This is an adaption from DB_Pager by Tomas V. Cox
    * This is an associative array with the following
    * values filled in:
    *
    * array(
    *    'current' => X,    // current page url
    *    'numrows' => X,    // total number of results
    *    'next'    => X,    // url for the next page
    *    'prev'    => X,    // url for the prev page
    *    'remain'  => X,    // number of results remaning *in next page*
    *    'numpages'=> X,    // total number of pages
    *    'from'    => X,    // the row to start fetching
    *    'to'      => X,    // the row to stop fetching
    *    'limit'   => X,    // how many results per page
    *    'maxpages'   => X, // how many pages to show (Google style)
    *    'firstpage'  => X, // the row number of the first page
    *    'lastpage'   => X, // the row number where the last page starts
    *    'pages'   => array(    // assoc with page "number => url of page"
    *                1 => X,
    *                2 => X,
    *                3 => X
    *                )
    *    );
    * @param int $from    The row to start fetching
    * @param int $limit   How many results per page
    * @param int $numrows Number of results from query
    * @return   array associative array with data or DB_error on error
    * @access   public
    */
    function getPages($maxpages = false)
    {
        $numrows = $this->resultsFound;
        if ($numrows == 0) {
            return null;
        }
        $from = $this->resultsPerPage * $this->pageNumber;
        $limit = $this->resultsPerPage;

        // Total number of pages
        $pages = (int)ceil($numrows/$limit);
        $data['numpages'] = $pages;
        // first & last page
        $data['firstpage'] = 1;
        $data['lastpage']  = $pages;
        // Build pages array
        $data['pages'] = array();
        $pageUrl = $_SERVER['PHP_SELF'].'?'.$this->queryString;
        $pageUrl = str_replace('&np='.$this->pageNumber, '', $pageUrl);
        for ($i=1; $i <= $pages; $i++) {
            $url = $pageUrl.'&np='.($i-1);
            $offset = $limit * ($i-1);
            $data['pages'][$i] = $url;
            // $from must point to one page
            if ($from == $offset) {
                // The current page we are
                $data['current'] = $i;
            }
        }
        // Limit number of pages (Google like display)
        if ($maxpages) {
            $radio = floor($maxpages/2);
            $minpage = $data['current'] - $radio;
            if ($minpage < 1) {
                $minpage = 1;
            }
            $maxpage = $data['current'] + $radio - 1;
            if ($maxpage > $data['numpages']) {
                $maxpage = $data['numpages'];
            }
            foreach (range($minpage, $maxpage) as $page) {
                $tmp[$page] = $data['pages'][$page];
            }
            $data['pages'] = $tmp;
            $data['maxpages'] = $maxpages;
        } else {
            $data['maxpages'] = null;
        }
        // Prev link
        $prev = $from - $limit;
        if ($prev >= 0) {
            $pageUrl = '';
            $pageUrl = $_SERVER['PHP_SELF'].'?'.$this->queryString;
            if (strpos($pageUrl, 'np='.$this->pageNumber)) {
                $pageUrl = str_replace('np='.$this->pageNumber, 'np='.($this->pageNumber-1), $pageUrl);
            } else {
                $pageUrl .= '&np='.($this->pageNumber-1);
            }
            $data['prev'] = $pageUrl;
        } else {
            $data['prev'] = null;
        }
        // Next link
        $next = $from + $limit;
        if ($next < $numrows) {
            $pageUrl = '';
            $pageUrl = $_SERVER['PHP_SELF'].'?'.$this->queryString;
            if (strpos($pageUrl, 'np='.$this->pageNumber)) {
                $pageUrl = str_replace('np='.$this->pageNumber, 'np='.($this->pageNumber+1), $pageUrl);
            } else {
                $pageUrl .= '&np='.($this->pageNumber+1);
            }
            $data['next'] = $pageUrl;
        } else {
            $data['next'] = null;
        }
        // Results remaining in next page & Last row to fetch
        if ($data['current'] == $pages) {
            $data['remain'] = 0;
            $data['to'] = $numrows;
        } else {
            if ($data['current'] == ($pages - 1)) {
                $data['remain'] = $numrows - ($limit*($pages-1));
            } else {
                $data['remain'] = $limit;
            }
            $data['to'] = $data['current'] * $limit;
        }
        $data['numrows'] = $numrows;
        $data['from']    = $from + 1;
        $data['limit']   = $limit;
        return $data;
    } // end func getPages

    /**
     * Set options from the submitted form
     *
     * @param  array    options     Submitted values ($_GET or $_POST) array or custom array
     * @return void
     * @access public
     */
    function setFormOptions($options)
    {       
        // Set all search parameters

        foreach ($GLOBALS['SEARCH_MNOGOSEARCH_PARAMETERS'] as $key => $value) {

            $paramName = key($value);
            $defaultValue = $value[$paramName];

            if (isset($options[$key])) {
                $submitVal = $options[$key];
                if (is_array($defaultValue)) {
                    if (isset($defaultValue[$submitVal])) {
                        $this->setParameter($paramName, $defaultValue[$submitVal]);
                    } else {
                        $this->setParameter($paramName, $defaultValue[0]); // default
                    }
                } else {
                    $this->setParameter($paramName, $submitVal);
                }
            }
        }
        
        // Set all search limits

        foreach ($GLOBALS['SEARCH_MNOGOSEARCH_LIMITS'] as $key => $value) {

            $limitName = key($value);
            $methodName = $value[$limitName];

            if (isset($options[$key])) {
                $submitVal = $options[$key];
                $this->$methodName($limitName, $submitVal);
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
     * @param  string name  name of limit tag
     * @param  mixed value  value(s) for limit
     * @return void
     * @access public
     */
    function setLimitDate($name, $value)
    {
        $constant = constant('UDM_LIMIT_DATE');
        if (is_array($value)) {
            if (isset($value['begin'])) {
                udm_add_search_limit($this->agent, $constant, '>'.$value['begin']);
            }
            if (isset($value['end'])) {
                udm_add_search_limit($this->agent, $constant, '<'.$value['end']);
            }
        }
    } // end func setLimitDate
    
    /**
     * Set the url search limit
     * If autowild is set, then url is autocompleted
     *
     * @param  string name  name of limit tag
     * @param  mixed value  value(s) for limit
     * @return void
     * @access public
     */
    function setLimitUrl($name, $value)
    {
        $constant = constant('UDM_LIMIT_URL');
        if (is_array($value)) {
            if ($this->_autoWild) {
                foreach ($value as $url) {
                    if (preg_match('/^(http|https|news|ftp):\/\/(.*)/i', $url, $match)) {
                        $url = strtolower($match[1]).'://'.$match[2].'%';
                        udm_add_search_limit($this->agent, $constant, $url);
                    } else {
                        udm_add_search_limit($this->agent, $constant, '%'.$url.'%');
                    }
                }
            } else {
                udm_add_search_limit($this->agent, $constant, $url);            
            }
        } else {
            udm_add_search_limit($this->agent, $constant, $value);
        }
    } // end func setLimitUrl

    /**
     * Set the search limits in a generic fashion
     *
     * @param  string name  name of limit tag
     * @param  mixed value  value(s) for limit
     * @return void
     * @access public
     */
    function setLimit($name, $value)
    {
        $constant = constant($name);
        if (is_array($value)) {
            foreach ($value as $limit) {
                udm_add_search_limit($this->agent, $constant, $limit);
            }
        } else {
            udm_add_search_limit($this->agent, $constant, $value);
        }
    } // end func setLimit

    /**
     * Parses the query to make it usable by mnogosearch
     * Converts boolean operators and clean the query
     *
     * @param  string   query   Query words from the 'q' form variable
     * @return string   clean query
     * @access private
     */
    function _parseQuery($query)
    {
        $query = urldecode($query);
        if ($this->_autoParse) {
            $query = str_replace('&&', '&', $query);
            $query = str_replace(' AND ', '&', $query);
            $query = str_replace(' and ', '&', $query);
            $query = str_replace('&', ' & ', $query);
            $query = str_replace('||', '|', $query);
            $query = str_replace(' OR ', '|', $query);
            $query = str_replace(' or ', '|', $query);
            $query = str_replace('|', ' | ', $query);
            $query = preg_replace('/\s-(.*)/', '~\1', $query);
            $query = preg_replace('/^-(.*)/', '~\1', $query);
            $query = str_replace(' NOT ', '~', $query);
            $query = str_replace(' not ', '~', $query);
            $query = str_replace('~', ' ~ ', $query);
            $query = str_replace('  ', ' ', $query);
            $query = trim($query);

            if (preg_match('/\s[&\|~]\s/', $query)) {
                $this->setParameter('UDM_PARAM_SEARCH_MODE','UDM_MODE_BOOL');
            }
        }
        $this->words = preg_split('/\s(&|\||~|)\s?/', $query);
        return $query;
    } // end func _parseQuery

    /**
     * Sets whether to parse the query automatically
     * This will modify the query in order to accomodate boolean operators
     *
     * @param  bool     bool    true to set _autoParse on
     * @return void
     * @access public
     */
    function setAutoParse($bool)
    {
        $this->_autoParse = $bool;
    } // end func setAutoParse
} // end class Search_Mnogosearch
?>