<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/RdbAPI.php';

class Nifcloud_RdbParam extends Nifcloud_RdbAPI
{
    public function exists()
    {
        return isset($this->res['DescribeDBParameterGroupsResult']['DBParameterGroups']['DBParameterGroup']['DBParameterGroupName']);
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'               => 'DescribeDBParameterGroups',
            'DBParameterGroupName' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($family, $desc = '')
    {
        $url   = '';
        $param = array(
            'Action'                 => 'CreateDBParameterGroup',
            'DBParameterGroupFamily' => $family,
            'DBParameterGroupName'   => $this->name,
            'Description'            => $desc,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function modify($params)
    {
        $url   = '';
        $param = array(
            'Action'                 => 'ModifyDBParameterGroup',
            'DBParameterGroupName'   => $this->name,
        );
        $idx = 0;
        foreach ($params as $v) {
            $idx++;
            $param += array(
                "Parameters.member.{$idx}.ApplyMethod"    => $v['method'],
                "Parameters.member.{$idx}.ParameterName"  => $v['name'],
                "Parameters.member.{$idx}.ParameterValue" => $v['value'],
            );
        }

        $this->call($url, $param);
        return !$this->isError();
    }

}
