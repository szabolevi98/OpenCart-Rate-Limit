<?php
namespace Opencart\Admin\Controller\Extension\RateLimit\Module;
/**
 * Class RateLimit
 *
 * @package Opencart\Admin\Controller\Extension\RateLimit\Module
 */
class RateLimit extends \Opencart\System\Engine\Controller {
    /**
     * Index
     *
     * @return void
     */
    public function index(): void {
        $this->load->language('extension/rate_limit/module/rate_limit');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/rate_limit/module/rate_limit', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/rate_limit/module/rate_limit.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        if (isset($this->request->post['module_rate_limit_status'])) {
            $data['module_rate_limit_status'] = $this->request->post['module_rate_limit_status'];
        } else {
            $data['module_rate_limit_status'] = $this->config->get('module_rate_limit_status');
        }

        if (isset($this->request->post['module_rate_limit_message'])) {
            $data['module_rate_limit_message'] = $this->request->post['module_rate_limit_message'];
        } else if ($this->config->get('module_rate_limit_message')) {
            $data['module_rate_limit_message'] = $this->config->get('module_rate_limit_message');
        } else {
            $data['module_rate_limit_message'] = $this->language->get("text_rate_limit_message");
        }

        if (isset($this->request->post['module_rate_limit_max_request'])) {
            $data['module_rate_limit_max_request'] = $this->request->post['module_rate_limit_max_request'];
        } else if ($this->config->get('module_rate_limit_max_request')) {
            $data['module_rate_limit_max_request'] = $this->config->get('module_rate_limit_max_request');
        } else {
            $data['module_rate_limit_max_request'] = 75;
        }

        if (isset($this->request->post['module_rate_limit_interval'])) {
            $data['module_rate_limit_interval'] = $this->request->post['module_rate_limit_interval'];
        } else if ($this->config->get('module_rate_limit_interval')) {
            $data['module_rate_limit_interval'] = $this->config->get('module_rate_limit_interval');
        } else {
            $data['module_rate_limit_interval'] = 300;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/rate_limit/module/rate_limit', $data));
    }

    /**
     * Save
     *
     * @return void
     */
    public function save(): void {
        $this->load->language('extension/rate_limit/module/rate_limit');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/rate_limit/module/rate_limit')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('module_rate_limit', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Install
     *
     * @return void
     */
    public function install(): void {
        $this->load->model('setting/startup');
        $startup_data = [
            'code'        => 'rate_limit',
            'description' => 'OpenCart Rate Limit. You can set how many requests a user can make in a given interval before getting rate limited.',
            'action'      => 'catalog/extension/rate_limit/startup/rate_limit',
            'status'      => 1,
            'sort_order'  => 0
        ];
        $this->model_setting_startup->addStartup($startup_data);
    }

    /**
     * Uninstall
     *
     * @return void
     */
    public function uninstall(): void {
        $this->load->model('setting/startup');
        $this->model_setting_startup->deleteStartupByCode('rate_limit');
    }
}
