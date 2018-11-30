<?php
try {
    if ($CONFIG['DEV']) {
        if (PHP_OS === "WINNT") {
            class MemcacheWrap extends Memcache {
                public function set($key, $val, $expire = 0) {
                    return parent::set($key, $val, 0, $expire);
                }
            }
            $Memcached = new MemcacheWrap();
            $Memcached->connect($CONFIG["MEMCACHED"]["SERVER"], $CONFIG["MEMCACHED"]["PORT"]);
        } else {
            $Memcached = new Memcached();
            $Memcached->addServer($CONFIG["MEMCACHED"]["SERVER"], $CONFIG["MEMCACHED"]["PORT"]);
        }
    } else {
        $Memcached = new Memcached();
        $Memcached->addServer($CONFIG["MEMCACHED"]["SERVER"], $CONFIG["MEMCACHED"]["PORT"]);
    }
} catch (Exception $e) {
    echo base64_decode("c82ezKDNjcyvzLPNk8yZaM2AzY/MncyfzK3MusytzZnMr8y6ac2dzKfMtM2P0onNiMyjzJzMqcyvzLHMn82UzYnMpsyXzLzMncyszJ3MsMyzzKrMlnTNos2BzZ3Nn8ywzY3NmsyszKzMoMypzYXMqcyuzKbNls2UzLvMrc2JzKTMrsyfJ82fzY/NmsyszZTMmcyrzK3Mq8ytzLrMpnPMtc2hzK3MvMyvzJ0gzKfMt82PzLHMpcyYzKDNls2NzKDNhcyWzKBmzZ3Mm8y4zJ7MsMygzLHMn82TzKnNic2IdcybzJvNh8yjzJ7MpsyYzZPMu2PMuM2fzKLNiM2VzJ7Mr8yxzZTMnsyrzKTMsMywzKnNiMyya8ynzY/NocyhzZPMpMyrzKzMpsywzZXNk82TzZrMssyxzKnNms2IzZPMpGXNoM2ezY7MucykzLPMmMy7zJfMu8yqzKvNmc2aZM2ezY/Mp82YzLHMqsyYzLvNjcydIM2PzKDMqc2JzJbNmcyYzKnMo8y6zKrNiMyXzZnMscyWzY3MqnXNgc2dzaDMu8ygzY7Mu82VzLrNk8yezK7Mn8ykzJnMrc2UzKpwzLTMnsyczY3MqcyzzLnNic2azYXMs8yszKrMrcyfzLrMrsyWzKk=");
    die;
} catch (Error $e) {
    echo base64_decode("c82ezKDNjcyvzLPNk8yZaM2AzY/MncyfzK3MusytzZnMr8y6ac2dzKfMtM2P0onNiMyjzJzMqcyvzLHMn82UzYnMpsyXzLzMncyszJ3MsMyzzKrMlnTNos2BzZ3Nn8ywzY3NmsyszKzMoMypzYXMqcyuzKbNls2UzLvMrc2JzKTMrsyfJ82fzY/NmsyszZTMmcyrzK3Mq8ytzLrMpnPMtc2hzK3MvMyvzJ0gzKfMt82PzLHMpcyYzKDNls2NzKDNhcyWzKBmzZ3Mm8y4zJ7MsMygzLHMn82TzKnNic2IdcybzJvNh8yjzJ7MpsyYzZPMu2PMuM2fzKLNiM2VzJ7Mr8yxzZTMnsyrzKTMsMywzKnNiMyya8ynzY/NocyhzZPMpMyrzKzMpsywzZXNk82TzZrMssyxzKnNms2IzZPMpGXNoM2ezY7MucykzLPMmMy7zJfMu8yqzKvNmc2aZM2ezY/Mp82YzLHMqsyYzLvNjcydIM2PzKDMqc2JzJbNmcyYzKnMo8y6zKrNiMyXzZnMscyWzY3MqnXNgc2dzaDMu8ygzY7Mu82VzLrNk8yezK7Mn8ykzJnMrc2UzKpwzLTMnsyczY3MqcyzzLnNic2azYXMs8yszKrMrcyfzLrMrsyWzKk=");
    die;
}