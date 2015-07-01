<?
if(!defined('hotspot')) die('.!.');

class tpl
{
    var $content;
    var $data = array();

    function tpl()
    {
        $this->data['main'] = $this->open('main', true);
    }

    function set($tpl, $search, $replace, $from_tpl = false)
    {
        if($from_tpl)
        {
            $replace = (!isset($this->data[$replace]) ? $this->open($replace, true) : $this->data[$replace]);
        }

        $this->data[$tpl] = str_replace('{%' . $search . '%}', $replace, $this->data[$tpl]);
    }

    function compile($tpl)
    {
        print($this->data[$tpl]);
    }

    function open($tpl, $return = false)
    {
        $this->data[$tpl] = file_get_contents(root_dir . '/engine/templates/' . $tpl . '.tpl');

        if($return) return $this->data[$tpl];
    }
}

$tpl = new tpl;
