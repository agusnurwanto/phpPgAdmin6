<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render tables. Formerly part of Misc.php.
 */
class HTMLHeaderController extends HTMLController
{
    public $controller_name = 'HTMLHeaderController';
    private $_no_output     = false;

    /**
     * sets the value of private member variable $_no_output.
     *
     * @param bool $flag [description]
     *
     * @return $this
     */
    public function setNoOutput($flag)
    {
        $this->_no_output = (bool) $flag;

        return $this;
    }

    /**
     * Prints the page header.  If member variable $this->_no_output is
     * set then no header is drawn.
     *
     * @param $title The title of the page
     * @param $script script tag
     * @param $do_print boolean if false, the function will return the header content
     * @param mixed $template
     */
    public function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig')
    {
        if (function_exists('newrelic_disable_autorum')) {
            newrelic_disable_autorum();
        }
        $appName        = $this->appName;
        $lang           = $this->lang;
        $plugin_manager = $this->plugin_manager;

        //$this->prtrace('appName', $appName);

        $viewVars = [];
        //$viewVars = $this->lang;
        if (isset($_SESSION['isolang'])) {
            $viewVars['isolang']   = $_SESSION['isolang'];
            $viewVars['applocale'] = $lang['applocale'];
        }

        $viewVars['dir']            = (0 != strcasecmp($lang['applangdir'], 'ltr')) ? ' dir="'.htmlspecialchars($lang['applangdir']).'"' : '';
        $viewVars['headertemplate'] = $template;
        $viewVars['title']          = ('' !== $title) ? ' - '.$title : '';
        $viewVars['appName']        = htmlspecialchars($this->appName).(('' != $title) ? htmlspecialchars(" - {$title}") : '');

        $viewVars['script'] = $script;
        //$this->prtrace($viewVars);
        $header_html = $this->view->fetch($template, $viewVars);

        /*$plugins_head = [];
        $_params      = ['heads' => &$plugins_head];

        $plugin_manager->do_hook('head', $_params);

        foreach ($plugins_head as $tag) {
        $header_html .= $tag;
        }*/

        if (!$this->_no_output && $do_print) {
            header('Content-Type: text/html; charset=utf-8');
            echo $header_html;
        } else {
            return $header_html;
        }
    }

    /**
     * Prints the page body.
     *
     * @param $doBody True to output body tag, false to return
     * @param $bodyClass - name of body class
     */
    public function printBody($doBody = true, $bodyClass = 'detailbody')
    {
        $bodyClass = htmlspecialchars($bodyClass);
        $bodyHtml  = '<body data-controller="'.$this->controller_name.'" class="'.$this->lang['applangdir'].' '.$bodyClass.'" >';
        $bodyHtml .= "\n";
        /*$bodyHtml .= '<div id="flexbox_wrapper">';
        $bodyHtml .= "\n";
        $bodyHtml .= '<div id="detail_container">';
        $bodyHtml .= "\n";*/

        if (!$this->_no_output && $doBody) {
            echo $bodyHtml;
        } else {
            return $bodyHtml;
        }
    }

    /**
     * Print out the page heading and help link.
     *
     * @param $title Title, already escaped
     * @param $help (optional) The identifier for the help link
     * @param mixed $do_print
     */
    public function printTitle($title, $help = null, $do_print = true)
    {
        $lang = $this->lang;

        $title_html = '<h2>';
        $title_html .= $this->misc->printHelp($title, $help, false);
        $title_html .= "</h2>\n";

        if ($do_print) {
            echo $title_html;
        } else {
            return $title_html;
        }
    }
}
