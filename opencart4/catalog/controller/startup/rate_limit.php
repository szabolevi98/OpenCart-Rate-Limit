<?php
namespace Opencart\Catalog\Controller\Extension\RateLimit\Startup;
/**
 * Class RateLimit
 *
 * @package Opencart\Catalog\Controller\Extension\RateLimit\Startup
 */
class RateLimit extends \Opencart\System\Engine\Controller {
    /**
     * Index
     *
     * @return void
     */
    public function index(): void {
        if ($this->config->get("module_rate_limit_status")) {
            $maxRequests = $this->config->get("module_rate_limit_max_request") ?? 75;
            $interval = $this->config->get("module_rate_limit_interval") ?? 300;
            $this->registry->set('rate_limit', new \Opencart\System\Library\Extension\RateLimit\RateLimit((int)$maxRequests, (int)$interval));
            if ($this->rate_limit->checkLimited()) {
                http_response_code(429);
                exit($this->config->get("module_rate_limit_message"));
            }
        }
    }
}
