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
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/ITStatic.php';
require_once 'HTML/Template/Sigma.php';
require_once 'Pager/Pager.php';

/**
 * A concrete renderer for Search_Mnogosearch.
 * This renderer uses HTML_Template_Sigma, HTML_QuickForm 
 * and Pager.
 *
 * @access   public
 * @author   Gerrit Goetsch <goetsch@cross-solution.de> 
 * @author   Carsten Bleek <bleek@cross-solution.de> 
 * @license  http://www.php.net/license/2_02.txt PHP License 2.02
 * @example  docs/sigma-example.php How to use this render
 * @package  Search_Mnogosearch
 * @category Search
 * @version  $Revision$
 */
class Search_Mnogosearch_Renderer_Sigma extends Search_Mnogosearch_Renderer 
{
    /**
    * The template
    * @access private
    */
    var $_tpl = null;

    /**
    * The template variable names.
    * @access private
    */
    var $_variable_names = array(
        'contentmain' => 'contentmain', 
        'contenttop'  => 'contenttop', 
        'query'       => 'query', 
        'date'        => 'date', 
        'from'        => 'from', 
        'to'          => 'to', 
        'totalItems'  => 'totalItems', 
        'wordinfo'    => 'wordinfo', 
        'searchtime'  => 'searchtime', 
        'title'       => 'title', 
        'description' => 'description', 
        'link'        => 'link', 
        'numOfPages'  => 'numOfPages', 
        'morelinks'   => 'morelinks', 
        'site'        => 'site'
    );

    /**
    * The template names to use.
    * @access private
    */
    var $_templates = array(
        'groupbysite'  => 'groupbysite.html', 
        'single'       => 'single.html', 
        'noresult'     => 'noresult.html', 
        'searchform'   => 'searchform.html'
    );

    /**
     * The http parameter names
     * @access private
     */
    var $_http_parameters = array();

    /**
     * The query
     * @access private
     */
    var $_query = '';

    /**
     * The form
     * @access private
     */
    var $_form = null;

    /**
     * The renderer
     * @access private
     */
    var $_renderer = null;

    /**
    * The result flag.
    * @access private
    */
    var $_results = false;

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
    * Pager parameters
    * @access private
    */
    var $_pager_params = array(
        'prevImg'               => "prev",
        'nextImg'               => "next",
        'separator'             => "",
        'linkClass'             => "pager",
        'spacesBeforeSeparator' => 1,
        'spacesAfterSeparator'  => 1,
        'lastPagePre'           => "",
        'lastPagePost'          => "",
        'lastPageText'          => "last",
        'firstPagePre'          => "",
        'firstPagePost'         => "",
        'firstPageText'         => "first",
        'curPageLinkClassName'  => "pagerCurrentLink",
        'clearIfVoid'           => "",
        'mode'                  => 'Sliding',
        'delta'                 => 2
    );
    
    /**
     * Constructor
     * @param object The Sigma template.
     * @access public
     */
    function Search_Mnogosearch_Renderer_Sigma(& $tpl) 
    {
        $this->Search_Mnogosearch_Renderer();
        $this->_tpl = & $tpl;
        $this->_renderer = & new HTML_QuickForm_Renderer_ITStatic($tpl);
        $this->_renderer->setRequiredTemplate(
            '{label}<font color="red" size="1">*</font>');
        $this->_renderer->setErrorTemplate(
            '<font color="orange" size="1">{error}</font><br/>{html}');

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
            $this->_tpl->addBlockfile($this->_variable_names['contentmain'], 'liste', $this->_templates['groupbysite']);
            $more = $_SERVER['PHP_SELF'].'?'.$this->_http_parameters['query'].'='.urlencode($this->_query).'&'.$this->_http_parameters['siteid'].'='.$data['siteid'];
            $this->_tpl->setVariable($this->_variable_names['numOfPages'], $data['persite']);
            $this->_tpl->setVariable($this->_variable_names['morelinks'], $more);
            $this->_tpl->setVariable($this->_variable_names['site'], $data['siteid']);
        } else {
            $this->_tpl->addBlockfile($this->_variable_names['contentmain'], 'liste', $this->_templates['single']);
        }
        $this->_tpl->setVariable($this->_variable_names['title'], $title);
        $this->_tpl->setVariable($this->_variable_names['description'], $text);
        $this->_tpl->setVariable($this->_variable_names['date'], $data['lastmod']);
        $this->_tpl->setVariable($this->_variable_names['link'], $data['url']);
        $this->_tpl->parse('added_block');
        return;
    } // end func renderRow

    /**
     * Called when visiting a form, before processing any results
     *
     * @param    Search_Mnogosearch_Result An Search_Mnogosearch_Result object being visited
     * @access   public
     * @return   void
     */
    function startResult(& $result) 
    {
        $this->_results = true;
        $this->_query = $result->getInfo('query');
        $this->_tpl->setVariable($this->_variable_names['from'], $result->getInfo(SEARCH_MNOGOSEARCH_INFO_FIRST_DOC));
        $this->_tpl->setVariable($this->_variable_names['to'], $result->getInfo(SEARCH_MNOGOSEARCH_INFO_LAST_DOC));
        $this->_tpl->setVariable($this->_variable_names['totalItems'], $result->getInfo(SEARCH_MNOGOSEARCH_FOUND));
        $this->_tpl->setVariable($this->_variable_names['wordinfo'], $result->getInfo(SEARCH_MNOGOSEARCH_INFO_WORDINFO));
        $this->_tpl->setVariable($this->_variable_names['searchtime'], $result->getInfo(SEARCH_MNOGOSEARCH_INFO_SEARCHTIME));
        $this->_tpl->setVariable($this->_variable_names['query'], $result->getInfo('query'));
        return;
    } // end func startResult

    /**
     * Called when visiting a form, after processing any results
     *
     * @param    Search_Mnogosearch_Result An Search_Mnogosearch_Result object being visited
     * @access   public
     * @return   void
     */
    function finishResult(& $result) 
    {
        $this->_renderPager($this->_tpl, $result->results, $result->resultsPerPage);
        return;
    } // end func finishResult

    /**
     * Called when visiting a form, before processing any form elements
     *
     * @param    Search_Mnogosearch  An Search_Mnogosearch object being visited
     * @access   public
     * @return   void
     */
    function startForm(& $agent) 
    {
        $this->_http_parameters = $agent->_http_parameters;

        $this->_form = & new HTML_QuickForm('search', 'GET');
        $this->_form->addElement('text', $this->_http_parameters['query'], _("Volltextsuche"), array('maxlength' => '200', 'class' => 'formFieldLong'));
        $this->_form->addElement('submit', 'button', 'Search');
        $this->_form->addElement('checkbox', 'group', _("Group"), 'Group by site');

        if (isset ($_GET[$this->_http_parameters['group']]) && $_GET[$this->_http_parameters['group']] == "1") {
            $this->_form->setConstants(array("group" => "1"));
        } else {
            $this->_form->setConstants(array("group" => "0"));
        }

        return;
    } // end func startForm

    /**
     * Called when visiting a form, after processing all form elements
     *
     * @param    Search_Mnogosearch  An Search_Mnogosearch object being visited
     * @access   public
     * @return   void
     */
    function finishForm(& $agent) 
    {
        if (!$this->_results) {
            $this->_tpl->addBlockfile($this->_variable_names['contentmain'], 'liste', $this->_templates['noresult']);
            $this->_tpl->setVariable($this->_variable_names['query'], $agent->query);
        }

        $this->_tpl->addBlockfile($this->_variable_names['contenttop'], 'form', $this->_templates['searchform']);
        $this->_form->accept($this->_renderer);
        return;
    } // end func finishForm

    /**
     * Sets the variable names of the templates
     *
     * @param    array     A array with the names of the variable.
     * @access   public
     * @return   void
     */
    function setVariableNames($names = array()) 
    {
        foreach ($names as $name => $value) {
            $this->_variable_names[$name] = $value;
        }
    } // end func setVariableNames

    /**
     * Sets parameters for the pager
     *
     * @param    array     A array with the params.
     * @access   public
     * @return   void
     */
    function setPagerOptions($options = array()) 
    {
        foreach ($options as $name => $value) {
            $this->_pager_params[$name] = $value;
        }
    } // end func setPagerOptions

    /**
     * Sets the templates to use for different views.
     *
     * @param    array     A array with the names of the templates.
     * @access   public
     * @return   void
     */
    function setTemplates($tpls = array()) 
    {
        foreach ($tpls as $tpl => $value) {
            $this->_templates[$tpl] = $value;
        }
    } // end func setTemplates

    /**
     * Renders the pager.
     *
     * @param    object      A templete object
     * @param    int         Number of total items
     * @param    int         Number of items per page
     * @access   private
     * @return   void
     */
    function _renderPager(& $tpl, $totalItems, $perPage) 
    {
        $this->_pager_params['totalItems'] = $totalItems;
        $this->_pager_params['perPage']    = $perPage;
        $this->_pager_params['urlVar']     = & $this->_http_parameters['page'];
        $pager = & Pager::factory($this->_pager_params);
        $data = $pager->getPageData();
        $array['links'] = $pager->getLinks();
        $array['totalItems'] = $totalItems;
        list ($array['from'], $array['to']) = $pager->getOffsetByPageId();
        $tpl->setVariable('from', $array['from']);
        $tpl->setVariable('next', $array['links']['next']);
        $tpl->setVariable('back', $array['links']['back']);
        $tpl->setVariable('pages', $array['links']['all']);
        $tpl->setVariable('to', $array['to']);
        $tpl->setVariable('totalItems', $array['totalItems']);
    } // end func renderPager
    
    /**
     * Returns results text with highlights
     *
     * @param  string   Text to be highlighted
     * @return string   highlighted text
     * @access private
     */
    function _highlightText($text = '') 
    {
        if ($text == '') {
            return '';
        }
        $str = $text;

        $str = str_replace("\2", $this->_hlbeg, $str);
        $str = str_replace("\3", $this->_hlend, $str);
        return $str;
    } // end func _highlightText

    /**
     * Sets the highlight tag.
     *
     * @param  array An array with the higlicht tags.
     * @return void
     * @access public
     */
    function setHighlightTags($tags = array())
    {
        if (isset($tags['begin'])) {
            $this->_hlbeg=$tags['begin'];
        }
        if (isset($tags['end'])) {
            $this->_hlend=$tags['end'];
        }
    } // end func setHighlightTags
    
} // end class Search_Mnogosearch_Renderer_Sigma
?>
