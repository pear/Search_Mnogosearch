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
 * This File contains the abstract class Search_Mnogosearch_Renderer.
 * @package  Search_Mnogosearch
 * @author   Gerrit Goetsch <goetsch@cross-solution.de>
 */
 
/**
 * An abstract base class for Mnogosearch renderers
 *
 * The class implements a Visitor design pattern
 *
 * @access public
 * @abstract
 * @author   Gerrit Goetsch <goetsch@cross-solution.de> 
 * @license  http://www.php.net/license/2_02.txt PHP License 2.02
 * @category Search
 * @package Search_Mnogosearch
 * @version  $Revision$
 */
class Search_Mnogosearch_Renderer 
{
    /**
    * Constructor
    *
    * @public
    */
    function Search_Mnogosearch_Renderer() 
    {
    } // end constructor

    /**
     * Called when visiting a row
     *
     * @param    array     An Search_Mnogosearch_Result row array being visited
     * @access   public
     * @return   void
     * @abstract
     */
    function renderRow(& $data) 
    {
        return;
    } // end func renderRow

    /**
     * Called when visiting a form, before processing any results
     *
     * @param    Search_Mnogosearch_Result    An Search_Mnogosearch_Result object being visited
     * @access   public
     * @return   void
     * @abstract
     */
    function startResult(& $result) 
    {
        return;
    } // end func startResult

    /**
     * Called when visiting a form, after processing any results
     *
     * @param    Search_Mnogosearch_Result    An Search_Mnogosearch_Result object being visited
     * @access   public
     * @return   void
     * @abstract
     */
    function finishResult(& $result) 
    {
        return;
    } // end func finishResult

    /**
     * Called when visiting a form, before processing any form elements
     *
     * @param    Search_Mnogosearch    An Search_Mnogosearch object being visited
     * @access   public
     * @return   void
     * @abstract
     */
    function startForm(& $agent) 
    {
        return;
    } // end func startForm

    /**
     * Called when visiting a form, after processing all form elements
     *
     * @param    Search_Mnogosearch     An Search_Mnogosearch object being visited
     * @access   public
     * @return   void
     * @abstract
     */
    function finishForm(& $agent) 
    {
        return;
    } // end func finishForm

} // end class Search_Mnogosearch_Renderer
?>
