<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_Dns extends Nifcloud_API
{
    protected $name = null;

    public function __construct($accessKey, $secretKey, $zone, $name)
    {
        parent::__construct($accessKey, $secretKey, $zone);
        $this->name = $name;
        $this->setVersion(3);
    }

    protected function isError()
    {
        return $this->res === null || isset($this->res['Error']);
    }

    public function getLastError()
    {
        return isset($this->res['Error']['Code']) ? $this->res['Error']['Code'] : 'unknown code';
    }

    public function getLastErrorMsg()
    {
        return isset($this->res['Error']['Message']) ? $this->res['Error']['Message'] : 'unknown error';
    }

    protected function getEndpoint()
    {
        return 'aws://dns.api.nifcloud.com/2012-12-12N2013-12-16/';
    }

    public function getRes()
    {
        return $this->res;
    }

    public function getZones()
    {
        $url   = 'hostedzone';
        $param = array(
        );

        $this->call($url, $param);
        if ($this->isError()) {
            return false;
        }

        $res = array();
        if (isset($this->res['HostedZones']['HostedZone'])) {
            $res = $this->res['HostedZones']['HostedZone'];
        }
        return $res;
    }

    public function getZone($zoneid)
    {
        $url   = "hostedzone/{$zoneid}";
        $param = array(
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function getRr($zoneid)
    {
        $url   = "hostedzone/{$zoneid}/rrset";
        $param = array(
        );

        $this->call($url, $param);
        if ($this->isError()) {
            return false;
        }

        $res = array();
        if (isset($this->res['ResourceRecordSets']['ResourceRecordSet'])) {
            if (isset($this->res['ResourceRecordSets']['ResourceRecordSet']['Name'])) {
                $res[] = $this->res['ResourceRecordSets']['ResourceRecordSet'];
            } else {
                $res = $this->res['ResourceRecordSets']['ResourceRecordSet'];
            }
        }
        return $res;
    }

    public function changeRr($zoneid, $rrs)
    {
        $url   = "hostedzone/{$zoneid}/rrset";

        $fmtXml = <<< EOS
<?xml version="1.0" encoding="UTF-8"?>
<ChangeResourceRecordSetsRequest xmlns="https://route53.amazonaws.com/doc/2012-12-12/">
  <ChangeBatch>
    <Changes>
        %s
    </Changes>
  </ChangeBatch>
</ChangeResourceRecordSetsRequest>
EOS;

        $fmtRrset = <<< EOS
<Change>
  <Action>%s</Action>
  <ResourceRecordSet>
    <Name>%s</Name>
    <Type>%s</Type>
    <TTL>%d</TTL>
    <ResourceRecords>
      <ResourceRecord>
        <Value>%s</Value>
      </ResourceRecord>
    </ResourceRecords>
    <XniftyComment>%s</XniftyComment>
  </ResourceRecordSet>
</Change>
EOS;

        $buf = array();
        foreach ($rrs as $rr) {
            $buf[] = sprintf($fmtRrset,
                $rr['action'],
                $rr['name'],
                $rr['type'],
                $rr['ttl'],
                $rr['value'],
                $rr['comment']
            );
        }
        $param = sprintf($fmtXml, implode("\n", $buf));

        $this->call($url, $param, null, true);
        return !$this->isError();
    }
}
