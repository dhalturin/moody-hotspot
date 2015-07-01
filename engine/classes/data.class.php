<?
if(!defined('hotspot')) die('.!.');

class data
{
    function data()
    {
        global $db;

        $this->db = $db;
    }

    function getList($filter = false)
    {
        $field = array(
            'table' => 'device',
            'field' => '*',
            'where' => $filter,
            'order' => array('ip', false)
            //,'debug' => true
        );

        //if($filter == 'banned') die;

        return $this->db->select($field);
    }

    function deviceInsert($data)
    {
        return $this->db->insert(array(
            'table' => 'device',
            'field' => $data
            //,'debug' => true
        ));
    }

    function deviceUpdate($data, $filter)
    {
        return $this->db->update(array(
            'table' => 'device',
            'field' => $data,
            'where' => $filter
            //,'debug' => true
        ));
    }
}

$data = new data;
