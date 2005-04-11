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
 * This File contains the class Search_Mnogosearch_Renderer_Default.
 * @package  Search_Mnogosearch
 * @author   Gerrit Goetsch <goetsch@cross-solution.de>
 */
 
/** */
require_once 'Search/Mnogosearch/Renderer.php';

/**
 * A concrete renderer for Search_Mnogosearch
 *
 * @access   public
 * @author   Gerrit Goetsch <goetsch@cross-solution.de> 
 * @license  http://www.php.net/license/2_02.txt PHP License 2.02
 * @example  docs/search-example.php How to use this render
 * @package  Search_Mnogosearch
 * @category Search
 * @version  $Revision$
 */
class Search_Mnogosearch_Renderer_Default extends Search_Mnogosearch_Renderer 
{
    /**
     * The HTML of the search
     * @var      string
     * @access   private
     */
    var $_html = '';

    /**
     * The HTML of the search form
     * @var      string
     * @access   private
     */
    var $_form = '';

    /**
     * Form Template string
     * @var      string
     * @access   private
     */
    var $_formTemplate = 
        "\n<center>\n<form name=\"search\" method=\"get\">{content}\n</form>\n</center>";

    /**
     * Input Template string
     * @var      string
     * @access   private
     */
    var $_inputTemplate = 
        "\n\t<input maxlength=\"200\" name=\"{name}\" type=\"text\" value=\"{value}\" />
         \n\t<input name=\"submit\" type=\"submit\" value=\"Search\"/><br>
         \n\t<input name=\"{namegroup}\" type=\"checkbox\" value=\"1\" />Group by site";

    /**
     * Head Template string
     * @var      string
     * @access   private
     */
    var $_headTemplate = 
        "\n\t<tr><td><font size=-1>Results: {results} of {total}</font></td></tr>";

    /**
     * Row Template string
     * @var      string
     * @access   private
     */
    var $_rowTemplate = 
        "\n\t<tr><td><a href=\"{url}\" target=\"_blank\">{title}</a></td></tr><tr><td><font size=-1>{text}</font></td></tr>";

    /**
     * Row details Template string
     * @var      string
     * @access   private
     */
    var $_rowDetailTemplate = 
        "\n\t<tr><td><table width=\"100%\"><tr><td><a href=\"{url}\">{title}</a></td><td align=\"right\"><font size=-1>{text}</font></td></tr></table></td></tr>";

    /**
     * Result table Template string
     * @var      string
     * @access   private
     */
    var $_resultTemplate = "\n<table>{rows}\n</table>";

    /**
     * Pager Template string
     * @var      string
     * @access   private
     */
    var $_pagerTemplate = 
        "\n<hr>\n<center>\n<table width=\"1%\">\n\t<tr>{pager}\n\t</tr>\n</table>\n</center>";

    /**
     * Link Template string for the pager
     * @var      string
     * @access   private
     */
    var $_pagerLinkTemplate = 
        "\n\t<td style='white-space:nowrap' ><font size=\"-1\"><b><a href=\"{url}\">{name}</a></b></font>&nbsp;</td>";

    /**
     * No link Template string for the pager
     * @var      string
     * @access   private
     */
    var $_pagerNoLinkTemplate = 
        "\n\t<td><font size=\"-1\">{name}</font>&nbsp;</td>";

    /**
     * @access  private
     */
    var $_query = '';

    /**
     * @access  private
     */
    var $_http_parameters = array ();
    
    /**
    * Beginning HTML tag for word highlight
    */
    var $hlbeg = '<strong>';

    /**
    * Ending HTML tag for word highlight
    */
    var $hlend = '</strong>';    

    /**
     * Constructor
     *
     * @access public
     */
    function Search_Mnogosearch_Renderer_Default() 
    {
        $this->Search_Mnogosearch_Renderer();
    } // end constructor

    /**
     * Called when visiting a row
     *
     * @param    array     An Search_Mnogosearch_Result row array being visited
     * @access   public
     * @return   void
     */
    function renderRow(& $data) 
    {
        $title = $this->_highlightText($data['title']);
        $text = $this->_highlightText($data['text']);
        if ($data['persite'] != 0) {
            $row = str_replace('{title}', $title, $this->_rowTemplate);
            $row = str_replace('{text}', $text, $row);
            $row = str_replace('{url}', $data['url'], $row);
            $this->_html .= $row;
            $row = str_replace('{title}', 
                $data['persite']." results", 
                $this->_rowDetailTemplate);
            $row = str_replace('{text}', $data['lastmod'], $row);
            $url = $_SERVER['PHP_SELF'].'?'.
                $this->_http_parameters['query'].'='.
                urlencode($this->_query).
                '&'.$this->_http_parameters['siteid'].'='.$data['siteid'];
            $row = str_replace('{url}', $url, $row);
            $this->_html .= $row;
        } else {
            $row = str_replace('{title}', $title, $this->_rowTemplate);
            $row = str_replace('{text}', $text, $row);
            $row = str_replace('{url}', $data['url'], $row);
            $this->_html .= $row;
            $row = str_replace('{title}', "", $this->_rowDetailTemplate);
            $row = str_replace('{text}', $data['lastmod'], $row);
            $row = str_replace('{url}', $data['url'], $row);
            $this->_html .= $row;
        }
    } // end func renderRow

    /**
     * Called when visiting a form, before processing any results
     *
     * @param    Search_Mnogosearch_Result    An Search_Mnogosearch_Result 
     *                                        object being visited
     * @access   public
     * @return   void
     */
    function startResult(& $result) 
    {
        $start = ($result->pageNumber - 1) * $result->resultsPerPage;
        $end = $start + $result->numRows();
        $start ++;
        $row = $this->_headTemplate;
        $row = str_replace('{results}', $start.'-'.$end, $row);
        $row = str_replace('{total}', $result->results, $row);
        $this->_html .= $row;
        $this->_query = $result->getInfo('query');
    } // end func startResult

    /**
     * Called when visiting a form, after processing any results
     *
     * @param    Search_Mnogosearch_Result    An Search_Mnogosearch_Result 
     *                                        object being visited
     * @access   public
     * @return   void
     */
    function finishResult(& $result) 
    {
        $pager = $this->_renderPager(
            $this->_getPages($result, 20), 
            $result->pageNumber);
        $this->_html = str_replace(
            '{rows}', 
            $this->_html, 
            $this->_resultTemplate);
        $this->_html .= str_replace('{pager}', $pager, $this->_pagerTemplate);
    } // end func finishResult

    /**
     * returns the HTML generated for the pager
     *
     * @param array      The pager data array
     * @param int        The current page number
     * @access private
     * @return string
     */
    function _renderPager(& $pages, $curPage) 
    {
        $curPage = (int) $curPage;
        $htmlstr = "";
        if ($curPage != $pages['firstpage']) {
            $str = str_replace(
                '{url}', $pages['firstpageurl'], $this->_pagerLinkTemplate);
            $str = str_replace('{name}', '<<&nbsp;', $str);
            $htmlstr .= $str;
        } 
        if ($pages['prev'] != "") {
            $str = str_replace('{url}', $pages['prev'], $this->_pagerLinkTemplate);
            $str = str_replace('{name}', '<&nbsp;', $str);
            $htmlstr .= $str;
        } 
        foreach ($pages['pages'] as $pgnum => $link) {
            if ($pgnum == $curPage) {
                $htmlstr .= str_replace('{name}', $pgnum, $this->_pagerNoLinkTemplate);
            } else {
                $str = str_replace('{url}', $link, $this->_pagerLinkTemplate);
                $str = str_replace('{name}', $pgnum, $str);
                $htmlstr .= $str;
            }
        }
        if ($pages['next'] != "") {
            $str = str_replace('{url}', $pages['next'], $this->_pagerLinkTemplate);
            $str = str_replace('{name}', '&nbsp;>', $str);
            $htmlstr .= $str;
        } 
        if ($curPage != $pages['lastpage']) {
            $str = str_replace('{url}', $pages['lastpageurl'], $this->_pagerLinkTemplate);
            $str = str_replace('{name}', '&nbsp;>>', $str);
            $htmlstr .= $str;
        } 
        return $htmlstr;
    } // end func renderPager

    /**
     * returns an array with the pager data
     *
     * @param   Search_Mnogosearch_Result   An Search_Mnogosearch_Result object
     * @param   int                         The maximum pages to display
     * @access private
     * @return array
     */
    function _getPages(& $result, $maxpages = 20) 
    {
        $pageParameter = $this->_http_parameters['page'];
        $pageNumber = $result->pageNumber;

        $numrows = $result->results;
        if ($numrows == 0) {
            return null;
        }
        $queryString = $result->getInfo('queryString');
        $from = $result->resultsPerPage * ($pageNumber -1);
        $limit = $result->resultsPerPage;
        // Total number of pages
        $pages = (int) ceil($numrows / $limit);
        $data['numpages'] = $pages;
        // first & last page
        $data['firstpage'] = 1;
        $data['lastpage'] = $pages;
        // Build pages array
        $data['pages'] = array ();
        $pageUrl = $_SERVER['PHP_SELF'].'?'.$queryString;
        $pageUrl = str_replace('&'.$pageParameter.'='.$pageNumber, '', $pageUrl);
        $data['firstpageurl'] = $pageUrl.'&'.$pageParameter.'='."1";
        $data['lastpageurl'] = $pageUrl.'&'.$pageParameter.'='.$pages;
        $data['current'] = 0;
        for ($i = 1; $i <= $pages; $i ++) {
            $url = $pageUrl.'&'.$pageParameter.'='. ($i);
            $offset = $limit * ($i -1);
            $data['pages'][$i] = $url;
            // $from must point to one page
            if ($from == $offset) {
                // The current page we are
                $data['current'] = $i;
            }
        }
        // Limit number of pages (Google like display)
        if ($maxpages) {
            $radio = floor($maxpages / 2);
            $minpage = $data['current'] - $radio;
            if ($minpage < 1) {
                $minpage = 1;
            }
            $maxpage = $data['current'] + $radio -1;
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
            $pageUrl = $_SERVER['PHP_SELF'].'?'.$queryString;
            if (strpos($pageUrl, ''.$pageParameter.'='.$pageNumber)) {
                $pageUrl = str_replace(
                    ''.$pageParameter.'='.$pageNumber, 
                    ''.$pageParameter.'='. ($pageNumber -1), 
                    $pageUrl);
            } else {
                $pageUrl .= '&'.$pageParameter.'='. ($pageNumber -1);
            }
            $data['prev'] = $pageUrl;
        } else {
            $data['prev'] = null;
        }
        // Next link
        $next = $from + $limit;
        if ($next < $numrows) {
            $pageUrl = '';
            $pageUrl = $_SERVER['PHP_SELF'].'?'.$queryString;
            if (strpos($pageUrl, ''.$pageParameter.'='.$pageNumber)) {
                $pageUrl = str_replace(
                    ''.$pageParameter.'='.$pageNumber, 
                    ''.$pageParameter.'='. ($pageNumber +1), 
                    $pageUrl);
            } else {
                $pageUrl .= '&'.$pageParameter.'='. ($pageNumber +1);
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
            if ($data['current'] == ($pages -1)) {
                $data['remain'] = $numrows - ($limit * ($pages -1));
            } else {
                $data['remain'] = $limit;
            }
            $data['to'] = $data['current'] * $limit;
        }
        $data['numrows'] = $numrows;
        $data['from'] = $from +1;
        $data['limit'] = $limit;
        return $data;
    } // end func getPages

    /**
     * Called when visiting a form, before processing any form elements
     *
     * @param    Search_Mnogosearch    An Search_Mnogosearch object being visited
     * @access   public
     * @return   void
     */
    function startForm(& $agent) 
    {
        $this->_http_parameters = $agent->_http_parameters;
        return;
    } // end func startForm

    /**
     * Called when visiting a form, after processing all form elements
     *
     * @param    Search_Mnogosearch An Search_Mnogosearch object being visited
     * @access   public
     * @return   void
     */
    function finishForm(& $agent) 
    {
        $form = str_replace('{value}', $agent->query, $this->_inputTemplate);
        $form = str_replace('{name}', $this->_http_parameters['query'], $form);
        $form = str_replace('{namegroup}', $this->_http_parameters['group'], $form).'<br>';
        $form .=  "\n\tResults per page: ".$this->_getSelect(
            $this->_http_parameters['pagesize'],
            array('10' => '10', '20' => '20', '50' => '50')).'<br>';
        $form .=  "\n\tMatch: ".$this->_getSelect(
            $this->_http_parameters['mode'],
            array('All' => 'all', 'Any' => 'any', 'Boolean' => 'bool')).'<br>';
        $form .=  "\n\tSearch for: ".$this->_getSelect(
            $this->_http_parameters['wordmatch'],
            array(
                'Whole word'    => 'wrd',
                'Beginning'     => 'beg',
                'Ending'        => 'end',
                'Substring'     => 'sub'
            )).'<br>';
        $form .=  "\n\tSearch through: ".$this->_getSelect(
            $this->_http_parameters['type'],
            array(
                'All types'     => '',
                'text/html'     => 'text/html',
                'text/plain'    => 'text/plain'
            )).'<br>';
        $form .=  "\n\tSort by: ".$this->_getSelect(
            $this->_http_parameters['sortorder'],
            array('Relevancy' => 'RPD', 'Last Modified Date' => 'DRP')).'<br>';
        $form .=  "\n\tSearch in: ".$this->_getSelect(
            $this->_http_parameters['weightfactor'],
            array(
                'all sections'  => '2221',
                'Description'   => '2000',
                'Keywords'      => '0200',
                'Title'         => '0020',
                'Body'          => '0001'
            )).'<br>';
        
        $this->_form = str_replace('{content}', $form, $this->_formTemplate);        
        $this->_html = $this->_form."\n<hr>".$this->_html;
    } // end func finishForm

    /**
     * returns the HTML generated for the form
     *
     * @access public
     * @return string
     */
    function toHtml() 
    {
        return $this->_html;
    } // end func toHtml
    
    /**
     * Returns results text with highlights
     *
     * @param  string text  Text to be highlighted
     * @return string       highlighted text
     * @access private
     */
    function _highlightText($text = '') 
    {
        if ($text == '')
            return '';
        $str = $text;

        $str = str_replace("\2", $this->hlbeg, $str);
        $str = str_replace("\3", $this->hlend, $str);
        return $str;
    } // end func _highlightText
    
    /**
     * 
     * @access private
     */
    function _getSelect($name, $values=array())
    {
        
        $select = "<select name=\"$name\">";
        foreach ($values as $text => $value) {
            $selected = '';   

            if (isset($_GET[$name]) && $_GET[$name]==$value) {
                $selected = "selected=\"true\"";
            }
            $select .= "<option value=\"$value\" $selected>$text</option>";
        }
        $select .= "</select>";
        return $select;
    }
} // end class Search_Mnogosearch_Renderer_Default
?>
