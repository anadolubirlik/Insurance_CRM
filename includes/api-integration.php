<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Insurance_CRM_API_Integration {
    private $api_url;
    private $api_key;
    private $api_secret;
    
    public function __construct() {
        $this->api_url = get_option('insurance_crm_api_url', '');
        $this->api_key = get_option('insurance_crm_api_key', '');
        $this->api_secret = get_option('insurance_crm_api_secret', '');
    }
    
    public function verify_policy($policy_number, $customer_tc) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('api_not_configured', 'API ayarları yapılandırılmamış.');
        }
        
        $endpoint = '/verify-policy';
        $data = [
            'policy_number' => $policy_number,
            'customer_tc' => $customer_tc
        ];
        
        return $this->make_request('POST', $endpoint, $data);
    }
    
    public function get_policy_details($policy_number) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('api_not_configured', 'API ayarları yapılandırılmamış.');
        }
        
        $endpoint = '/policy-details/' . urlencode($policy_number);
        return $this->make_request('GET', $endpoint);
    }
    
    public function create_policy($policy_data) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('api_not_configured', 'API ayarları yapılandırılmamış.');
        }
        
        $endpoint = '/create-policy';
        return $this->make_request('POST', $endpoint, $policy_data);
    }
    
    private function make_request($method, $endpoint, $data = null) {
        $url = rtrim($this->api_url, '/') . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if ($data !== null) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);
        
        if ($status !== 200) {
            return new WP_Error(
                'api_error',
                sprintf('API isteği başarısız oldu (HTTP %d): %s', $status, $body)
            );
        }
        
        return json_decode($body);
    }
}