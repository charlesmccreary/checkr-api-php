<?php
// Checkr Background Check API

class Checkr_API {
	public  $curl_info = [];
	public  $error_message = '';
	private $production_mode = false;
	private $staging_hostname = 'api.checkr-staging.com';
	private $live_hostname = 'api.checkr.com';
	private $staging_api_key = '';
	private $live_api_key = '';

	public function __construct($production_mode = null, $api_key = null) {
		if ($production_mode !== null) $this->set_production_mode($production_mode);
		if ($api_key !== null) $this->set_api_key($api_key);
	}

	public function set_production_mode($production_mode) {
		$this->production_mode = ($production_mode) ? true : false;
	}

	public function set_api_key($api_key) {
		if ($this->production_mode) {
			$this->live_api_key = $api_key;
		} else {
			$this->staging_api_key = $api_key;
		}
	}

	private function api_request($endpoint, $post_data = []) {
		$hostname = ($this->production_mode) ? $this->live_hostname : $this->staging_hostname;
		$api_key = ($this->production_mode) ? $this->live_api_key : $this->staging_api_key;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://' . $hostname . '/' . ltrim($endpoint, '/'));

		if ($post_data) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
		}

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERNAME, $api_key);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);

		$response = curl_exec($ch);
		$this->curl_info = curl_getinfo($ch);
		$this->error_message = (curl_errno($ch)) ? curl_errno($ch) . ': ' . curl_error($ch) : '';

		curl_close($ch);
		return $response;
	}

	public function create_candidate($email, $phone = '', $first_name = '', $last_name = '', $middle_name = '') {
		$data = [];
		$data['email'] = $email;
		if ($phone) $data['phone'] = $phone;
		if ($first_name) $data['first_name'] = $first_name;
		if ($last_name) $data['last_name'] = $last_name;

		if ($middle_name) { $data['middle_name'] = $middle_name; }
		elseif ($first_name && $last_name && !$middle_name) { $data['no_middle_name'] = true; }

		$response = $this->api_request('/v1/candidates', $data);
		if (!$response) return null;
		return json_decode($response, true); // id
	}

	public function create_invitation($candidate_id, $state = '', $city = '', $country = 'US', $package = 'essential_criminal', $node = 'primary') {
		$data = [];
		$data['package'] = $package;
		$data['candidate_id'] = $candidate_id;
		if ($node) $data['node'] = $node;

		$work_locations = [];
		if ($country == 'US') {
			if ($state) {
				$work_locations['country'] = 'US';
				$work_locations['state'] = $state;
				if ($city) $work_locations['city'] = $city;
			}
		} else {
			$work_locations['country'] = $country;
		}

		if ($work_locations) $data['work_locations'][] = $work_locations;

		$response = $this->api_request('/v1/invitations', $data);
		if (!$response) return null;
		return json_decode($response, true); // invitation_id, invitation_url, report_id
	}

	public function get_invitation($invitation_id) {
		$response = $this->api_request('/v1/invitations/' . $invitation_id);
		if (!$response) return null;
		return json_decode($response, true); // status, invitation_url, report_id
	}

	public function get_report($report_id) {
		$response = $this->api_request('/v1/reports/' . $report_id);
		if (!$response) return null;
		return json_decode($response, true); // status, result
	}
}

?>
