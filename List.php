<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_List extends Nifcloud_API
{
    protected $name = null;

    public function __construct($accessKey, $secretKey, $zone)
    {
        parent::__construct($accessKey, $secretKey, $zone);
    }

    public function getServerList()
    {
        $url   = '';
        $param = array(
            'Action'       => 'DescribeInstances',
        );

        $this->call($url, $param);
        if ($this->isError()) {
            return false;
        }

        $res = array();
        if (isset($this->res['reservationSet']['item'])) {
            if (isset($this->res['reservationSet']['item']['reservationId'])) {
                $res[] = $this->res['reservationSet']['item'];
            } else {
                $res = $this->res['reservationSet']['item'];
            }
        }
        return $res;
    }
}
