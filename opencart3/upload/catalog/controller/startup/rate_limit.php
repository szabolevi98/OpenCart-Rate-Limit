<?php
class ControllerStartupRateLimit extends Controller {
    public function index() {
        if ($this->config->get("module_rate_limit_status")) {
            $maxRequests = $this->config->get("module_rate_limit_max_request");
            $interval = $this->config->get("module_rate_limit_interval");
            $this->registry->set('rate_limit', new RateLimit($maxRequests, $interval));
            if ($this->rate_limit->checkLimited()) {
                http_response_code(429);
                exit($this->config->get("module_rate_limit_message"));
            }
        }
    }
}
