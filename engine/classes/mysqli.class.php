<?
if(!defined('hotspot')) die('.!.');

class db
{
    var $db_id          = false;
    var $db_version;
    var $db_query;
    var $db_q_num       = 0;
    function connect($host, $base, $user, $pass)
    {
        $host = explode(':', $host);

        if($host[1])
            $this->db_id = mysqli_connect($host[0], $user, $pass, $base, $host[1]);
        else
            $this->db_id = mysqli_connect($host[0], $user, $pass, $base);

        if(!$this->db_id)
            $this->show_error(mysqli_connect_error());

        $this->db_version = mysqli_get_server_info($this->db_id);

        $this->query('set names utf8 collate utf8_bin');

        return true;
    }

    function close()
    {
        mysqli_close($this->db_id);
    }

    function query($query)
    {
        global $cfg;

        if(!$this->db_id)
            $this->connect($cfg['db_host'], $cfg['db_base'], $cfg['db_user'], $cfg['db_pass']);

        if(($this->db_query = mysqli_query($this->db_id, $query)))
        {
            $this->db_q_num ++;
            return $this->db_query;
        }
        else
        {
            return $this->show_error(mysqli_error($this->db_id), $query);
        }
    }

    function insert($input)
    {
        $query = 'insert into `' . $input['table'] . '` (';

        for($i = 0; $i < count($input['field']); $i++)
        {
            $query.= '`' . $input['field'][$i][0] . '`';

            if(($i + 1) != count($input['field']))
                $query.= ', ';
        }

        $query.= ') values (';

        for($i = 0; $i < count($input['field']); $i++)
        {
            //$query.= '\'' . $this->escape($input['field'][$i][1]) . '\'';
            $query.= '\'' . $input['field'][$i][1] . '\'';

            if(($i + 1) != count($input['field']))
                $query.= ', ';
        }

        $query.= ');';

        if($input['debug'])
            print($query . "\r\n");

        return $this->query($query);
    }

    function update($input)
    {
        $query = 'update `' . $input['table'] . '` set ';
        for($i = 0; $i < count($input['field']); $i++)
        {
            if($input['field'][$i][2])
            {
                //$query.= '`' . $input['field'][$i][0] . '` = `' . $this->escape($input['field'][$i][0] . '` ' . $input['field'][$i][2] . ' \'' . $input['field'][$i][1] . '\'';
                $query.= '`' . $this->escape(htmlspecialchars($input['field'][$i][0], ENT_QUOTES, 'utf-8')) . '` = `' . $this->escape(htmlspecialchars($input['field'][$i][0], ENT_QUOTES, 'utf-8')) . '` ' . $this->escape(htmlspecialchars($input['field'][$i][2], ENT_QUOTES, 'utf-8')) . ' \'' . $this->escape(htmlspecialchars($input['field'][$i][1], ENT_QUOTES, 'utf-8')) . '\'';
            }
            else
            {
                $query.= '`' . $this->escape(htmlspecialchars($input['field'][$i][0], ENT_QUOTES, 'utf-8')) . '` = \'' . $this->escape(htmlspecialchars($input['field'][$i][1], ENT_QUOTES, 'utf-8')) . '\'';
            }

            if(($i + 1) != count($input['field']))
                $query.= ', ';
        }

        if($input['where'])
        {
            $query.= ' where';
            if(is_array($input['where']))
            {
                if(is_array($input['where'][0]))
                {
                    for($i = 0; $i < count($input['where']); $i++)
                    {
                        if(is_array($input['where'][$i]))
                        {
                            $query.= ' `' . $input['where'][$i][0] . '` = \'' . $input['where'][$i][1] . '\'';
                        }
                        else
                        {
                            $query.= ' ' . $input['where'][$i];
                        }
                        if(($i + 1) != count($input['where']))
                            if($input['where'][$i + 1][2] && is_array($input['where'][$i + 1])){
                                $query.= ' ' . $input['where'][$i + 1][2];
                            }else
                                $query.= ' and';
                    }
                }
                else
                {
                    $query.= ' `' . $input['where'][0] . '` = \'' . $input['where'][1] . '\'';
                }

            }
            else $query.= ' ' . $input['where'];
        }

        if($input['order'])
        {
            $query.= ' order by';
            if(is_array($input['order'][0]))
            {
                for($i = 0; $i < count($input['order']); $i++)
                {
                    $query.= ' `' . $input['order'][$i][0] . '` ' . ($input['order'][$i][1]?'desc':'asc');
                    if(($i + 1) != count($input['order']))
                        $query.= ', ';
                }
            }
            else $query.= ' `' . $input['order'][0] . '` ' . ($input['order'][1]?'desc':'asc');
        }

        if($input['limit'])
        {
            $query.= ' limit ';
            if(is_array($input['limit'])) $query .= $input['limit'][0] . ', ' . $input['limit'][1];
            else $query.= $input['limit'];
        }

        $query.= ';';

        if($input['debug'])
        {
            print($query . "\r\n");
        }

        return $this->query($query);
    }
    function select($input, $cache_time = null, $recache = null)//, $where=null, $sort=null, $limit=null, $debug=null)
    {
        if($cache_time)
        {
            global $mc;

            return $mc->select($input, $cache_time, $recache);
        }

        if($input['debug'])
        {
            print str_repeat('- ', 40) . PHP_EOL;
            getmicrotime();
        }

        $query = 'select ';
        if(is_array($input['field']))
        {
            for($i = 0; $i < count($input['field']); $i++)
            {
                $query.= '`' . $input['field'][$i] . '`';
                if(($i + 1) != count($input['field']))
                    $query.= ', ';
            }
        }
        elseif(empty($input['field']))
        {
            $query.= '*';
        }
        else
        {
            $query.= $input['field'];
        }

        $query.= ' from `' . $input['table'] . '`';

        if($input['where'])
        {
            $query.= ' where';
            if(is_array($input['where']))
            {
                if(sizeof($input['where']) == 2 && !is_array($input['where'][0]))
                {
                    $query.= ' `' . $input['where'][0] . '` = \'' . $input['where'][1] . '\'';
                }
                else
                {
                    for($i = 0; $i < count($input['where']); $i++)
                    {
                        if(is_array($input['where'][$i]))
                        {
                            //$query.= ' `' . $input['where'][$i][0] . '` = \'' . $this->escape($input['where'][$i][1]) . '\'';
                            $query.= ' `' . $input['where'][$i][0] . '` = \'' . $input['where'][$i][1] . '\'';
                        }
                        else
                        {
                            $query.= ' ' . $input['where'][$i];
                        }

                        if(($i + 1) != count($input['where']) && $input['where'][$i + 1])
                        {
                            if($input['where'][$i + 1][2] && is_array($input['where'][$i + 1]))
                            {
                                $query.= ' ' . $input['where'][$i + 1][2];
                            }
                            else
                            {
                                $query.= ' and';
                            }
                        }
                    }
                }
            }
            else $query.= ' ' . $input['where'];
        }

        if($input['order'])
        {
            $query.= ' order by';
            if(!is_array($input['order'][0]) && sizeof($input['order']) == 2)
            {
                $query.= ' `' . $input['order'][0] . '` ' . ($input['order'][1] ? 'desc':'asc');
            }
            else
            {
                for($i = 0; $i < count($input['order']); $i++)
                {
                    if(is_array($input['order'][$i]))
                    {
                        //$query.= ' `' . $input['where'][$i][0] . '` = \'' . $this->escape($input['where'][$i][1]) . '\'';
                        $query.= ' `' . $input['order'][$i][0] . '` ' . ($input['order'][$i][1]?'desc':'asc');
                    }
                    else
                    {
                        $query.= ' ' . $input['order'][$i];
                    }


                    if(($i + 1) != count($input['order']))
                        $query.= ',';
                }
            }
        }

        if($input['limit'])
        {
            $query.= ' limit ';
            if(is_array($input['limit'])) $query .= $input['limit'][0] . ', ' . $input['limit'][1];
            else $query.= $input['limit'];
        }

        $query.= ';';

        if($input['debug'])
        {
            print 'Time to generate query: ' . getmicrotime() . PHP_EOL;
        }

        if(($result = $this->query($query)))
        {
            if($input['debug'])
            {
                print 'Time to execute query: ' . getmicrotime() . PHP_EOL;
            }

            $return = array();

            while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
            {
                if($input['id'])
                {
                    if(is_array($input['id'])) $return[] = $row[$input['id'][0]];
                    else $return[$row[$input['id']]] = $row;
                }
                else
                {
                    $return[] = $row;
                }
            }

            if($input['debug'])
            {
                print 'Time to execute result: ' . getmicrotime() . PHP_EOL . PHP_EOL;
                print 'Query: ' . $query . PHP_EOL;
                print str_repeat('- ', 40) . PHP_EOL;
            }

            return $return;
        }
        else
        {
            print $result;
            return $result;
        }
    }

    function delete($input){
        $query = 'delete from `' . $input['table'] . '`';
        if($input['where'])
        {
            $query.= ' where';
            if(is_array($input['where']))
            {
                if(is_array($input['where'][0]))
                {
                    for($i = 0; $i < count($input['where']); $i++)
                    {
                        if(is_array($input['where'][$i]))
                        {
                            $query.= ' `' . $input['where'][$i][0] . '` = \'' . $input['where'][$i][1] . '\'';
                        }
                        else
                        {
                            $query.= ' ' . $input['where'][$i];
                        }
                        if(($i + 1) != count($input['where']))
                            if($input['where'][$i + 1][2] && is_array($input['where'][$i + 1])){
                                $query.= ' ' . $input['where'][$i + 1][2];
                            }else
                                $query.= ' and';
                    }
                }
                else
                {
                    $query.= ' `' . $input['where'][0] . '` = \'' . $input['where'][1] . '\'';
                }
            }
            else $query.= ' ' . $input['where'];
        }
        $query.= ';';

        if($input['debug'])
            print($query . "\r\n");

        return $this->query($query);
    }
    function escape($string)
    {
        global $_db;

        if(!$this->db_id)
            $this->connect($_db['host'], $_db['base'], $_db['user'], $_db['pass']);

        return mysqli_real_escape_string($this->db_id, $string);
    }
    function show_error($error, $query = null)
    {
        global $m, $user, $result, $tpl;

        $result[$m]['error'] = '';

        if($user[0]['group'] < 2)
        {
            $result[$m]['error'].= '<b>MySQL stack trace </b>: ' . $error . '<br/><br/>';

            if($query)
            {
                $result[$m]['error'].= '<b>Query</b>: ' . $query . '<br/><br/>';
            }
        }

        if($query) return false;
        else
        {
            $tpl->create();
            $tpl->set('title', $cfg['title']);
            $tpl->set('api_id', $app['id']);
            $tpl->set('date', date('YmdHis'));
            $tpl->set('random', rand(10000, 99999));
            $tpl->set('request', $_GET['request_key'] ? $_GET['request_key'] : '');
            $tpl->set('var_config', json_encode($_cfg));
            $tpl->set('var_user', json_encode($user));
            $tpl->set('year', date('Y'));
            $tpl->compile();
            die;            
        }
    }
}

$db = new db;
