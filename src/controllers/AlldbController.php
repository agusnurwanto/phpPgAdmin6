<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class AlldbController extends BaseController
{
    public $controller_name = 'AlldbController';
    public $table_place     = 'alldb-databases';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang   = $this->lang;
        $action = $this->action;

        if ('tree' == $action) {
            return $this->doTree();
        }

        $this->printHeader($lang['strdatabases']);
        $this->printBody();

        switch ($action) {
            case 'export':
                $this->doExport();

                break;
            case 'save_create':
                if (isset($_POST['cancel'])) {
                    $this->doDefault();
                } else {
                    $this->doSaveCreate();
                }

                break;
            case 'create':
                $this->doCreate();

                break;
            case 'drop':
                if (isset($_REQUEST['drop'])) {
                    $this->doDrop(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_drop':
                $this->doDrop(true);

                break;
            case 'alter':
                if (isset($_POST['oldname'], $_POST['newname']) && !isset($_POST['cancel'])) {
                    $this->doAlter(false);
                } else {
                    $this->doDefault();
                }

                break;
            case 'confirm_alter':
                $this->doAlter(true);

                break;
            default:
                $this->doDefault();

                break;
        }

        return $this->printFooter();
    }

    /**
     * Show default list of databases in the server.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
        $lang = $this->lang;

        $this->printTrail('server');
        $this->printTabs('server', 'databases');
        $this->printMsg($msg);
        $data      = $this->misc->getDatabaseAccessor();
        $databases = $data->getDatabases();

        $columns = [
            'database'   => [
                'title' => $lang['strdatabase'],
                'field' => Decorator::field('datname'),
                'url'   => \SUBFOLDER . "/redirect/database?{$this->misc->href}&amp;",
                'vars'  => ['database' => 'datname'],
            ],
            'owner'      => [
                'title' => $lang['strowner'],
                'field' => Decorator::field('datowner'),
            ],
            'encoding'   => [
                'title' => $lang['strencoding'],
                'field' => Decorator::field('datencoding'),
            ],
            'lc_collate' => [
                'title' => $lang['strcollation'],
                'field' => Decorator::field('datcollate'),
            ],
            'lc_ctype'   => [
                'title' => $lang['strctype'],
                'field' => Decorator::field('datctype'),
            ],
            'tablespace' => [
                'title' => $lang['strtablespace'],
                'field' => Decorator::field('tablespace'),
            ],
            'dbsize'     => [
                'title' => $lang['strsize'],
                'field' => Decorator::field('dbsize'),
                'type'  => 'prettysize',
            ],
            'actions'    => [
                'title' => $lang['stractions'],
            ],
            'comment'    => [
                'title' => $lang['strcomment'],
                'field' => Decorator::field('datcomment'),
            ],
        ];

        $actions = [
            'multiactions' => [
                'keycols' => ['database' => 'datname'],
                'url'     => 'alldb.php',
                'default' => null,
            ],
            'drop'         => [
                'content'     => $lang['strdrop'],
                'attr'        => [
                    'href' => [
                        'url'     => 'alldb.php',
                        'urlvars' => [
                            'subject'      => 'database',
                            'action'       => 'confirm_drop',
                            'dropdatabase' => Decorator::field('datname'),
                        ],
                    ],
                ],
                'multiaction' => 'confirm_drop',
            ],
            'privileges'   => [
                'content' => $lang['strprivileges'],
                'attr'    => [
                    'href' => [
                        'url'     => 'privileges.php',
                        'urlvars' => [
                            'subject'  => 'database',
                            'database' => Decorator::field('datname'),
                        ],
                    ],
                ],
            ],
        ];
        if ($data->hasAlterDatabase()) {
            $actions['alter'] = [
                'content' => $lang['stralter'],
                'attr'    => [
                    'href' => [
                        'url'     => 'alldb.php',
                        'urlvars' => [
                            'subject'       => 'database',
                            'action'        => 'confirm_alter',
                            'alterdatabase' => Decorator::field('datname'),
                        ],
                    ],
                ],
            ];
        }

        if (!$data->hasTablespaces()) {
            unset($columns['tablespace']);
        }

        if (!$data->hasServerAdminFuncs()) {
            unset($columns['dbsize']);
        }

        if (!$data->hasDatabaseCollation()) {
            unset($columns['lc_collate'], $columns['lc_ctype']);
        }

        if (!isset($data->privlist['database'])) {
            unset($actions['privileges']);
        }

        echo $this->printTable($databases, $columns, $actions, $this->table_place, $lang['strnodatabases']);

        $navlinks = [
            'create' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'alldb.php',
                        'urlvars' => [
                            'action' => 'create',
                            'server' => $_REQUEST['server'],
                        ],
                    ],
                ],
                'content' => $lang['strcreatedatabase'],
            ],
        ];
        $this->printNavLinks($navlinks, $this->table_place, get_defined_vars());
    }

    public function doTree()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $databases = $data->getDatabases();

        $reqvars = $this->misc->getRequestVars('database');

        //$this->prtrace($reqvars);

        $attrs = [
            'text'    => Decorator::field('datname'),
            'icon'    => 'Database',
            'toolTip' => Decorator::field('datcomment'),
            'action'  => Decorator::redirecturl('redirect.php', $reqvars, ['database' => Decorator::field('datname')]),
            'branch'  => Decorator::url('/src/views/database', $reqvars, ['action' => 'tree', 'database' => Decorator::field('datname')]),
        ];

        return $this->printTree($databases, $attrs, 'databases');
    }

    /**
     * Display a form for alter and perform actual alter.
     *
     * @param mixed $confirm
     */
    public function doAlter($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('database');
            $this->printTitle($lang['stralter'], 'pg.database.alter');

            echo '<form action="' . \SUBFOLDER . "/src/views/alldb.php\" method=\"post\">\n";
            echo "<table>\n";
            echo "<tr><th class=\"data left required\">{$lang['strname']}</th>\n";
            echo '<td class="data1">';
            echo "<input name=\"newname\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
            htmlspecialchars($_REQUEST['alterdatabase']), "\" /></td></tr>\n";

            if ($data->hasAlterDatabaseOwner() && $data->isSuperUser()) {
                // Fetch all users

                $rs    = $data->getDatabaseOwner($_REQUEST['alterdatabase']);
                $owner = isset($rs->fields['usename']) ? $rs->fields['usename'] : '';
                $users = $data->getUsers();

                echo "<tr><th class=\"data left required\">{$lang['strowner']}</th>\n";
                echo '<td class="data1"><select name="owner">';
                while (!$users->EOF) {
                    $uname = $users->fields['usename'];
                    echo '<option value="', htmlspecialchars($uname), '"',
                    ($uname == $owner) ? ' selected="selected"' : '', '>', htmlspecialchars($uname), "</option>\n";
                    $users->moveNext();
                }
                echo "</select></td></tr>\n";
            }
            if ($data->hasSharedComments()) {
                $rs      = $data->getDatabaseComment($_REQUEST['alterdatabase']);
                $comment = isset($rs->fields['description']) ? $rs->fields['description'] : '';
                echo "<tr><th class=\"data left\">{$lang['strcomment']}</th>\n";
                echo '<td class="data1">';
                echo '<textarea rows="3" cols="32" name="dbcomment">',
                htmlspecialchars($comment), "</textarea></td></tr>\n";
            }
            echo "</table>\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"alter\" />\n";
            echo $this->misc->form;
            echo '<input type="hidden" name="oldname" value="',
            htmlspecialchars($_REQUEST['alterdatabase']), "\" />\n";
            echo "<input type=\"submit\" name=\"alter\" value=\"{$lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } else {
            if (!isset($_POST['owner'])) {
                $_POST['owner'] = '';
            }

            if (!isset($_POST['dbcomment'])) {
                $_POST['dbcomment'] = '';
            }

            if (0 == $data->alterDatabase($_POST['oldname'], $_POST['newname'], $_POST['owner'], $_POST['dbcomment'])) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($lang['strdatabasealtered']);
            } else {
                $this->doDefault($lang['strdatabasealteredbad']);
            }
        }
    }

    /**
     * Show confirmation of drop and perform actual drop.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if (empty($_REQUEST['dropdatabase']) && empty($_REQUEST['ma'])) {
            return $this->doDefault($lang['strspecifydatabasetodrop']);
        }

        if ($confirm) {
            $this->printTrail('database');
            $this->printTitle($lang['strdrop'], 'pg.database.drop');

            echo '<form action="' . \SUBFOLDER . "/src/views/alldb.php\" method=\"post\">\n";
            //If multi drop
            if (isset($_REQUEST['ma'])) {
                foreach ($_REQUEST['ma'] as $v) {
                    $a = unserialize(htmlspecialchars_decode($v, ENT_QUOTES));
                    echo '<p>', sprintf($lang['strconfdropdatabase'], $this->misc->printVal($a['database'])), "</p>\n";
                    printf('<input type="hidden" name="dropdatabase[]" value="%s" />', htmlspecialchars($a['database']));
                }
            } else {
                echo '<p>', sprintf($lang['strconfdropdatabase'], $this->misc->printVal($_REQUEST['dropdatabase'])), "</p>\n";
                echo '<input type="hidden" name="dropdatabase" value="', htmlspecialchars($_REQUEST['dropdatabase']), "\" />\n";
            } // END if multi drop

            echo "<input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" />\n";
            echo "</form>\n";
        } // END confirm
        else {
            //If multi drop
            if (is_array($_REQUEST['dropdatabase'])) {
                $msg = '';
                foreach ($_REQUEST['dropdatabase'] as $d) {
                    $status = $data->dropDatabase($d);
                    if (0 == $status) {
                        $msg .= sprintf('%s: %s<br />', htmlentities($d, ENT_QUOTES, 'UTF-8'), $lang['strdatabasedropped']);
                    } else {
                        $this->doDefault(sprintf('%s%s: %s<br />', $msg, htmlentities($d, ENT_QUOTES, 'UTF-8'), $lang['strdatabasedroppedbad']));

                        return;
                    }
                } // Everything went fine, back to Default page...
                $this->setReloadDropDatabase(true);
                $this->doDefault($msg);
            } else {
                $status = $data->dropDatabase($_POST['dropdatabase']);
                if (0 == $status) {
                    $this->setReloadDropDatabase(true);
                    $this->doDefault($lang['strdatabasedropped']);
                } else {
                    $this->doDefault($lang['strdatabasedroppedbad']);
                }
            }
        } //END DROP
    }

    // END FUNCTION

    /**
     * Displays a screen where they can enter a new database.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('server');
        $this->printTitle($lang['strcreatedatabase'], 'pg.database.create');
        $this->printMsg($msg);

        if (!isset($_POST['formName'])) {
            $_POST['formName'] = '';
        }

        // Default encoding is that in language file
        if (!isset($_POST['formEncoding'])) {
            $_POST['formEncoding'] = '';
        }
        if (!isset($_POST['formTemplate'])) {
            $_POST['formTemplate'] = 'template1';
        }

        if (!isset($_POST['formSpc'])) {
            $_POST['formSpc'] = '';
        }

        if (!isset($_POST['formComment'])) {
            $_POST['formComment'] = '';
        }

        // Fetch a list of databases in the cluster
        $templatedbs = $data->getDatabases(false);

        // Fetch all tablespaces from the database
        if ($data->hasTablespaces()) {
            $tablespaces = $data->getTablespaces();
        }

        echo '<form action="' . \SUBFOLDER . "/src/views/alldb.php\" method=\"post\">\n";
        echo "<table>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strname']}</th>\n";
        echo "\t\t<td class=\"data1\"><input name=\"formName\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_POST['formName']), "\" /></td>\n\t</tr>\n";

        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strtemplatedb']}</th>\n";
        echo "\t\t<td class=\"data1\">\n";
        echo "\t\t\t<select name=\"formTemplate\">\n";
        // Always offer template0 and template1
        echo "\t\t\t\t<option value=\"template0\"",
        ('template0' == $_POST['formTemplate']) ? ' selected="selected"' : '', ">template0</option>\n";
        echo "\t\t\t\t<option value=\"template1\"",
        ('template1' == $_POST['formTemplate']) ? ' selected="selected"' : '', ">template1</option>\n";
        while (!$templatedbs->EOF) {
            $dbname = htmlspecialchars($templatedbs->fields['datname']);
            if ('template1' != $dbname) {
                // filter out for $this->conf[show_system] users so we dont get duplicates
                echo "\t\t\t\t<option value=\"{$dbname}\"",
                ($dbname == $_POST['formTemplate']) ? ' selected="selected"' : '', ">{$dbname}</option>\n";
            }
            $templatedbs->moveNext();
        }
        echo "\t\t\t</select>\n";
        echo "\t\t</td>\n\t</tr>\n";

        // ENCODING
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$lang['strencoding']}</th>\n";
        echo "\t\t<td class=\"data1\">\n";
        echo "\t\t\t<select name=\"formEncoding\">\n";
        echo "\t\t\t\t<option value=\"\"></option>\n";

        foreach ($data->codemap as $key) {
            echo "\t\t\t\t<option value=\"", htmlspecialchars($key), '"',
            ($key == $_POST['formEncoding']) ? ' selected="selected"' : '', '>',
            $this->misc->printVal($key), "</option>\n";
        }
        echo "\t\t\t</select>\n";
        echo "\t\t</td>\n\t</tr>\n";

        if ($data->hasDatabaseCollation()) {
            if (!isset($_POST['formCollate'])) {
                $_POST['formCollate'] = '';
            }

            if (!isset($_POST['formCType'])) {
                $_POST['formCType'] = '';
            }

            // LC_COLLATE
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strcollation']}</th>\n";
            echo "\t\t<td class=\"data1\">\n";
            echo "\t\t\t<input name=\"formCollate\" value=\"", htmlspecialchars($_POST['formCollate']), "\" />\n";
            echo "\t\t</td>\n\t</tr>\n";

            // LC_CTYPE
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strctype']}</th>\n";
            echo "\t\t<td class=\"data1\">\n";
            echo "\t\t\t<input name=\"formCType\" value=\"", htmlspecialchars($_POST['formCType']), "\" />\n";
            echo "\t\t</td>\n\t</tr>\n";
        }

        // Tablespace (if there are any)
        if ($data->hasTablespaces() && $tablespaces->recordCount() > 0) {
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strtablespace']}</th>\n";
            echo "\t\t<td class=\"data1\">\n\t\t\t<select name=\"formSpc\">\n";
            // Always offer the default (empty) option
            echo "\t\t\t\t<option value=\"\"",
            ('' == $_POST['formSpc']) ? ' selected="selected"' : '', "></option>\n";
            // Display all other tablespaces
            while (!$tablespaces->EOF) {
                $spcname = htmlspecialchars($tablespaces->fields['spcname']);
                echo "\t\t\t\t<option value=\"{$spcname}\"",
                ($spcname == $_POST['formSpc']) ? ' selected="selected"' : '', ">{$spcname}</option>\n";
                $tablespaces->moveNext();
            }
            echo "\t\t\t</select>\n\t\t</td>\n\t</tr>\n";
        }

        // Comments (if available)
        if ($data->hasSharedComments()) {
            echo "\t<tr>\n\t\t<th class=\"data left\">{$lang['strcomment']}</th>\n";
            echo "\t\t<td><textarea name=\"formComment\" rows=\"3\" cols=\"32\">",
            htmlspecialchars($_POST['formComment']), "</textarea></td>\n\t</tr>\n";
        }

        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Actually creates the new view in the database.
     */
    public function doSaveCreate()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        // Default tablespace to null if it isn't set
        if (!isset($_POST['formSpc'])) {
            $_POST['formSpc'] = null;
        }

        // Default comment to blank if it isn't set
        if (!isset($_POST['formComment'])) {
            $_POST['formComment'] = null;
        }

        // Default collate to blank if it isn't set
        if (!isset($_POST['formCollate'])) {
            $_POST['formCollate'] = null;
        }

        // Default ctype to blank if it isn't set
        if (!isset($_POST['formCType'])) {
            $_POST['formCType'] = null;
        }

        // Check that they've given a name and a definition
        if ('' == $_POST['formName']) {
            $this->doCreate($lang['strdatabaseneedsname']);
        } else {
            $status = $data->createDatabase(
                $_POST['formName'],
                $_POST['formEncoding'],
                $_POST['formSpc'],
                $_POST['formComment'],
                $_POST['formTemplate'],
                $_POST['formCollate'],
                $_POST['formCType']
            );
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($lang['strdatabasecreated']);
            } else {
                $this->doCreate($lang['strdatabasecreatedbad']);
            }
        }
    }

    /**
     * Displays options for cluster download.
     *
     * @param mixed $msg
     */
    public function doExport($msg = '')
    {
        $lang = $this->lang;

        $this->printTrail('server');
        $this->printTabs('server', 'export');
        $this->printMsg($msg);

        echo '<form action="' . \SUBFOLDER . "/src/views/dbexport.php\" method=\"post\">\n";
        echo "<table>\n";
        echo "<tr><th class=\"data\">{$lang['strformat']}</th><th class=\"data\">{$lang['stroptions']}</th></tr>\n";
        // Data only
        echo '<tr><th class="data left" rowspan="2">';
        echo "<input type=\"radio\" id=\"what1\" name=\"what\" value=\"dataonly\" checked=\"checked\" /><label for=\"what1\">{$lang['strdataonly']}</label></th>\n";
        echo "<td>{$lang['strformat']}\n";
        echo "<select name=\"d_format\">\n";
        echo "<option value=\"copy\">COPY</option>\n";
        echo "<option value=\"sql\">SQL</option>\n";
        echo "</select>\n</td>\n</tr>\n";
        echo "<tr><td><input type=\"checkbox\" id=\"d_oids\" name=\"d_oids\" /><label for=\"d_oids\">{$lang['stroids']}</label></td>\n</tr>\n";
        // Structure only
        echo "<tr><th class=\"data left\"><input type=\"radio\" id=\"what2\" name=\"what\" value=\"structureonly\" /><label for=\"what2\">{$lang['strstructureonly']}</label></th>\n";
        echo "<td><input type=\"checkbox\" id=\"s_clean\" name=\"s_clean\" /><label for=\"s_clean\">{$lang['strdrop']}</label></td>\n</tr>\n";
        // Structure and data
        echo '<tr><th class="data left" rowspan="3">';
        echo "<input type=\"radio\" id=\"what3\" name=\"what\" value=\"structureanddata\" /><label for=\"what3\">{$lang['strstructureanddata']}</label></th>\n";
        echo "<td>{$lang['strformat']}\n";
        echo "<select name=\"sd_format\">\n";
        echo "<option value=\"copy\">COPY</option>\n";
        echo "<option value=\"sql\">SQL</option>\n";
        echo "</select>\n</td>\n</tr>\n";
        echo "<tr><td><input type=\"checkbox\" id=\"sd_clean\" name=\"sd_clean\" /><label for=\"sd_clean\">{$lang['strdrop']}</label></td>\n</tr>\n";
        echo "<tr><td><input type=\"checkbox\" id=\"sd_oids\" name=\"sd_oids\" /><label for=\"sd_oids\">{$lang['stroids']}</label></td>\n</tr>\n";
        echo "</table>\n";

        echo "<h3>{$lang['stroptions']}</h3>\n";
        echo "<p><input type=\"radio\" id=\"output1\" name=\"output\" value=\"show\" checked=\"checked\" /><label for=\"output1\">{$lang['strshow']}</label>\n";
        echo "<br/><input type=\"radio\" id=\"output2\" name=\"output\" value=\"download\" /><label for=\"output2\">{$lang['strdownload']}</label></p>\n";

        echo "<p><input type=\"hidden\" name=\"action\" value=\"export\" />\n";
        echo "<input type=\"hidden\" name=\"subject\" value=\"server\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$lang['strexport']}\" /></p>\n";
        echo "</form>\n";
    }
}
