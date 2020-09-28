<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_Rdb extends Nifcloud_API
{
    protected $name = null;

    public function __construct($accessKey, $secretKey, $zone, $name)
    {
        parent::__construct($accessKey, $secretKey, $zone);
        $this->name = $name;
        //$this->setVersion(4);
    }

    protected function getEndpoint()
    {
        $endpoints = array(
            'east-1' => 'aws://jp-east-1.rdb.api.nifcloud.com/',
            'east-2' => 'aws://jp-east-2.rdb.api.nifcloud.com/',
            'east-3' => 'aws://jp-east-3.rdb.api.nifcloud.com/',
            'east-4' => 'aws://jp-east-4.rdb.api.nifcloud.com/',
            'west-1' => 'aws://jp-west-1.rdb.api.nifcloud.com/',
        );
        return isset($endpoints[$this->region]) ? $endpoints[$this->region] : null;
    }

    public function getRes()
    {
        return $this->res;
    }

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
}
