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
// | Author: Gerrit Goetsch <goetsch@cross-solution.de>                   |
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * This File contains the class Search_Mnogosearch_Result.
 * @package  Search_Mnogosearch
 * @author   Gerrit Goetsch <goetsch@cross-solution.de>
 */
 
/**
 * The result class for Mnogosearch.
 * Contains the search results, result informations
 * and some search informations.
 *
 *
 * @access public
 * @author   Gerrit Goetsch <goetsch@cross-solution.de> 
 * @license  http://www.php.net/license/2_02.txt PHP License 2.02
 * @package  Search_Mnogosearch
 * @category Search
 * @version  $Revision$
 */
class Search_Mnogosearch_Result {
    /** 
    * internal result set.
    * @access private
    */
    var $_result;
    /**
    * internal row counter.
    * @access private
    */
    var $_i;

    /**
    * Number of rows to display per page
    */
    var $resultsPerPage = 20;
    /**
    * Current page number 
    * Page numbers starting from 1.
    */
    var $pageNumber = 1;
    /**
    * Number of actual results
    * @access private
    */
    var $_rows = 0;
    /** 
    * The search query
    * @access private
    */
    var $_query = '';
    /**
    * Number of total results for this search query
    */
    var $results = 0;
    /**
     * Constructor
     *
     * @param  array result  The result as array
     * @return void
     * @access private
     */
    function Search_Mnogosearch_Result($result) 
    {
        $this->_result = $result;
        $this->_i = 0;
        $this->_rows = $result[SEARCH_MNOGOSEARCH_INFO_NUMROW];
        $this->_query = $result['query'];
        $this->results = $this->getInfo(SEARCH_MNOGOSEARCH_FOUND);
    } // end constructor 

    /**
     * Returns the the next search results.
     * @return array
     * @access public 
     */
    function fetchRow() 
    {
        if ($this->_i >= $this->_rows) {
            return false;
        }
        return $this->_result['rows'][$this->_i++];
    } // end func fetchRow

    /**
     * Returns infos about the search result. Using
     * the RESULT_MNOGOSEARCH_ define´s.
     * @param string RESULT_MNOGOSEARCH_ define´s.
     * @return the infos.
     * @access public
     */
    function getInfo($info) 
    {
        return $this->_result[$info];
    } // end func getInfo

    /**
    * Returns the number of rows for the
    * actual result.
    * @return the number of rows
    * @access public
    */
    function numRows() 
    {
        return $this->_rows;
    } // end func numRows

    /**
     * Accepts a renderer
     *
     * @param object     An Search_Mnogosearch_Renderer object
     * @access public
     * @return void
     */
    function accept(& $renderer) 
    {
        $renderer->startResult($this);
        while ($row = $this->fetchRow()) {
            $renderer->renderRow($row);
        }
        $renderer->finishResult($this);
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
        $this->accept($renderer);
        return $renderer->toHtml();
    } // end func toHtml

    /**
     * Returns a reference to default renderer object
     *
     * @access public
     * @return object a default renderer object
     */
    function defaultRenderer() 
    {
        if (!isset ($GLOBALS['_Search_Mnogosearch_default_renderer'])) {
            include_once ('Search/Mnogosearch/Renderer/Default.php');
            $GLOBALS['_Search_Mnogosearch_default_renderer'] = 
                & new Search_Mnogosearch_Renderer_Default();
        }
        return $GLOBALS['_Search_Mnogosearch_default_renderer'];
    } // end func defaultRenderer

} // end class Result_Mnogosearch
?>
