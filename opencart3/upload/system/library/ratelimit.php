<?php
/**
 * @package		Rate Limit
 * @author		Szabó Levente
 * @link		https://levente.net
 */
class RateLimit {

    /**
     * @var int
     */
    private $maxRequests;
    /**
     * @var int
     */
    private $interval;
    /**
     * @var string
     */
    private $dbFilePath = DIR_LOGS . "rate_limit.json";
    /**
     * @var int
     */
    private $maxFileSize = 10 * 1024 * 1024;

    public function __construct($maxRequests, $interval) {
        $this->maxRequests = $maxRequests;
        $this->interval = $interval;
        if (!file_exists($this->dbFilePath)) {
            file_put_contents($this->dbFilePath, json_encode([]));
        }
    }

    /**
     * @return bool
     */
    public function checkLimited() {
        if (filesize($this->dbFilePath) > $this->maxFileSize) { // It should never reach the limit. But if ever, we're deleting the db to avoid long processing time.
            file_put_contents($this->dbFilePath, '');
        }

        $dbData = json_decode(file_get_contents($this->dbFilePath), true) ?: [];
        $currentTime = time();
        $newDb = [];
        $ip = strtolower($this->getUserIP());
        $count = 0;

        foreach ($dbData as $record) {
            if ($record["time"] > ($currentTime - $this->interval)) { // Only keep the requests in the $interval so the db gets cleaned
                $newDb[] = $record;
                if ($record["ip"] === $ip) {
                    $count++;
                }
            }
        }

        if ($count < $this->maxRequests) {
            $newDb[] = ["ip" => $ip, "time" => $currentTime];
            file_put_contents($this->dbFilePath, json_encode($newDb), LOCK_EX);
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function getUserIP() {
        // Get real visitor IP behind CloudFlare network
        if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            return $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        // Check the X-Forwarded-For header (in case of multiple IPs, the first one is the real one)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        // Check HTTP_CLIENT_IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // Default is REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'];
    }
}
