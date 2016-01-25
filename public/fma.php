<?php
    /**
     * Thin is a swift Framework for PHP 5.4+
     *
     * @package    Thin
     * @version    1.0
     * @author     Gerald Plusquellec
     * @license    BSD License
     * @copyright  1996 - 2016 Gerald Plusquellec
     * @link       http://github.com/schpill/thin
     */

    namespace Thin;

    $cli = true;

    include __DIR__ . DIRECTORY_SEPARATOR . 'index.php';

    $request = request();

    class Fma
    {
        private $tables = [];
        private $action;

        public function content($action, $tables)
        {
            $this->tables = $tables;

            if (!method_exists($this, $action)) {
                return $this->error('Unknown action.');
            }

            $this->action = $action;

            return $this->$action();
        }

        private function logout()
        {
            session('fma')->setAuth(null);
            Router::redirect('./fma.php');
        }

        private function home()
        {
            return '<a href="./fma.php?action=addTable"><i class="fa fa-plus"></i> Add a table</a>';
        }

        private function headerTable($id)
        {
            $html = '<h3 class="titleTable">' . System::Table()->find($id)->getName() . ' (' . System::Table()->find($id)->getNs() . ')</h3>';
            $first = $this->action != 'displayData'
            ? '<a href="./fma.php?action=displayData&amp;id=' . $id . '"><i class="fa fa-folder-open"></i> Display data</a>'
            : '<a href="./fma.php?action=table&amp;id=' . $id . '"><i class="fa fa-tasks"></i> Display structures</a>';
            $html .= '<p>
                ' . $first . ' |
                <a href="./fma.php?action=addRecord&amp;id=' . $id . '"><i class="fa fa-plus-square"></i> Add record</a> |
                <a href="./fma.php?action=import&amp;table=' . $id . '"><i class="fa fa-mail-forward"></i> Import data</a> |
                <a href="./fma.php?action=emptyTable&amp;id=' . $id . '"><i class="fa fa-ban"></i> Empty the table</a> |
                <a href="./fma.php?action=removeTable&amp;id=' . $id . '"><i class="fa fa-trash-o"></i> Delete the table</a>
            </p>';

            return $html;
        }

        private function deleteRecordConfirm()
        {
            $id     = isAke($_REQUEST, 'id', null);
            $table  = isAke($_REQUEST, 'table', null);
            $token  = isAke($_REQUEST, 'token', null);

            if (strlen($id) && strlen($table) && strlen($token)) {
                if ($token == sha1('deleteRecord' . $id . $table . date('dmY'))) {
                    $db     = System::Table()->instanciate(
                        System::Table()->find($table)->getNs(),
                        System::Table()->find($table)->getName()
                    );

                    $obj    = $db->find($id);

                    if (!empty($obj)) {
                        $obj->delete();
                        header('Location: ./fma.php?action=displayData&id=' . $table .'');
                    }
                } else {
                    return $this->error('Wrong token.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function deleteStructureConfirm()
        {
            $id     = isAke($_REQUEST, 'id', null);
            $token  = isAke($_REQUEST, 'token', null);

            if (strlen($id) && strlen($token)) {
                if ($token == sha1('deleteStructure' . $id . date('dmY'))) {
                    $dbs = System::Structure();
                    $obj = $dbs->find($id);

                    if (!empty($obj)) {
                        $db = System::Table()->instanciate(
                            System::Table()->find($obj->getTable())->getNs(),
                            System::Table()->find($obj->getTable())->getName()
                        );

                        $data = $db->get();

                        foreach ($data as $row) {
                            $f = $obj->field();
                            $f = empty($f) ? request() : $f;
                            unset($row[$f->getName()]);
                            $db->model($row)->save();
                        }

                        $obj->delete();

                        header('Location: ./fma.php?action=table&id=' . $obj->getTable() .'');
                    }
                } else {
                    return $this->error('Wrong token.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function deleteStructure()
        {
            $id     = isAke($_REQUEST, 'id', null);

            if (strlen($id)) {
                $dbs = System::Structure();
                $obj = $dbs->find($id);

                if (!empty($obj)) {
                    $key = sha1('deleteStructure' . $id . date('dmY'));

                    return '<p class="alert">
                    Click <a href="./fma.php?action=deleteStructureConfirm&amp;id=' . $id .'&amp;token=' . $key .'">here</a> to confirm this deletion or <a href="./fma.php?action=table&amp;id=' . $obj->getTable() .'">here</a> to go back.
                    </p>';
                } else {
                    return $this->error('Wrong request.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function deleteRecord()
        {
            $id     = isAke($_REQUEST, 'id', null);
            $table  = isAke($_REQUEST, 'table', null);

            if (strlen($id) && strlen($table)) {
                $key = sha1('deleteRecord' . $id . $table . date('dmY'));

                return '<p class="alert">
                Click <a href="./fma.php?action=deleteRecordConfirm&amp;table=' . $table .'&amp;id=' . $id .'&amp;token=' . $key .'">here</a> to confirm this deletion or <a href="./fma.php?action=displayData&amp;id=' . $table .'">here</a> to go back.
                </p>';
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function emptyTable()
        {
            $id     = isAke($_REQUEST, 'id', null);

            if (strlen($id)) {
                $table  = System::Table()->find($id);

                if (!empty($table)) {
                    $key = sha1('emptyTable' . $id . date('dmY'));

                    return '<p class="alert">
                    Click <a href="./fma.php?action=emptyTableConfirm&amp;id=' . $id .'&amp;token=' . $key .'">here</a> to empty the table &laquo; ' . System::Table()->find(request()->getId())->getName() . ' &raquo, or <a href="./fma.php?action=displayData&amp;id=' . $id .'">here</a> to go back.
                    </p>';
                } else {
                    return $this->error('Wrong request.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function removeTable()
        {
            $id     = isAke($_REQUEST, 'id', null);

            if (strlen($id)) {
                $table  = System::Table()->find($id);

                if (!empty($table)) {
                    $key = sha1('removeTable' . $id . date('dmY'));

                    return '<p class="alert">
                    Click <a href="./fma.php?action=removeTableConfirm&amp;id=' . $id .'&amp;token=' . $key .'">here</a> to drop the table &laquo; ' . System::Table()->find(request()->getId())->getName() . ' &raquo, or <a href="./fma.php?action=displayData&amp;id=' . $id .'">here</a> to go back.
                    </p>';
                } else {
                    return $this->error('Wrong request.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function addTable()
        {
            if (!empty($_POST)) {
                $name   = request()->getName();
                $ns     = request()->getNs();
                $db     = System::Table()->instanciate($ns, $name);
                $create = $db->createTable();

                if (false === $create) {
                    return $this->error("The table $name exists.");
                } else {
                    Router::redirect('./fma.php');
                }
            }

            $html = '<h3>Add a new table</h3>';
            $html .= '<p>
            <form action="./fma.php?action=addTable" method="post" id="addTable">
            <input type="text" required placeholder="Name of the database" name="ns" /><br>
            <input type="text" required placeholder="Name of the table" name="name" />
            <p><button onclick="$(\'#addTable\').submit();">OK</button></p>
            </form>
            </p>';

            return $html;
        }

        private function removeTableConfirm()
        {
            $id     = isAke($_REQUEST, 'id', null);
            $token  = isAke($_REQUEST, 'token', null);

            if (strlen($id) && strlen($token)) {
                $table  = System::Table()->find($id);

                if (!empty($table)) {
                    if ($token == sha1('removeTable' . $id . date('dmY'))) {
                        $db     = System::Table()->instanciate($table->getNs(), $table->getName());
                        $db->dropTable();
                        $dbs    = System::Structure();
                        $structures = $dbs->where(['table', '=', $id])->sortBy('field')->models();

                        foreach ($structures as $structure) {
                            $structure->delete();
                        }

                        $table->delete();

                        header(
                            'Location: ./fma.php'
                        );
                    } else {
                        return $this->error('Wrong request.');
                    }
                } else {
                    return $this->error('Wrong request.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function emptyTableConfirm()
        {
            $id     = isAke($_REQUEST, 'id', null);
            $token  = isAke($_REQUEST, 'token', null);

            if (strlen($id) && strlen($token)) {
                $table  = System::Table()->find($id);

                if (!empty($table)) {
                    if ($token == sha1('emptyTable' . $id . date('dmY'))) {
                        $db     = System::Table()->instanciate($table->getNs(), $table->getName());
                        $db->emptyTable();

                        header(
                            'Location: ./fma.php?action=displayData&id=' . $id
                        );
                    } else {
                        return $this->error('Wrong request.');
                    }
                } else {
                    return $this->error('Wrong request.');
                }
            } else {
                return $this->error('Wrong request.');
            }
        }

        private function displayData()
        {
            $t = System::Table()->find(request()->getId());

            if (empty($t)) {
                return $this->error("Please choose a valid table.");
            }

            $db     = System::Table()->instanciate($t->getNs(), $t->getName());
            $html   = $this->headerTable(request()->getId());
            $dbs    = System::Structure();

            $structures = $dbs->where(['table', '=', request()->getId()])->sortBy('field')->get();

            $fields         = array('id');
            $labels         = array('id');
            $types          = array('int');
            $defaults       = array('null');
            $typeFields     = array('id' => 'int');
            $defaultFields  = array('id' => 'null');

            foreach ($structures as $structure) {
                $s                      = $dbs->model($structure);
                $label                  = $s->getLabel();
                $field                  = $s->field()->getName();
                $fields[]               = $field;
                $labels[]               = !strlen($label) ? $field : $label;
                $types[]                = $s->getType();
                $defaults[]             = $s->getDefault();
                $typeFields[$field]     = $s->getType();
                $defaultFields[$field]  = $s->getDefault();
            }

            $page       = isAke($_REQUEST, 'page', 1);
            $limit      = isAke($_REQUEST, 'limit', 25);
            $order      = isAke($_REQUEST, 'order', 'id');
            $direction  = isAke($_REQUEST, 'direction', 'ASC');
            $query      = isAke($_REQUEST, 'query', 'id > 0');
            $isSearch   = false;

            if (strstr($query, '%%')) {
                $isSearch   = true;
                list($fieldsSeach, $operators, $values) = explode('%%', $query, 3);

                if (strstr($fieldsSeach, '##')) {
                    $searchFields       = explode('##', $fieldsSeach);
                    $searchOperators    = explode('##', $operators);
                    $searchValues       = explode('##', $values);
                    $searchQuery = '';

                    foreach ($searchFields as $k => $searchField) {
                        $searchOperator = $searchOperators[$k];
                        $searchValue    = $searchValues[$k];

                        if (strlen($searchQuery)) {
                            $searchQuery .= ' && ';
                        }

                        $typeSearch     = $typeFields[$searchField];

                        if ($typeSearch == 'set') {
                            $defaultSearch = $defaultFields[$searchField];
                            $tab = explode(',', $defaultSearch);

                            foreach ($tab as $k => $rowSet) {
                                if(strstr($rowSet, '%%')) {
                                    list($k, $v) = explode('%%', $rowSet, 2);
                                } else {
                                    $v = $rowSet;
                                }

                                if (Inflector::lower($v) == Inflector::lower($searchValue)) {
                                    $searchValue = $k;
                                    break;
                                }
                            }
                        }

                        $searchQuery    .= "$searchField $searchOperator $searchValue";
                    }
                } else {
                    $typeSearch  = $typeFields[$fieldsSeach];

                    if ($typeSearch == 'set') {
                        $defaultSearch = $defaultFields[$fieldsSeach];
                        $tab = explode(',', $defaultSearch);

                        foreach ($tab as $k => $rowSet) {
                            if(strstr($rowSet, '%%')) {
                                list($k, $v) = explode('%%', $rowSet, 2);
                            } else {
                                $v = $rowSet;
                            }

                            if (Inflector::lower($v) == Inflector::lower($values)) {
                                $values = $k;
                                break;
                            }
                        }
                    }
                    $searchQuery = "$fieldsSeach $operators $values";
                }
            } else {
                $searchQuery = $query;
            }

            $export     = isAke($_REQUEST, 'export', null);

            $offset     = ($limit * $page) - $limit;
            $firstRow   = $offset + 1;

            if (1 == $export) {
                $db->query($searchQuery)->order($order, $direction)->export();
            }

            $results    = $db->query($searchQuery)->order($order, $direction)->exec();
            $total      = count($results);
            $lastRow    = $offset + $limit;

            if ($total < $lastRow) {
                $lastRow = $total;
            }

            $last       = ceil($total / $limit);
            $paginator  = new Paginator($results, $page, $total, $limit, $last);

            $res        = $paginator->getItemsByPage();
            $pagination = $paginator->links();

            $html .= '<form id="listForm" action="./fma.php?action=displayData&id=' . request()->getId() . '" method="post">';
            $html .= '<input type="hidden" name="page" id="page" value="' . $page . '" />';
            $html .= '<input type="hidden" name="limit" id="limit" value="' . $limit . '" />';
            $html .= '<input type="hidden" name="order" id="order" value="' . $order . '" />';
            $html .= '<input type="hidden" name="direction" id="direction" value="' . $direction . '" />';
            $html .= '<input type="hidden" name="query" id="query" value="' . $query . '" />';
            $html .= '<input type="hidden" name="export" id="export" value="0" />';
            $html .= '</form>';

            if (empty($res) && false === $isSearch) {
                $html .= '<div class="alert alert-info">No data to display.</div>';
                return $html;
            } else if (empty($res) && true === $isSearch) {
                $html .= '<div class="alert alert-danger">
                    The search has no result.
                    <p><span onclick="selfPage();" class="link"><i class="fa fa-trash-o"></i> Reset this search</span></p>
                </div>';

                return $html;
            }

            if (false === $isSearch) {
                $html .= '<h3 class="link" onclick="showHide(\'searchContainer\');"><i class="fa fa-search"></i> <u>Search</u><h3>';
                $html .= '<div style="display: none;" id="searchContainer">';
                $html .= '<div id="search">';
                $html .= '<div id="rowSsearch">';
                $select = '<select class="fields" id="fields[]">';

                foreach ($fields as $k => $field) {
                    $label = $labels[$k];
                    $select .= '<option value="' . $field . '">' . $label . '</option>';
                }

                $select .= '</select>';

                $operator = '<select class="operators" id="operators[]">';
                $operator .= '<option value="=">=</option>';
                $operator .= '<option value="!=">!=</option>';
                $operator .= '<option value=">">&gt;</option>';
                $operator .= '<option value=">=">&gt;=</option>';
                $operator .= '<option value="<">&lt;</option>';
                $operator .= '<option value="<=">&lt;=</option>';
                $operator .= '<option value="LIKE">LIKE</option>';
                $operator .= '<option value="NOT LIKE">NOT LIKE</option>';
                $operator .= '<option value="LIKE START">STARTS WITH</option>';
                $operator .= '<option value="LIKE END">ENDS WITH</option>';
                $operator .= '<option value="IN">IN</option>';
                $operator .= '<option value="NOT IN">NOT IN</option>';
                $operator .= '</select>';
                $html .= $select
                . '&nbsp;'
                . $operator
                . '&nbsp;
                <input class="values" id="values[]" />
                &nbsp;
                <i title="Add a criteria" onclick="copyRow();" class="fa fa-plus link"></i>
                </div>
                </div>
                <button onclick="search();">GO</button>
                </div>';
            } else {
                $html .= '<p><span onclick="selfPage();" class="link"><i class="fa fa-trash-o"></i> Delete this search</span></p>';
            }

            $html .= '<p class="infos">' . $firstRow . ' to ' . $lastRow . ' on ' . $total . ' records</p>';
            $html .= $pagination;
            $html .= '<table class="table">';

            foreach ($fields as $k => $field) {
                $label = $labels[$k];
                $arrow = $order == $field ? $direction == 'ASC' ? '&uarr;' : '&darr;' : '';
                $html .= '<th><span class="link" onclick="paginationOrder(\'' . $field . '\'); return false;">' . $label . ' ' . $arrow . '</span></th>';
            }

            $html .= '<th>&nbsp;</th></tr>';
            $tabsComp = [];

            foreach ($res as $row) {
                $html .= '<tr>';
                $row = $db->model($row);

                foreach ($fields as $k => $field) {
                    $value = $row->$field;
                    $type = $types[$k];
                    $def = $defaults[$k];

                    if (is_string($value)) {
                        $longValue = $value;
                        $value = $this->truncate($value);
                    } elseif (empty($value)) {
                        $value = '<span class="muted">empty</span>';
                    } elseif (is_object($value)) {
                        $value = '<i>Object</i>';
                    }

                    if (strstr($type, 'fk_')) {
                        $fkTable = str_replace('fk_', '', $type);
                        $tableFk = $this->tableByValue($fkTable);
                        $fk = container()->fbm($fkTable)->find($value);

                        if (!empty($fk)) {
                            $value = '<a class="fk" href="./fma.php?action=editRecord&amp;id=' . $fk->getId() . '&amp;table=' . $tableFk->getId() . '">' . $this->truncate($fk->string()) . '</a>';
                        }
                    }

                    if ($type == 'timestamp') {
                        $value = date('d/m/Y H:i:s', round($value / 1000));
                    }

                    if ($type == 'file') {
                        $tab        = explode('/', $longValue);
                        $name       = Arrays::last($tab);
                        $isImage    = false;
                        if (strstr($name, '.')) {
                            $isImage = $this->isImage($name);
                        }
                        if (true === $isImage) {
                            $file = '<img rel="tooltip" title="download" style="height: 100px; width: 100px;" src="' . $longValue . '" alt="' . $field . '" />';
                        } else {
                            $file = $name;
                        }
                        $value = '<p><a href="' . $longValue . '" target="_blank">' . $file . '</a></p>';
                    } elseif ('set' == $type) {
                        $tabComp = isAke($tabsComp, $field);
                        if (empty($tabComp)) {
                            $tab = explode(',', $def);
                            foreach ($tab as $k => $rowSet) {
                                if(strstr($rowSet, '%%')) {
                                    list($k, $v) = explode('%%', $rowSet, 2);
                                } else {
                                    $v = $rowSet;
                                }
                                $tabComp[$k] = $v;
                            }
                            $tabsComp[$field] = $tabComp;
                        }
                        $value = $tabComp[$value];
                    }
                    $html .= '<td>' . $value . '</td>';
                }

                $html .= '<td>
                    <a href="./fma.php?action=editRecord&amp;id=' . $row->getId() . '&amp;table=' . request()->getId() . '"><i rel="tooltip" title="Edit record" class="fa fa-edit"></i></a> |
                    <a href="./fma.php?action=duplicateRecord&amp;id=' . $row->getId() . '&amp;table=' . request()->getId() . '"><i rel="tooltip" title="Duplicate record" class="fa fa-copy"></i></a> |
                    <a href="./fma.php?action=deleteRecord&amp;id=' . $row->getId() . '&amp;table=' . request()->getId() . '"><i rel="tooltip" title="Delete record" class="fa fa-trash-o"></i></a>
                </td></tr>';
            }

            $html .= '</table><p class="link yellow" onclick="makeExport();" ><i class="fa fa-file-excel-o fa-2x"></i> Export</p>';
            $html .= $pagination;

            return $html;
        }

        private function table()
        {
            $html = $this->headerTable(request()->getId());
            $html .= '<table class="table">';
            $html .= '<tr>';
            $html .= '<th>Field</th>';
            $html .= '<th>Type</th>';
            $html .= '<th>Default</th>';
            $html .= '<th>Index</th>';
            $html .= '<th>Null</th>';
            $html .= '<th>&nbsp;</th>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td>id</td>';
            $html .= '<td>int(11)</td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '<td class="true"><i class="fa fa-check"></td>';
            $html .= '<td class="false"><i class="fa fa-ban"></td>';
            $html .= '<td>&nbsp;</td>';
            $html .= '</tr>';

            $dbs = System::Structure();

            $structures = $dbs->where(['table', '=', request()->getId()])->sortBy('field')->get();

            foreach ($structures as $structure) {
                $s = $dbs->model($structure);
                $f = $s->field();
                $f = empty($f) ? request() : $f;
                $html .= '<tr>';
                $html .= '<td>' . $f->getName() . '</td>';
                $html .= '<td>' . $s->getType() . '(' . $s->getLength() . ')</td>';
                $html .= '<td>' . $this->truncate($s->getDefault()) . '</td>';

                if (true === $s->getIsIndex()) {
                    $html .= '<td class="true"><i class="fa fa-check"></td>';
                } else {
                    $html .= '<td class="false"><i class="fa fa-ban"></td>';
                }

                if (true === $s->getCanBeNull()) {
                    $html .= '<td class="true"><i class="fa fa-check"></td>';
                } else {
                    $html .= '<td class="false"><i class="fa fa-ban"></td>';
                }

                $html .= '<td>
                    <a href="./fma.php?action=editStructure&amp;id=' . $s->getId() . '"><i rel="tooltip" title="Edit structure" class="fa fa-edit"></i></a> |
                    <a href="./fma.php?action=deleteStructure&amp;id=' . $s->getId() . '"><i rel="tooltip" title="Delete structure" class="fa fa-trash-o"></i></a>
                </td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            $html .= '<p><a href="./fma.php?action=addStructure&amp;table=' . request()->getId() . '"><i class="fa fa-plus"></i> Add a field</a></p>';

            return $html;
        }

        private function checkBool($field)
        {
            $bool = request()->$field;
            $_POST[$field] = empty($bool) ? 'false' : true;

            return $this;
        }

        private function isImage($file)
        {
            $exts = array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'svg', 'tiff');

            if (strstr($file, '.')) {
                $tab = explode('.', $file);
                $ext = Inflector::lower(Arrays::last($tab));

                return Arrays::in($ext, $exts);
            }

            return false;
        }

        private function inputForm($type, $field, $length, $required, $value, $structure)
        {
            $type = strstr($type, 'text') ? 'text' : $type;
            $require = $required ? 'required' : '';

            if (strstr($type, 'blob') || strstr($type, 'binary')) {
                $type = 'object';
            }

            if (strstr($type, 'fk_')) {
                $table = repl('fk_', '', $type);
                $db = container()->fbm($table);
                $datas = $db->fetch()->exec(true);
                $tableFk = $this->tableByValue($table);
                $select = '<select class="input-medium" ' . $require . ' id="' . $field . '" name="' . $field . '">';
                if (count($datas)) {
                    foreach ($datas as $row) {
                        $selected = $value == $row->id ? 'selected' : '';
                        $select .= '<option ' . $selected . ' value="' . $row->id . '">' . $row->string() . '</option>';
                    }
                }
                $select .= '</select>
                <a target="_plus" href="./fma.php?action=addRecord&amp;id=' . $tableFk->getId() . '">
                <i rel="tooltip" title="Add ' . $table . '" class="fa fa-plus"></i>
                </a>';
                $type = 'fk';
            }
            switch ($type) {
                case 'object':
                    return '<textarea ' . $require . ' maxlength="' . $length . '" class="input-medium" name="' . $field . '" id="' . $field . '">' . json_encode($value) . '</textarea>';
                    break;
                case 'set':
                    $sets = $structure->getDefault();
                    if (strlen($sets)) {
                        $tab = explode(',', $sets);
                        $return = '<select class="input-medium" ' . $require . ' id="' . $field . '" name="' . $field . '">';
                        foreach ($tab as $k => $row) {
                            if(strstr($row, '%%')) {
                                list($k, $v) = explode('%%', $row, 2);
                            } else {
                                $v = $row;
                            }
                            $selected = $value == $k ? 'selected' : '';
                            $return .= '<option ' . $selected . ' value="' . $k . '">' . $v . '</option>';
                        }
                        $return .= '</select>';
                        return $return;
                    } else {
                        return '<input ' . $require . ' maxlength="' . $length . '" class="input-medium" name="' . $field . '" id="' . $field . '" value="' . $value . '" />';
                    }
                case 'file':
                    $input = '<input ' . $require . ' maxlength="' . $length . '" class="input-medium" name="' . $field . '" id="' . $field . '" type="file" />';
                    if (!is_null($value)) {
                        $tab        = explode('/', $value);
                        $name       = Arrays::last($tab);
                        $isImage    = false;
                        if (strstr($name, '.')) {
                            $isImage = $this->isImage($name);
                        }
                        if (true === $isImage) {
                            $file = '<img rel="tooltip" title="download" style="height: 150px; width: 150px;" src="' . $value . '" alt="' . $field . '" />';
                        } else {
                            $file = $name;
                        }
                        $input .= '<p><a href="' . $value . '" target="_blank">' . $file . '</a></p>';
                        return $input;
                    }
                case 'fk':
                    return $select;
                case 'text':
                    return '<textarea ' . $require . ' maxlength="' . $length . '" class="input-medium" name="' . $field . '" id="' . $field . '">' . $value . '</textarea>';
                case 'email':
                    return '<input type="email" ' . $require . ' maxlength="' . $length . '" class="input-medium" name="' . $field . '" id="' . $field . '" value="' . $value . '" />';
                default:
                    return '<input ' . $require . ' maxlength="' . $length . '" class="input-medium" name="' . $field . '" id="' . $field . '" value="' . $value . '" />';
            }
        }

        private function import()
        {
            $dbTable    = System::Table();
            $dbs        = System::Structure();
            $table      = $dbTable->find(request()->getTable());

            if (empty($table)) {
                return $this->error('Wrong request.');
            }

            if (!empty($_POST)) {
                $up = $this->uploadSession('csv');

                if (!$up) {
                    return $this->error("An error occured. Please try again.");
                } else {
                    $structures = $dbs->where('table = ' . $table->getId())->order('field')->exec(true);

                    return $this->map($structures, $table, request()->getSeparator());
                }
            }

            $html = '<h3>Import data in &laquo; ' . $table->getName() . ' &raquo;</h3>';
            $html .= '<p class="first">
                <form action="./fma.php?action=import&amp;table=' . $table->getId() . '" method="post" id="import" enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
                Choose a csv file <input type="file" id="csv" name="csv" />
                Choose a separator <select class="input-medium" id="separator" name="separator">
                    <option value="%%">%%</option>
                    <option value=";;">;;</option>
                    <option value="##">##</option>
                </select>
                <br />
                <button onclick="$(\'#editRecord\').submit();">OK</button>
                </form>
            </p>';

            return $html;
        }

        private function importMap()
        {
            if (!empty($_POST)) {
                $dbTable    = System::Table();
                $dbs        = System::Structure();
                $table      = $dbTable->find(request()->getTable());

                if (empty($table)) {
                    return $this->error('Wrong request.');
                }

                $db         = container()->fbm($table->getName());
                $session    = session('upload');
                $data       = $session->getCsv();

                if (!strlen($data)) {
                    return $this->error("An error occured. Please try again.");
                }

                $rows           = explode("\n", $data);
                $first          = Arrays::first($rows);
                $words          = explode(request()->getSeparator(), $first);
                $fieldsToImport = [];

                for ($i = 0; $i < count($words); $i++) {
                    $req    = 'field_' . $i;
                    $label  =  request()->$req;
                    $fieldsToImport[] = $label;
                }

                $map = [];

                foreach ($rows as $row) {
                    if (strstr(trim($row), request()->getSeparator())) {
                        $words = explode(request()->getSeparator(), trim($row));
                        $record = [];

                        for ($i = 0; $i < count($words); $i++) {
                            $record[$fieldsToImport[$i]] = trim($words[$i]);
                        }

                        $map[] = $record;
                    }
                }

                if (count($map)) {
                    foreach ($map as $newRecord) {
                        $db->create()->hydrate($newRecord)->save();
                    }
                }

                Router::redirect(
                    container()->getUrlsite() . 'fma.php?action=displayData&id=' . $table->getId()
                );
            } else {
                return $this->error("An error occured. Please try again.");
            }
        }

        private function map($structures, $table, $separator = '%%')
        {
            $session    = session('upload');
            $data       = $session->getCsv();

            if (!strlen($data)) {
                return $this->error("An error occured. Please try again.");
            }

            $rows   = explode("\n", $data);
            $first  = current($rows);

            $html   = '<p><h2>Map the fields to structure</h2></p>
            <form action="./fma.php?action=importMap&amp;table=' . $table->getId() . '" method="post" name="map" id="map"><input type="hidden" name="separator" id="separator" value="' . $separator . '" />
            ';
            $select = '<select id="field_##key##" name="field_##key##">';

            foreach ($structures as $structure) {
                $label = $structure->getLabel();
                $label = !strlen($label) ? $structure->field()->getName() : $label;
                $select .= '<option value="' . $structure->field()->getName() . '">' . $label . '</option>';
            }

            $select .= '</select>';

            if (!strstr($first, $separator)) {
                return $this->error('An error occured. Plese try again.');
            }

            $words = explode($separator, $first);

            for ($i = 0; $i < count($words); $i++) {
                $word = trim($words[$i]);
                $tmpSelect = repl('##key##', $i, $select);
                $html .= $word . ' => ' . $tmpSelect . '<br />';
            }

            $html .= '<button onclick="$(\'#map\').submit();">OK</button>
                </form>';

            return $html;
        }

        private function uploadSession($field)
        {
            $session = session('upload');
            $upload  = isAke($_FILES, $field);

            if (!empty($upload)) {
                $setter = setter($field);
                $session->$setter(fgc($upload['tmp_name']));

                return true;
            }

            return false;
        }

        private function upload($field)
        {
            $bucket = container()->bucket();

            if (Arrays::exists($field, $_FILES)) {
                $fileupload         = $_FILES[$field]['tmp_name'];
                $fileuploadName     = $_FILES[$field]['name'];

                if (strlen($fileuploadName)) {
                    $tab = explode(".", $fileuploadName);
                    $ext = Inflector::lower(Arrays::last($tab));

                    return $bucket->data(fgc($fileupload), $ext);
                }
            }

            return null;
        }

        private function makeUpload($record, $table)
        {
            $dbs        = System::Structure();
            $structures = $dbs->where('table = ' . $table->getId())->order('field')->exec(true);
            $files      = [];

            foreach ($structures as $structure) {
                $type = $structure->getType();

                if ($type == 'file') {
                    $field = $structure->field()->getName();
                    array_push($files, $field);
                }
            }

            if (count($files)) {
                foreach ($files as $file) {
                    $record->$field = $this->upload($field);
                }
            }

            return $record;
        }

        private function editRecord()
        {
            $dbTable    = System::Table();
            $dbs        = System::Structure();
            $table      = $dbTable->find(request()->getTable());
            $structures = $dbs->where('table = ' . $table->getId())->order('field')->exec(true);

            if (empty($table)) {
                return $this->error('Wrong request.');
            }

            $db         = container()->fbm($table->getName(), $table->getNs());
            $record     = $db->find(request()->getId());

            if (empty($record)) {
                return $this->error('Wrong request.');
            }

            if (!empty($_POST)) {
                $MAX_FILE_SIZE = isAke($_POST, 'MAX_FILE_SIZE', null);

                if (!is_null($MAX_FILE_SIZE)) {
                    unset($_POST['MAX_FILE_SIZE']);
                }

                $this->makeUpload($record, $table);
                $record->hydrate()->save();

                Router::redirect(
                    container()->getUrlsite() . 'fma.php?action=displayData&id=' . $table->getId()
                );
            }

            $html = $this->headerTable($table->getId());
            $html .= '<p class="first">Edit record ' . $record->getId() . '</p>';

            $html .= '<form action="./fma.php?action=editRecord&amp;table=' . $table->getId() . '&amp;id=' . $record->getId() . '" method="post" id="editRecord" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
            <table class="table">';
            $html .= '<tr><th>id</th><td>' . $record->getId() . '</td></tr>';

            foreach ($structures as $structure) {
                $field      = $structure->field()->getName();
                $label      = $structure->getLabel();
                $label      = !strlen($label) ? $field : $label;
                $type       = $structure->getType();
                $length     = $structure->getLength();
                $required   = $structure->getCanBeNull() ? false : true;
                $input      = $this->inputForm($type, $field, $length, $required, $record->$field, $structure);
                $html .= '<tr>
                <th>' . $label . '</th>
                <td>' . $input . '</td>
                </tr>';
            }

            $html .= '<tr><td>&nbsp;</td><td><button onclick="$(\'#editRecord\').submit();">OK</button></td></tr>';
            $html .= '</table></form>';

            return $html;
        }

        private function duplicateRecord()
        {
            $dbTable    = System::Table();
            $dbs        = System::Structure();
            $table      = $dbTable->find(request()->getTable());
            $structures = $dbs->where('table = ' . $table->getId())->order('field')->exec(true);

            if (empty($table)) {
                return $this->error('Wrong request.');
            }

            $db         = container()->fbm($table->getName(), $table->getNs());
            $record     = $db->find(request()->getId());

            if (empty($record)) {
                return $this->error('Wrong request.');
            }

            if (!empty($_POST)) {
                $MAX_FILE_SIZE = isAke($_POST, 'MAX_FILE_SIZE', null);
                if (!is_null($MAX_FILE_SIZE)) {
                    unset($_POST['MAX_FILE_SIZE']);
                }
                $record = $db->create();
                $this->makeUpload($record, $table);
                $record->hydrate()->save();
                Router::redirect(
                    container()->getUrlsite() . 'fma.php?action=displayData&id=' . $table->getId()
                );
            }

            $html = $this->headerTable($table->getId());
            $html .= '<p class="first">Duplicate record ' . $record->getId() . '</p>';

            $html .= '<form action="./fma.php?action=duplicateRecord&amp;table=' . $table->getId() . '&amp;id=' . $record->getId() . '" method="post" id="duplicateRecord" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
            <table class="table">';

            foreach ($structures as $structure) {
                $field      = $structure->field()->getName();
                $label      = $structure->getLabel();
                $label      = !strlen($label) ? $field : $label;
                $type       = $structure->getType();
                $length     = $structure->getLength();
                $required   = $structure->getCanBeNull() ? false : true;
                $input      = $this->inputForm($type, $field, $length, $required, $record->$field, $structure);
                $html .= '<tr>
                <th>' . $label . '</th>
                <td>' . $input . '</td>
                </tr>';
            }

            $html .= '<tr><td>&nbsp;</td><td><button onclick="$(\'#duplicateRecord\').submit();">OK</button></td></tr>';
            $html .= '</table></form>';

            return $html;
        }

        private function addRecord()
        {
            $dbTable    = System::Table();
            $dbs        = System::Structure();
            $table      = $dbTable->find(request()->getId());

            if (empty($table)) {
                return $this->error('Wrong request.');
            }

            if (!empty($_POST)) {
                $MAX_FILE_SIZE = isAke($_POST, 'MAX_FILE_SIZE', null);

                if (!is_null($MAX_FILE_SIZE)) {
                    unset($_POST['MAX_FILE_SIZE']);
                }

                $db = container()->fbm($table->getName(), $table->getNs());
                $record = $db->create();
                $this->makeUpload($record, $table);
                $record->hydrate()->save();
                Router::redirect(
                    container()->getUrlsite() . 'fma.php?action=displayData&id=' . $table->getId()
                );
            }

            $html = $this->headerTable($table->getId());
            $html .= '<p class="first">Add record</p>';

            $structures = $dbs->where('table = ' . $table->getId())->order('field')->exec(true);
            $html .= '<form action="./fma.php?action=addRecord&amp;id=' . $table->getId() . '" method="post" id="addRecord" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
            <table class="table">';

            foreach ($structures as $structure) {
                $field      = $structure->field()->getName();
                $type       = $structure->getType();
                $label      = $structure->getLabel();
                $label      = !strlen($label) ? $field : $label;
                $length     = $structure->getLength();
                $required   = $structure->getCanBeNull() ? false : true;
                $input      = $this->inputForm($type, $field, $length, $required, $structure->getDefault(), $structure);
                $html .= '<tr>
                <th>' . $label . '</th>
                <td>' . $input . '</td>
                </tr>';
            }

            $html .= '<tr><td>&nbsp;</td><td><button onclick="$(\'#addRecord\').submit();">OK</button></td></tr>';
            $html .= '</table></form>';

            return $html;
        }

        private function addStructure()
        {
            $dbTable    = System::Table();
            $table      = $dbTable->find(request()->getTable());

            if (empty($table)) {
                return $this->error('Wrong request.');
            }

            if (!empty($_POST)) {
                $this->checkBool('is_index')->checkBool('can_be_null');
                $dbf        = container()->fbm('fma_field');
                $dbs        = System::Structure();
                $db         = container()->fbm($table->getName(), $table->getNs());
                $name       = request()->getName();
                $field      = $dbf->where('name = ' . $name)->first(true);
                $structure  = $dbs->create();

                if (!empty($field)) {
                    $check = $dbs
                    ->where('table = ' . request()->getTable())
                    ->where('field = ' . $field->getId())
                    ->first(true);
                    if (!empty($check)) {
                        $structure = $check;
                    } else {
                        $structure->setTable($table->getId())->setField($field->getId());
                        $data = $db->fetch()->exec(true);

                        foreach ($data as $row) {
                            $row->$name = request()->getDefault();
                            $row->save();
                        }
                    }
                } else {
                    $field = $dbf->create()->setName($name)->save();
                    $structure->setTable($table->getId())->setField($field->getId());
                    $data = $db->fetch()->exec(true);

                    foreach ($data as $row) {
                        $row->$name = request()->getDefault();
                        $row->save();
                    }
                }

                $structure->hydrate()->save();

                Router::redirect(
                    container()->getUrlsite() . 'fma.php?action=table&id=' . request()->getTable()
                );
            }

            $html           = $this->headerTable(request()->getTable());
            $selectTypes    = $this->selectTypes(null, request()->getTable());
            $html           .= '<p class="first">Add a new field</p>';
            $html           .= '<p class="first">
            <form action="./fma.php?action=addStructure&amp;table=' . request()->getTable() . '" method="post" id="editStructure">
            <table class="table">
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Length</th>
                <th>Default</th>
                <th>Null</th>
                <th>Index</th>
                <th>Label</th>
            </tr>
            <tr>
                <td><input required class="input-small" name="name" value="" id="name" type="text" /></td>
                <td>' . $selectTypes . '</td>
                <td><input class="input-small" value="255" name="length" id="length" /></td>
                <td><input class="input-small" value="" name="default" id="default" /></td>
                <td><input name="can_be_null" value="true" id="can_be_null" type="checkbox" /></td>
                <td><input name="is_index" value="true" id="is_index" type="checkbox" /></td>
                <td><input class="input-small" name="label" value="" id="label" type="text" /></td>
            </tr>
            </table>
            </p><p><button onclick="$(\'#editStructure\').submit();">OK</button></p>';

            return $html;
        }

        private function editStructure()
        {
            $dbs = System::Structure();
            $structure = $dbs->find(request()->getId());

            if (empty($structure)) {
                return $this->error('Wrong request.');
            }

            if (!empty($_POST)) {
                $this->checkBool('is_index')->checkBool('can_be_null');
                $structure->hydrate()->save();
                Router::redirect(
                    container()->getUrlsite() . 'fma.php?action=table&id=' . $structure->getTable()
                );
            }

            $html        = $this->headerTable($structure->getTable());
            $canBeNull   = true === $structure->getCanBeNull() ? 'checked' : '';
            $isIndex     = true === $structure->getIsIndex() ? 'checked' : '';
            $selectTypes = $this->selectTypes($structure);
            $html .= '<p class="first">Edit structure of &laquo; ' . $structure->field()->getName() . ' &raquo;</p>';
            $html .= '<p class="first">
            <form action="./fma.php?action=editStructure&amp;id=' . request()->getId() . '" method="post" id="editStructure">
            <table class="table">
            <tr>
                <th>Type</th>
                <th>Length</th>
                <th>Default</th>
                <th>Null</th>
                <th>Index</th>
                <th>Label</th>
            </tr>
            <tr>
                <td>' . $selectTypes . '</td>
                <td><input class="input-small" value="' . $structure->getLength() . '" name="length" id="length" /></td>
                <td><input class="input-small" value="' . $structure->getDefault() . '" name="default" id="default" /></td>
                <td><input name="can_be_null" value="true" id="can_be_null" type="checkbox" ' . $canBeNull . ' /></td>
                <td><input name="is_index" value="true" id="is_index" type="checkbox" ' . $isIndex . '/></td>
                <td><input class="input-small" value="' . $structure->getLabel() . '" name="label" id="label" /></td>
            </tr>
            </table>
            </p><p><button onclick="$(\'#editStructure\').submit();">OK</button></p>';

            return $html;
        }

        private function selectTypes($structure = null, $table = null)
        {
            $dbs        = System::Structure();
            $dbTable    = System::Table();

            if (!empty($structure)) {
                $table  = $structure->getTable();
                $type   = $structure->getType();
            } else {
                if (!empty($table)) {
                    $type = null;
                }
            }

            $fkTables = $dbTable->where('id != ' . $table)->order('name')->exec();

            $select = '<select class="input-small" id="type" name="type">
            <optgroup label="Strings">
            <option ' . $this->selected($type, 'varchar') . '>varchar</option>
            <option ' . $this->selected($type, 'text') . '>text</option>
            <option ' . $this->selected($type, 'email') . '>email</option>
            <option ' . $this->selected($type, 'file') . '>file</option>
            <option ' . $this->selected($type, 'char') . '>char</option>
            <option ' . $this->selected($type, 'tinytext') . '>tinytext</option>
            <option ' . $this->selected($type, 'mediumtext') . '>mediumtext</option>
            <option ' . $this->selected($type, 'longtext') . '>longtext</option>
            </optgroup>
            <optgroup label="Numbers">
            <option ' . $this->selected($type, 'tinyint') . '>tinyint</option>
            <option ' . $this->selected($type, 'smallint') . '>smallint</option>
            <option ' . $this->selected($type, 'mediumint') . '>mediumint</option>
            <option ' . $this->selected($type, 'int') . '>int</option>
            <option ' . $this->selected($type, 'bigint') . '>bigint</option>
            <option ' . $this->selected($type, 'decimal') . '>decimal</option>
            <option ' . $this->selected($type, 'float') . '>float</option>
            <option ' . $this->selected($type, 'double') . '>double</option>
            </optgroup>
            <optgroup label="Date and time">
            <option ' . $this->selected($type, 'date') . '>date</option>
            <option ' . $this->selected($type, 'datetime') . '>datetime</option>
            <option ' . $this->selected($type, 'timestamp') . '>timestamp</option>
            <option ' . $this->selected($type, 'time') . '>time</option>
            <option ' . $this->selected($type, 'year') . '>year</option>
            </optgroup>

            <optgroup label="Foreign keys">';

            foreach ($fkTables as $fkTable) {
                $select .= '<option value="fk_' . $fkTable['name'] . '" ' . $this->selected($type, 'fk_' . $fkTable['name']) . '>' . $fkTable['name'] . '</option>';
            }

            $select .= '<optgroup label="Lists">
            <option ' . $this->selected($type, 'set') . '>set</option>
            </optgroup>
            <optgroup label="Binary">
            <option ' . $this->selected($type, 'bit') . '>bit</option>
            <option ' . $this->selected($type, 'binary') . '>binary</option>
            <option ' . $this->selected($type, 'varbinary') . '>varbinary</option>
            <option ' . $this->selected($type, 'tinyblob') . '>tinyblob</option>
            <option ' . $this->selected($type, 'blob') . '>blob</option>
            <option ' . $this->selected($type, 'mediumblob') . '>mediumblob</option>
            <option ' . $this->selected($type, 'longblob') . '>longblob</option>
            </optgroup>
            <optgroup label="Geometry">
            <option ' . $this->selected($type, 'geometry') . '>geometry</option>
            <option ' . $this->selected($type, 'point') . '>point</option>
            <option ' . $this->selected($type, 'linestring') . '>linestring</option>
            <option ' . $this->selected($type, 'polygon') . '>polygon</option>
            <option ' . $this->selected($type, 'multipoint') . '>multipoint</option>
            <option ' . $this->selected($type, 'multilinestring') . '>multilinestring</option>
            <option ' . $this->selected($type, 'multipolygon') . '>multipolygon</option>
            <option ' . $this->selected($type, 'geometrycollection') . '>geometrycollection</option>
            </optgroup>';
            $select .= '</optgroup></select>';

            return $select;
        }

        private function selected($value, $expected)
        {
            if (strlen($value)) {
                if ($value == $expected) {
                    return 'selected';
                }
            }

            return '';
        }

        private function error($message)
        {
            return '<div class="error">' . $message . '</div>';
        }

        private function tableById($id)
        {
            $dbt = $this->tableByName('fma_table');
            $t = $dbt->find($id);

            if (!empty($t)) {
                return $this->tableByName($t->name, $t->ns);
            }

            return null;
        }

        private function tableByValue($id)
        {
            $dbt = System::Table();
            $t = $dbt->where('name = ' . $id)->first(true);

            if (!empty($t)) {
                return $t;
            }

            return null;
        }

        private function tableByName($name, $ns = 'core')
        {
            return container()->fbm($name, $ns);
        }

        public function truncate($str, $length = 20)
        {
            if (strlen($str) > $length) {
                $seg = substr($str, 0, $length);
                $str = $seg . '&hellip;';
            }

            return $str;
        }
    }

    set_time_limit(0);
    // $db = container()->fbm('test');
    // $i = 0;
    // $max = 0;
    // while ($i < $max) {
    //     $i++;
    //     $db->create()->setName(Utils::token())->setPrice(rand(100, 25000))->save();
    // }

    // System::Auth()->create()->setLogin('gplusquellec')->setPassword(sha1('123456'))->save();

    $error = null;

    $dbAuth     = System::Auth();
    $session    = session('fma');
    $auth       = $session->getAuth();
    $isAuth     = !is_null($auth);

    if (false === $isAuth && !empty($_POST)) {
        $login      = $request->getLogin();
        $password   = $request->getPassword();

        if (!is_null($login) && !is_null($password)) {
            $count = $dbAuth->where(['login', '=', $login])->where(['password', '=', sha1($password)])->count();

            if (0 < $count) {
                $isAuth = true;
                $session->setAuth($isAuth);
            }
        } else {
            $error = 'Wrong credentials.';
        }
    }

    if (true === $isAuth) {
        $session->setAuth($isAuth);
        Fastdata::tables();
        $action     = $request->getAction();
        $action     = is_null($action) ? 'home' : $action;
        $dbTable    = System::Table();
        $tables     = $dbTable->sortBy('name')->sortBy('ns')->get();

        $rma        = new Fma();
        $content    = $rma->content($action, $tables);
    }

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>flatMyAdmin</title>
        <link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="http://fonts.googleapis.com/css?family=Oswald:400,300,700|Questrial:400,300,700,900,600&amp;subset=latin,latin-ext" rel="stylesheet" type="text/css" />
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" />
        <style>
        .affix {
            position: fixed;
        }

        body, .table {
            font-family: Oswald;
            font-weight: 300;
            background: #222;
            color: #f1f1f1;
            font-size: 18px;
        }

        .table {
            font-family: Questrial;
        }

        .table th {
            background: #000;
            color: #fff;
            font-size: 18px;
        }

        .table tr:hover, .table tr:hover a {
            background: #888;
            color: #222;
        }

        .table tr:hover a:hover {
            color: #ffcc00;
        }

        a {
            color: #f1f1f1;
            text-decoration: none;
            font-weight: bold;
        }

        .half, .third, .fourth, .fifth, .sixth {
            float: left;
            min-height: 1px;
            margin-right: 2%;
        }

        .half {
            width: 49%;
        }

        .third {
            width: 32%;
        }

        .fourth {
            width: 23.5%;
        }

        .fifth {
            width: 18.4%;
        }

        .sixth {
            width: 15%;
        }

        .half:last-child, .third:last-child, .fourth:last-child, .fifth:last-child, .sixth:last-child {
            margin-right: 0;
        }
        section {
            margin-top: 50px;
            max-width: 100% !important;
            *zoom: 1;
        }

        section:before,
        section:after {
            display: table;
            line-height: 0;
            content: "";
        }

        section:after {
            clear: both;
        }

        .title {
            padding: 10px;
            border: solid 1px;
        }

        .bordered {
            border: solid 1px;
            padding: 10px;
        }

        button, input, textarea, select {
            color: #f1f1f1;
            background: #222;
            border: solid 1px;
            padding: 10px;
            margin-bottom: 25px;
            margin-right: 25px;
        }

        .input-small, .input-medium, #search button, #search input, #search textarea, #search select {
            color: #f1f1f1;
            background: #333;
            border: solid 1px;
            padding: 4px;
            margin-bottom: 12px;
            margin-right: 12px;
            font-size: 16px;
            font-family: Questrial;
            max-width: 100px;
        }

        .input-medium {
            max-width: 200px;
        }

        .link, .title, button {
            cursor: pointer;
        }

        .link:hover {
            color: #ffcc00;
        }

        .title:hover, button:hover, a:hover, #search button:hover {
            color: #ffcc00;
        }

        .inRow {
            margin-bottom: 25px;
        }

        .error {
            background: #b1b1b1;
            color: red;
            border: solid 2px red;
            padding: 20px;
        }

        .titleTable {
            margin-bottom: 35px;
            border-bottom: dotted 2px;
            padding: 15px;
        }

        .true {
            color: green;
        }

        .false {
            color: red;
        }

        .infos {
            font-family: Questrial;
            font-size: 16px;
        }

        .copyright {
            font-family: Questrial;
            font-size: 12px;
            margin-top: 50px;
        }

        .pagination ul {
            list-style: none;
        }

        .pagination ul:after {
            clear: both;
        }

        .pagination li {
            float: left;
            margin-right: 15px;
            border: solid 1px #999;
            padding: 5px;
            background: #666;
        }

        .pagination .active {
            background: #d1d1d1;
            color: #222;
        }

        .pagination .active a {
            background: #d1d1d1;
            color: #222;
        }

        .pagination li:last-child {
            margin-right: 0;
        }

        .first {
            margin-top: 30px;
        }

        .fk, .fk:hover {
            color: pink;
            font-weight: normal;
            text-decoration: underline;
        }

        .listTable {
            list-style: none;
            padding: 0;
        }

        .linkTable {
            font-weight: 300;
        }

        .yellow {
            border-bottom: dotted 1px #ffcc00;
            border-top: dotted 1px #ffcc00;
            padding: 25px;
            background: #444;
            font-size: 65%;
        }

        .table tr:last-child {
            border-bottom: solid 1px;
        }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if(false === $isAuth): ?>
            <h1 class="text-center">
                <span onclick="document.location.href = './fma.php';" class="title">
                    <i class="fa fa-cogs fa-3x"></i>&nbsp;&nbsp;flatMyAdmin
                </span>
            </h1>
            <section id="auth" class="row text-center">
                <form action="" method="post" id="authForm">
                    <i class="fa fa-user fa-3x inRow"></i><p />
                    <input required id="login" name="login" placeholder="login" />
                    <input required type="password" id="password" name="password" placeholder="password" />
                    <button onclick="document.getElementById('authForm').submit();">OK</button>
                </form>
            </section>
            <?php else: ?>
            <div class="row">
                <h5 class="pull-left">
                    <span onclick="document.location.href = './fma.php';" class="title">
                        <i class="fa fa-cogs fa-3x"></i>&nbsp;&nbsp;flatMyAdmin
                    </span>
                </h5>
                <div class="pull-right">
                    <a href="./fma.php?action=home">
                        <i class="fa fa-home"></i>
                    </a> |
                    <a href="./fma.php?action=logout">
                        <i class="fa fa-power-off"></i>
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <h2>Tables</h2>
                    <?php if(count($tables)): ?>
                    <div id="tables">
                        <ul class="listTable">
                            <?php foreach($tables as $table): ?>
                            <li>
                                <i rel="tooltip-t" title="Show data" onclick="document.location.href = './fma.php?action=displayData&amp;id=<?php echo $table['id']; ?>';" class="linkTable link fa fa-list"></i>
                                <a class="linkTable" href="./fma.php?action=table&amp;id=<?php echo $table['id']; ?>">
                                    <?php echo $rma->truncate($table['name']); ?>
                                    (<?php echo $rma->truncate($table['ns']); ?>)
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <?php echo $content; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="row copyright">
                &copy; GP 2008 - <?php echo date('Y'); ?>
            </div>
        </div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.0/js/bootstrap.min.js"></script>
        <script>

        function paginationGoPage(page)
        {
            $('#export').val(0);
            $('#page').val(page);
            $('#listForm').submit();
        }

        function paginationOrder(order)
        {
            var oldOrder = $('#order').val();
            var oldDirection = $('#direction').val();

            if (oldOrder != order) {
                var direction = 'ASC';
            } else {
                var direction = oldDirection == 'ASC' ? 'DESC' : 'ASC';
            }

            $('#order').val(order);
            $('#direction').val(direction);
            $('#export').val(0);
            $('#page').val(1);
            $('#listForm').submit();
        }

        function showHide(id)
        {
            if(!$('#' + id).is(':visible')) {
                $('#' + id).slideDown();
            } else {
                $('#' + id).slideUp();
            }
        }

        function copyRow()
        {
            var divs = $('#search').find('div');
            var divRef = divs[divs.length - 1];
            var newDiv = document.createElement('div');

            $(newDiv).html($(divRef).html());
            $('#search').append(newDiv);

            var i = $(divRef).find('i');
            i.remove();

            var newI = document.createElement('i');

            $(newI).attr('rel', 'tooltip');
            $(newI).attr('title', 'Delete this criteria');
            $(newI).addClass('link');
            $(newI).addClass('fa');
            $(newI).addClass('fa-trash-o');

            $(newI).click(function () {
                divRef.remove();
            });

            $(divRef).append(newI);
        }

        function search()
        {
            var fields      = '';
            var operators   = '';
            var values      = '';

            $('.fields').each(function() {
                if (0 < fields.length) {
                    fields += '##';
                }

                fields += $(this).val();
            });

            $('.operators').each(function() {
                if (0 < operators.length) {
                    operators += '##';
                }

                operators += $(this).val();
            });

            $('.values').each(function() {
                if (0 < values.length) {
                    values += '##';
                }

                values += $(this).val();
            });

            var query = fields + '%%' + operators + '%%' + values;

            $('#order').val('id');
            $('#direction').val('ASC');
            $('#page').val(1);
            $('#export').val(0);
            $('#query').val(query);
            $('#listForm').submit();
        }

        function makeExport()
        {
            $('#export').val(1);
            $('#listForm').submit();
        }

        function selfPage()
        {
            document.location.href = document.URL;
        }

        $(document).ready(function() {
            $('.openHover').dropdownHover().dropdown();

            $('[rel=tooltip]').tooltip({
                placement: 'bottom'
            });

            $('[rel=tooltip-b]').tooltip({
                placement: 'bottom'
            });

            $('[rel=tooltip-t]').tooltip({
                placement: 'top'
            });

            $('[rel=tooltip-l]').tooltip({
                placement: 'left'
            });

            $('[rel=tooltip-r]').tooltip({
                placement: 'right'
            });
        });
        </script>
    </body>
</html>
