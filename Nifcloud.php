<?php
// vim: set et ts=4 sw=4 sts=4:
require_once 'Nifcloud/List.php';
require_once 'Nifcloud/Firewall.php';
require_once 'Nifcloud/MultiLoadBalancer.php';
require_once 'Nifcloud/LoadBalancer.php';
require_once 'Nifcloud/PrivateLan.php';
require_once 'Nifcloud/Server.php';
require_once 'Nifcloud/SshKey.php';
require_once 'Nifcloud/Disk.php';


class Nifcloud
{
    private $ak;
    private $sk;
    private $disp;

    public function __construct($ak, $sk, $disp = true)
    {
        $this->ak   = $ak;
        $this->sk   = $sk;
        $this->disp = $disp;
    }

    private function disp($msg, $lf = true)
    {
        if ($this->disp) {
            echo $msg;
            if ($lf) {
                echo "\n";
            }
        }
    }

    public function createSshKey($zone, $keyName, $param)
    {
        $key = new Nifcloud_SshKey($this->ak, $this->sk, $zone, $keyName);
        if ($key->exists()) {
            $this->disp("{$keyName} は作成済です");
            return null;
        } else {
            $this->disp("{$keyName} を作成します");
            $res = $key->create($param);
            if ($res === false) {
                $this->disp($key->getLastErrorMsg());
                return false;
            }
            $this->disp("{$keyName} を作成しました");
            return $key->getKey();
        }
        return true;
    }

    public function createServers($zone, $servers)
    {
        $res = false;
        foreach ($servers as $srvName => $conf) {
            $res = $this->createServer($zone, $srvName, $conf);
            if (!$res) {
                break;
            }
        }
        return $res;
    }

    public function createServer($zone, $srvName, $conf)
    {
        $nic = array();
        if (isset($conf['global']) && $conf['global']) {
            $nic[] = array(
                'NetworkId' => 'net-COMMON_GLOBAL',
            );
        }
        if (!empty($conf['vlan'])) {
            $nic[] = array(
                'NetworkName' => $conf['vlan'],
                'IpAddress'   => 'static',
            );
        } else {
            $nic[] = array(
                'NetworkId' => 'net-COMMON_PRIVATE',
            );
        }
    
        $param = array(
            'ImageId' => isset($conf['imageid']) ? $conf['imageid'] : 183, //CentOS 7.6
            'KeyName' => $conf['keyname'],
            'SecurityGroup.1' => $conf['firewall'],
            'InstanceType' => $conf['type'],
            'Placement.AvailabilityZone' => $zone,
            'DisableApiTermination' => 'true',
            'AccountingType' => isset($conf['accountingtype']) ? $conf['accountingtype'] : 2,
            'NetworkInterface' => $nic,
        );
        
        if (isset($conf['script']) && file_exists($conf['script'])) {
            $script = file_get_contents($conf['script']);
            $script = str_replace('SERVERNAME', $srvName, $script);
            if (isset($conf['ipaddr6'])) {
                $script = str_replace('IPADDRESS6', $conf['ipaddr6'], $script);
            }
            if (isset($conf['ipaddr'])) {
                $script = str_replace('IPADDRESS', $conf['ipaddr'], $script);
            }
            if (isset($conf['global']) && !$conf['global']) {
                $script = str_replace('SETYUMPROXY', 'true', $script);
            }
            $param['UserData'] = base64_encode($script);
        }
    
        $srv = new Nifcloud_Server($this->ak, $this->sk, $zone, $srvName);
        if ($srv->exists()) {
            $this->disp("{$srvName} は作成済です");
        } else {
            $this->disp("{$srvName} を作成します");
            $res = $srv->create($param);
            if ($res === false) {
                $this->disp($srv->getLastErrorMsg());
                return false;
            }
            if (!empty($conf['waitcomp'])) {
                // 作成完了まで待つ
                do {
                    sleep(2);
                    $res = $srv->getStatus(true);
                    $this->disp('.', false);
                } while($res === 'pending');
                $this->disp('done');
            }
            $this->disp("{$srvName} を作成しました");
        }
        return true;
    }

    public function createFirewall($zone, $fwName, $rules)
    {
        $fw = new Nifcloud_Firewall($this->ak, $this->sk, $zone, $fwName);
        if ($fw->exists()) {
            $this->disp("{$fwName} は作成済です");
        } else {
            $this->disp("{$fwName} を作成します");
            $res = $fw->create();
            if ($res === false) {
                $this->disp($fw->getLastErrorMsg());
                return false;
            }
            if (!empty($rules)) {
                //  設定するルールがある場合はステータスがprocessingの間待つ
                do {
                    sleep(2);
                    $res = $fw->getStatus(true);
                    $this->disp('.', false);
                } while($res === 'processing');
                $this->disp('done');
            }
            $this->disp("{$fwName} を作成しました");
        }
        if (!empty($rules)) {
            return $this->setFwRules($fw);
        }
        return true;
    }

    public function setFirewallRules($zone, $fwName, $rules)
    {
        $fw = new Nifcloud_Firewall($this->ak, $this->sk, $zone, $fwName);
        if ($fw->exists()) {
            return $this->setFwRules($fw);
        }
    }

    private function setFwRules($fw)
    {
        $this->disp("{$fwName} にルールを追加します");
        // ルール設定
        $res = $fw->setRules($rules);
        //var_dump('set rule', $res);
        if ($res === false) {
            $this->disp($fw->getLastErrorMsg());
            return false;
        }
        $this->disp("{$fwName} にルールを追加しました");
        return true;
    }

    public function getFirewallLog($zone, $fwName, $date = null, $start = null, $end =null)
    {
        $fw = new Nifcloud_Firewall($this->ak, $this->sk, $zone, $fwName);
        if ($fw->exists()) {
            $this->disp("{$fwName} のログを取得します");
            $res = $fw->getLog($date, $start, $end);
            if ($res === false) {
                $this->disp($fw->getLastErrorMsg());
                return false;
            }
            $this->disp("{$fwName} のログを取得しました");
        }
        $res = $fw->getRes();
        return isset($res['log']) ? $res['log'] : null;
    }

    public function createPrivateLan($zone, $vlanName, $param)
    {
        $pvl = new Nifcloud_PrivateLan($this->ak, $this->sk, $zone, $vlanName);
        if ($pvl->exists()) {
            $this->disp("{$vlanName} は作成済です");
        } else {
            $this->disp("{$vlanName} を作成します");
            $res = $pvl->create($param);
            if ($res === false) {
                $this->disp($pvl->getLastErrorMsg());
                return false;
            }
            // ステータス確認
            do {
                sleep(2);
                $res = $pvl->getStatus(true);
                $this->disp('.', false);
            } while($res === 'pending');
            $this->disp('done');
            $this->disp("{$vlanName} を作成しました");
        }
        return true;
    }

    public function createMultiLoadBalancer($zone, $mlbName, $param)
    {
        $mlb = new Nifcloud_MultiLoadBalancer($this->ak, $this->sk, $zone, $mlbName);
        if ($mlb->exists()) {
            $this->disp("{$mlbName} は作成済です");
        } else {
            $this->disp("{$mlbName} を作成します");
            $res = $mlb->create($param);
            if ($res === false) {
                // 失敗
                $this->disp($mlb->getLastErrorMsg());
                return false;
            }
            // ステータス確認
            do {
                sleep(2);
                $res = $mlb->getStatus(true);
                $this->disp('.', false);
            } while($res === 'pending');
            $this->disp('done');
            $this->disp("{$mlbName} を作成しました");
        }
        return true;
    }

    public function registMultiLoadBalancer($zone, $mlbName, $params)
    {
        $mlb = new Nifcloud_MultiLoadBalancer($this->ak, $this->sk, $zone, $mlbName);
        if ($mlb->exists()) {
            $this->disp("{$mlbName} にサーバを登録します");
            foreach ($params as $param) {
                $res = $mlb->regist($param);
                if ($res === false) {
                    // 失敗
                    $this->disp($mlb->getLastErrorMsg());
                    return false;
                }
                do {
                    sleep(2);
                    $res = $mlb->getStatus(true);
                    $this->disp('.', false);
                } while($res === 'processing');
                $this->disp('done');
            }
            $this->disp("{$mlbName} にサーバを登録しました");
        }
        return true;
    }

    public function createLoadBalancer($zone, $lbName, $param, $filter = null)
    {
        $lb = new Nifcloud_LoadBalancer($this->ak, $this->sk, $zone, $lbName);
        if ($lb->exists()) {
            $this->disp("{$lbName} は作成済です");
        } else {
            $this->disp("{$lbName} を作成します");
            $res = $lb->create($param);
            if ($res === false) {
                // 失敗
                $this->disp($lb->getLastErrorMsg());
                return false;
            }
            // ステータス確認
            do {
                sleep(2);
                $res = $lb->getStatus(true);
                $this->disp('.', false);
            } while($res === 'pending');
            $this->disp('done');
            $this->disp("{$lbName} を作成しました");
        }
        if ($filter !== null) {
            return $this->filterLoadBalancer($zone, $lbName, $filter);
        }
        return true;
    }

    public function registLoadBalancer($zone, $lbName, $params)
    {
        $lb = new Nifcloud_LoadBalancer($this->ak, $this->sk, $zone, $lbName);
        if ($lb->exists()) {
            $this->disp("{$lbName} にサーバを登録します");
            foreach ($params as $param) {
                $res = $lb->regist($param);
                if ($res === false) {
                    // 失敗
                    $this->disp($lb->getLastErrorMsg());
                    return false;
                }
                do {
                    sleep(2);
                    $res = $lb->getStatus(true);
                    $this->disp('.', false);
                } while($res === 'processing');
                $this->disp('done');
            }
            $this->disp("{$lbName} にサーバを登録しました");
        }
        return true;
    }

    public function filterLoadBalancer($zone, $lbName, $params)
    {
        $lb = new Nifcloud_LoadBalancer($this->ak, $this->sk, $zone, $lbName);
        if ($lb->exists()) {
            $this->disp("{$lbName} にフィルタを登録します");
            $res = $lb->setFilter($params);
            if ($res === false) {
                // 失敗
                $this->disp($lb->getLastErrorMsg());
                return false;
            }
            $this->disp("{$lbName} にフィルタを登録しました");
        }
        return true;
    }

    public function createDisk($zone, $diskName, $param)
    {
        $disk = new Nifcloud_Disk($this->ak, $this->sk, $zone, $diskName);
        if ($disk->exists()) {
            $this->disp("{$diskName} は作成済です");
        } else {
            $this->disp("{$diskName} を作成します");
            $res = $disk->create($param);
            if ($res === false) {
                $this->disp($disk->getLastErrorMsg());
                return false;
            }
            if (!empty($conf['waitcomp'])) {
                // 作成完了まで待つ
                do {
                    sleep(2);
                    $res = $disk->getStatus(true);
                    $this->disp('.', false);
                } while($res === 'pending');
                $this->disp('done');
            }
            $this->disp("{$diskName} を作成しました");
        }
        return true;
    }
}
