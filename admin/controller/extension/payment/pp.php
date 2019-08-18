<?php
class ControllerExtensionPaymentPP extends Controller {
	private $partner_id = 'KS4GNGV8UKZ9Y';
	private $partner_client_id = 'AfY5kpMlbIQPPYOO2fVTQer4_8hicGi97Rkh9UaBVlxXBAud_eEMbDBrQTjQpdPvfVRPh-GZp3uYuKQ5';
	private $error = array();
	
	public function index() {
		$this->load->language('extension/payment/pp');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_pp', $this->request->post);

			$this->session->data['success'] = $this->language->get('success_save');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
		
		if (isset($this->session->data['authorization_code']) && isset($this->session->data['shared_id']) && isset($this->session->data['seller_nonce'])) {
			$shared_id = $this->session->data['shared_id'];
			
			$token_info = array(
				'grant_type' => 'authorization_code',
				'code' => $this->session->data['authorization_code'],
				'code_verifier' => $this->session->data['seller_nonce']
			);
			
			require_once DIR_SYSTEM .'library/pp/pp.php';
					
			$paypal = new PayPal($shared_id);
			
			$data['token'] = $paypal->setAccessToken($token_info);
		
			$result = $paypal->getSellerCredentials($this->partner_id);
			
			if (isset($result['client_id']) && isset($result['client_secret'])) {
				$client_id = $result['client_id'];
				$secret = $result['client_secret'];
			}
		
			if ($paypal->hasErrors()) {
				$this->error['warning'] = implode(' ', $paypal->getErrors());
			}
			
			unset($this->session->data['authorization_code']);
			unset($this->session->data['shared_id']);
			unset($this->session->data['seller_nonce']);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['client_id'])) {
			$data['error_client_id'] = $this->error['client_id'];
		} else {
			$data['error_client_id'] = '';
		}

		if (isset($this->error['secret'])) {
			$data['error_secret'] = $this->error['secret'];
		} else {
			$data['error_secret'] = '';
		}
	
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extensions'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/pp', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['partner_id'] = $this->partner_id;
		$data['partner_client_id'] = $this->partner_client_id;
		$data['seller_nonce'] = token(50);
		
		$data['action'] = $this->url->link('extension/payment/pp', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
		$data['partner_url'] = str_replace('&amp;', '%26', $this->url->link('extension/payment/pp', 'user_token=' . $this->session->data['user_token'], true));
		$data['callback_url'] = str_replace('&amp;', '&', $this->url->link('extension/payment/pp/callback', 'user_token=' . $this->session->data['user_token'], true));
				
		if (isset($client_id)) {
			$data['payment_pp_client_id'] = $client_id;
		} elseif (isset($this->request->post['payment_pp_client_id'])) {
			$data['payment_pp_client_id'] = $this->request->post['payment_pp_client_id'];
		} else {
			$data['payment_pp_client_id'] = $this->config->get('payment_pp_client_id');
		}

		if (isset($secret)) {
			$data['payment_pp_secret'] = $secret;
		} elseif (isset($this->request->post['payment_pp_secret'])) {
			$data['payment_pp_secret'] = $this->request->post['payment_pp_secret'];
		} else {
			$data['payment_pp_secret'] = $this->config->get('payment_pp_secret');
		}
		
		if (isset($this->request->post['payment_pp_environment'])) {
			$data['payment_pp_environment'] = $this->request->post['payment_pp_environment'];
		} else {
			$data['payment_pp_environment'] = $this->config->get('payment_pp_environment');
		}
		
		if (isset($this->request->post['payment_pp_transaction_method'])) {
			$data['payment_pp_transaction_method'] = $this->request->post['payment_pp_transaction_method'];
		} else {
			$data['payment_pp_transaction_method'] = $this->config->get('payment_pp_transaction_method');
		}

		if (isset($this->request->post['payment_pp_total'])) {
			$data['payment_pp_total'] = $this->request->post['payment_pp_total'];
		} else {
			$data['payment_pp_total'] = $this->config->get('payment_pp_total');
		}

		if (isset($this->request->post['payment_pp_order_status_id'])) {
			$data['payment_pp_order_status_id'] = $this->request->post['payment_pp_order_status_id'];
		} else {
			$data['payment_pp_order_status_id'] = $this->config->get('payment_pp_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_pp_geo_zone_id'])) {
			$data['payment_pp_geo_zone_id'] = $this->request->post['payment_pp_geo_zone_id'];
		} else {
			$data['payment_pp_geo_zone_id'] = $this->config->get('payment_pp_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_pp_status'])) {
			$data['payment_pp_status'] = $this->request->post['payment_pp_status'];
		} else {
			$data['payment_pp_status'] = $this->config->get('payment_pp_status');
		}

		if (isset($this->request->post['payment_pp_sort_order'])) {
			$data['payment_pp_sort_order'] = $this->request->post['payment_pp_sort_order'];
		} else {
			$data['payment_pp_sort_order'] = $this->config->get('payment_pp_sort_order');
		}
		
		unset($this->session->data['payment_pp_client_id']);
		unset($this->session->data['payment_pp_secret']);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/pp', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/pp')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_pp_client_id']) {
			$this->error['client_id'] = $this->language->get('error_client_id');
		}

		if (!$this->request->post['payment_pp_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}
		
		return !$this->error;
	}

    public function callback() {
		if (isset($this->request->post['authorization_code']) && isset($this->request->post['shared_id']) && isset($this->request->post['seller_nonce'])) {
			$this->session->data['authorization_code'] = $this->request->post['authorization_code'];
			$this->session->data['shared_id'] = $this->request->post['shared_id'];
			$this->session->data['seller_nonce'] = $this->request->post['seller_nonce'];
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
    }
}