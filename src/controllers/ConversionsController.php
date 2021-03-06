<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class ConversionsController extends BaseController
{
    public $controller_name = 'ConversionsController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang = $this->lang;

        $action = $this->action;
        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['strconversions']);
        $this->printBody();

        switch ($action) {
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of conversions in the database.
     *
     * @param string $msg
     *
     * @return string|void
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('schema');
        $this->printTabs('schema', 'conversions');
        $this->printMsg($msg);

        $conversions = $data->getconversions();

        $columns = [
            'conversion'      => [
                'title' => $lang['strname'],
                'field' => Decorator::field('conname'),
            ],
            'source_encoding' => [
                'title' => $lang['strsourceencoding'],
                'field' => Decorator::field('conforencoding'),
            ],
            'target_encoding' => [
                'title' => $lang['strtargetencoding'],
                'field' => Decorator::field('contoencoding'),
            ],
            'default'         => [
                'title' => $lang['strdefault'],
                'field' => Decorator::field('condefault'),
                'type'  => 'yesno',
            ],
            'comment'         => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('concomment'),
            ],
        ];

        $actions = [];

        echo $this->printTable($conversions, $columns, $actions, 'conversions-conversions', $lang['strnoconversions']);
    }

    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $constraints = $data->getConstraints($_REQUEST['table']);

        $reqvars = $this->misc->getRequestVars('schema');

        $getIcon = function ($f) {
            switch ($f['contype']) {
                case 'u':
                    return 'UniqueConstraint';
                case 'c':
                    return 'CheckConstraint';
                case 'f':
                    return 'ForeignKey';
                case 'p':
                    return 'PrimaryKey';
            }
        };

        $attrs = [
            'text' => Decorator::field('conname'),
            'icon' => Decorator::callback($getIcon),
        ];

        return $this->printTree($constraints, $attrs, 'constraints');
    }
}
