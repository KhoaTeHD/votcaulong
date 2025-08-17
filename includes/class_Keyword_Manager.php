<?php
class Keyword_Manager {
	private $table;
	private $wpdb;
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table = $wpdb->prefix . 'keywords';
		if (!$this->table_exists()) {
			my_theme_create_keywords_table();
		}
	}
	private function table_exists() {
		return $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table}'") == $this->table;
	}
	public function create($keyword, $count = 0) {
		global $wpdb;
		return $wpdb->insert(
			$this->table,
			[
				'keyword' => $keyword,
				'count'   => $count,
			],
			[ '%s', '%d' ]
		);
	}

	public function get($id_or_keyword) {
		global $wpdb;
		if (is_numeric($id_or_keyword)) {
			return $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id_or_keyword),
				ARRAY_A
			);
		} else {
			return $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$this->table} WHERE keyword = %s", $id_or_keyword),
				ARRAY_A
			);
		}
	}

	public function update($id_or_keyword, $data) {
		global $wpdb;
		$where = [];
		$where_format = [];
		if (is_numeric($id_or_keyword)) {
			$where['id'] = $id_or_keyword;
			$where_format[] = '%d';
		} else {
			$where['keyword'] = $id_or_keyword;
			$where_format[] = '%s';
		}
		return $wpdb->update(
			$this->table,
			$data,
			$where
		);
	}

	public function delete($id_or_keyword) {
		global $wpdb;
		if (is_numeric($id_or_keyword)) {
			return $wpdb->delete($this->table, [ 'id' => $id_or_keyword ], [ '%d' ]);
		} else {
			return $wpdb->delete($this->table, [ 'keyword' => $id_or_keyword ], [ '%s' ]);
		}
	}

	public function all($limit = 100, $offset = 0) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$this->table} ORDER BY updated_at DESC LIMIT %d OFFSET %d", $limit, $offset),
			ARRAY_A
		);
	}

	public function increment($keyword) {
		$row = $this->get($keyword);
		if ($row) {
			$this->update($keyword, [ 'count' => $row['count'] + 1 ]);
		} else {
			$this->create($keyword, 1);
		}
	}
	public function get_top($limit = 10, $min_count = 1) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE `count` >= %d ORDER BY `count` DESC, updated_at DESC LIMIT %d",
				$min_count, $limit
			),
			ARRAY_A
		);
	}
}
