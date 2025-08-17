<?php
add_action('admin_menu', function () {
	add_submenu_page(
		'tools.php',
		'ERP API Health Check',
		'ERP API Health',
		'manage_options',
		'erp-api-health-check',
		'erp_api_health_check_page'
	);
});

function erp_api_health_check_page() {
	$client = new ERP_API_Client();

	$health = new ERP_API_HealthCheck($client);
	$results = $health->run();

	echo '<div class="wrap"><h1>ERP API Health Check</h1>';
	echo '<table class="widefat striped" style="max-width:900px">';
	echo '<thead><tr>
        <th>API</th>
        <th>Status</th>
        <th>Time (ms)</th>
        <th>Message / Data</th>
    </tr></thead><tbody>';
	foreach ($results as $api => $result) {
		$color = $result['status'] == 'PASS' ? 'green' : ($result['status']=='FAIL' ? 'red' : 'orange');
		echo '<tr>';
		echo "<td style='font-weight: bold;font-size: 1.2em;'>{$api}</td>";
		echo "<td style='color:{$color};font-weight:bold'>{$result['status']}</td>";
		echo '<td>' . ($result['time_ms'] ?? '-') . '</td>';
		echo '<td style="max-width:400px;word-break:break-all"><div style="max-height: 100px;overflow-y: auto">';
		if (!empty($result['message'])) echo esc_html($result['message']);
		elseif (!empty($result['sample_data'])) echo '<pre>' . esc_html(print_r($result['sample_data'],1)) . '</pre>';
		elseif (!empty($result['data'])) echo '<pre>' . esc_html(print_r($result['data'],1)) . '</pre>';
		echo '</div></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '</div>';
}
class ERP_API_HealthCheck {
	/** @var ERP_API_Client */
	private $client;
	private $results = [];

	public function __construct(ERP_API_Client $client) {
		$this->client = $client;
	}

	public function run() {
		$this->check('list_all_item_groups', function () {
			return $this->client->list_all_item_groups();
		});
		$this->check('browse_items', function () {
			return $this->client->browse_items(['limit_page_length' => 1]);
		});
		$this->check('get_product', function () {
			return $this->client->get_product('1703300012');
		});
		$this->check('list_address_locations', function () {
			return $this->client->list_address_locations(true);
		});
		$this->check('list_brands', function () {
			return $this->client->list_brands();
		});
		$this->check('search_item', function () {
			return $this->client->search_item(1,'vot cau long');
		});
		$this->check('get_filters', function () {
			return $this->client->get_filters();
		});
		return $this->results;
	}

	private function check($name, $callback) {
		$start = microtime(true);
		try {
			$result = call_user_func($callback);
			$duration = round((microtime(true) - $start) * 1000); // ms

			if (is_wp_error($result)) {
				$this->results[$name] = [
					'status' => 'FAIL',
					'message' => $result->get_error_message(),
					'data' => $result->get_error_data(),
					'time_ms' => $duration,
				];
			} else {
				$this->results[$name] = [
					'status' => 'PASS',
					'time_ms' => $duration,
					'sample_data' => is_array($result) ? array_slice($result, 0, 1) : $result,
				];
			}
		} catch (\Throwable $e) {
			$this->results[$name] = [
				'status' => 'ERROR',
				'message' => $e->getMessage(),
			];
		}
	}
}
