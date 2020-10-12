<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/RdbAPI.php';

class Nifcloud_Rdb extends Nifcloud_RdbAPI
{
    public function getLog($date = null, $start = null, $end =null)
    {
        $url   = '';
        $param = array(
            'Action'      => 'DescribeEvents',
        );
        if (!is_null($this->name)) {
            $param += array(
                'SourceType' => 'db-instance',
                'SourceIdentifier' => $this->name,
            );
        }
        if ($date !== null) {
            $param['StartTime'] = gmdate('Y-m-d\TH:i:s\Z', strtotime("{$date} 00:00:00"));
            $param['EndTime'] = gmdate('Y-m-d\TH:i:s\Z', strtotime("{$date} 23:59:59"));
        }
        if ($start !== null && $end !== null) {
            $param['StartTime'] = gmdate('Y-m-d\TH:i:s\Z', strtotime($start));
            $param['EndTime'] = gmdate('Y-m-d\TH:i:s\Z', strtotime($end));
        }

        $this->call($url, $param);
        return !$this->isError();
    }

    public function exists()
    {
        return isset($this->res['DescribeDBInstancesResult']['DBInstances']['DBInstance']['DBInstanceIdentifier']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['DescribeDBInstancesResult']['DBInstances']['DBInstance']['DBInstanceStatus'])
            ? $this->res['DescribeDBInstancesResult']['DBInstances']['DBInstance']['DBInstanceStatus']
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'               => 'DescribeDBInstances',
            'DBInstanceIdentifier' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($params)
    {
        $url   = '';
        $param = array(
            'Action'               => 'CreateDBInstance',
            'DBInstanceIdentifier' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    }

}
