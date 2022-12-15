<?php
/**
 * Created by VSCode.
 * User: Simone Gosetto
 * Date: 14/08/2018
 * Time: 16:20
 **/

final class FD_Redis
{
    var Redis $redis;

    // costruttore con connessione automatica
    function __construct($host, $port = 6379)
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect($host, $port);
            return true;
        } catch (Exception $e) {
            //die($e->getMessage());
            return false;
        }
    }

    /* *******************
	 * PUBLIC
	 * *******************/

    /**
     * @throws RedisException
     */
    public function close(): void
    {
        $this->redis->close();
    }

    /**
     * @throws RedisException
     */
    public function exists($key): bool
    {
        return $this->redis->exists($key);
    }

    /**
     * @throws RedisException
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * @throws RedisException
     */
    public function queryCache($token, $process, $params, $result, $expire = 600): string {
        $params = hash("sha256", $params, false);
        $key = $token.":".$process.":".$params;
        $this->set($key, $result, $expire);
        return $key;
    }

    /**
     * @throws RedisException
     */
    public function queryGetFromCache($token, $process, $params): string {
        $params = hash("sha256", $params, false);
        return $this->get($token.":".$process.":".$params);
    }

    /**
     * @throws RedisException
     */
    public function set($key, $value, $expire = 600): void
    {
        $this->redis->set($key, $value);
        if ($expire > 0) {
            $this->redis->expire($key, $expire);
        }
    }

    /**
     * @throws RedisException
     */
    public function hgetall($key): array
    {
        return $this->redis->hgetall($key);
    }

    /**
     * @throws RedisException
     */
    public function del($key): void
    {
        $this->redis->del($key);
    }

    /* *******************
    * EXPIRY
    * *******************/

    /**
     * @throws RedisException
     */
    public function persist($key): void
    {
        $this->redis->persist($key);
    }

}
