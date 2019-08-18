<?php
class ControllerExtensionPaymentPP extends Controller {
	private $error = array();
	
	public function index() {
		$this->load->language('extension/payment/pp');
				
		$data['environment'] = $this->config->get('payment_pp_environment');
						
		$data['order_id'] = $this->session->data['order_id'];
		
		return $this->load->view('extension/payment/pp', $data);
	}
	
	public function send() {					
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$total_price = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$currency_code = $order_info['currency_code'];

		$client_id = $this->config->get('payment_pp_client_id');
		$secret = $this->config->get('payment_pp_secret');
		$environment = $this->config->get('payment_pp_environment');
		$transaction_method = $this->config->get('payment_pp_transaction_method');	

		$token_info = array(
			'grant_type' => 'client_credentials'
		);	
				
		$order_info = array(
			'intent' => strtoupper($transaction_method),
			'purchase_units' => array(
				array(
					'amount' => array(
						'currency_code' => $currency_code,
						'value' => $total_price
					)
				)
			)
		);
				
		require_once DIR_SYSTEM .'library/pp/pp.php';
		
		$paypal = new PayPal($client_id, $secret, $environment);
				
		$paypal->setAccessToken($token_info);
		
		$result = $paypal->setOrder($order_info);
			
		$data['order_id'] = '';
		
		if (isset($result['id'])) {
			$data['order_id'] = $result['id'];
		}
		
		if ($paypal->hasErrors()) {
			$this->error['warning'] = implode(' ', $paypal->getErrors());
		}
				
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function confirm() {		
		if (isset($this->request->post['order_id'])) {
			$client_id = $this->config->get('payment_pp_client_id');
			$secret = $this->config->get('payment_pp_secret');
			$environment = $this->config->get('payment_pp_environment');
			$transaction_method = $this->config->get('payment_pp_transaction_method');
			
			$token_info = array(
				'grant_type' => 'client_credentials'
			);	
			
			$order_id = $this->request->post['order_id'];
			
			require_once DIR_SYSTEM .'library/pp/pp.php';
		
			$paypal = new PayPal($client_id, $secret, $environment);
						
			$paypal->setAccessToken($token_info);
		
			if ($transaction_method == 'authorize') {
				$result = $paypal->setOrderAuthorize($order_id);
			} else {
				$result = $paypal->setOrderCapture($order_id);
			}
			
			if ($paypal->hasErrors()) {
				$this->error['warning'] = implode(' ', $paypal->getErrors());
			}
		}
		
		if (!$this->error) {
			$this->load->model('checkout/order');

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_pp_order_status_id'));
			
			$data['success'] = $this->url->link('checkout/success');
		}
		
		$data['error'] = $this->error;
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));		
	}
}