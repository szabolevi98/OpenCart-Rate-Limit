<?php
/**
 * @package     Rate Limit
 * @version     v1.1.1
 * @author      Szabó Levente
 * @link        https://levente.net
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
    private $cachePath = DIR_CACHE . "rate_limit/";
    /**
     * @var int
     */
    private $cleanupChance = 100; // 1 in 100 chance to clean old files
    /**
     * @var int
     */
    private $cleanupThreshold = 60*60*24*7; // 7 day

    public function __construct($maxRequests, $interval) {
        $this->maxRequests = $maxRequests;
        $this->interval = $interval;
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * @return bool
     */
    public function checkLimited() {
        // Clean up old files
        if (rand(1, $this->cleanupChance) === 1) {
            $this->cleanupOldFiles();
        }

        // Store requests in json file with hashed IP as filename
        $ipHash = hash('sha256', $this->getUserIP());
        $filePath = $this->cachePath . $ipHash . '.json';
        $currentTime = time();
        $requests = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];

        // Remove expired requests
        $requests = array_filter($requests, function ($timestamp) use ($currentTime) {
            return $timestamp > ($currentTime - $this->interval);
        });

        // Check if the user reached the limit
        if (count($requests) < $this->maxRequests) {
            $requests[] = $currentTime;
            file_put_contents($filePath, json_encode(array_values($requests)), LOCK_EX);
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    private function cleanupOldFiles() {
        foreach (glob($this->cachePath . "*.json") as $file) {
            if (filemtime($file) < (time() - $this->cleanupThreshold)) {
                unlink($file);
            }
        }
    }

    /**
     * @return string
     */
    private function getUserIP() {
        if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            return $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }
}
