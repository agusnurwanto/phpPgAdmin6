<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class AcinsertController extends BaseController
{
    public $controller_name = 'AcinsertController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang   = $this->lang;
        $data   = $this->misc->getDatabaseAccessor();
        $action = $this->action;

        if (isset($_POST['offset'])) {
            $offset = " OFFSET {$_POST['offset']}";
        } else {
            $_POST['offset'] = 0;
            $offset          = ' OFFSET 0';
        }

        $keynames = [];
        foreach ($_POST['fkeynames'] as $k => $v) {
            $fkeynames[$k] = html_entity_decode($v, ENT_QUOTES);
        }

        $keyspos = array_combine($fkeynames, $_POST['keys']);

        $f_schema = html_entity_decode($_POST['f_schema'], ENT_QUOTES);
        $data->fieldClean($f_schema);
        $f_table = html_entity_decode($_POST['f_table'], ENT_QUOTES);
        $data->fieldClean($f_table);
        $f_attname = $fkeynames[$_POST['fattpos'][0]];
        $data->fieldClean($f_attname);

        $q = "SELECT *
		FROM \"{$f_schema}\".\"{$f_table}\"
		WHERE \"{$f_attname}\"::text LIKE '{$_POST['fvalue']}%'
		ORDER BY \"{$f_attname}\" LIMIT 12 {$offset};";

        $res = $data->selectSet($q);

        if (!$res->EOF) {
            echo '<table class="ac_values">';
            echo '<tr>';
            foreach (array_keys($res->fields) as $h) {
                echo '<th>';

                if (in_array($h, $fkeynames, true)) {
                    echo '<img src="' . $this->misc->icon('ForeignKey') . '" alt="[referenced key]" />';
                }

                echo htmlentities($h, ENT_QUOTES, 'UTF-8'), '</th>';
            }
            echo "</tr>\n";
            $i = 0;
            while ((!$res->EOF) && ($i < 11)) {
                $j = 0;
                echo '<tr class="acline">';
                foreach ($res->fields as $n => $v) {
                    $finfo = $res->fetchField($j++);
                    if (in_array($n, $fkeynames, true)) {
                        echo "<td><a href=\"javascript:void(0)\" class=\"fkval\" name=\"{$keyspos[$n]}\">",
                        $this->misc->printVal($v, $finfo->type, ['clip' => 'collapsed']),
                            '</a></td>';
                    } else {
                        echo '<td><a href="javascript:void(0)">',
                        $this->misc->printVal($v, $finfo->type, ['clip' => 'collapsed']),
                            '</a></td>';
                    }
                }
                echo "</tr>\n";
                ++$i;
                $res->moveNext();
            }
            echo "</table>\n";

            $page_tests = '';

            $js = "<script type=\"text/javascript\">\n";

            if ($_POST['offset']) {
                echo '<a href="javascript:void(0)" id="fkprev">&lt;&lt; Prev</a>';
                $js .= "fkl_hasprev=true;\n";
            } else {
                $js .= "fkl_hasprev=false;\n";
            }

            if (12 == $res->recordCount()) {
                $js .= "fkl_hasnext=true;\n";
                echo '&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" id="fknext">Next &gt;&gt;</a>';
            } else {
                $js .= "fkl_hasnext=false;\n";
            }

            echo $js . '</script>';
        } else {
            printf("<p>{$lang['strnofkref']}</p>", "\"{$_POST['f_schema']}\".\"{$_POST['f_table']}\".\"{$fkeynames[$_POST['fattpos']]}\"");

            if ($_POST['offset']) {
                echo '<a href="javascript:void(0)" class="fkprev">Prev &lt;&lt;</a>';
            }
        }
    }
}
