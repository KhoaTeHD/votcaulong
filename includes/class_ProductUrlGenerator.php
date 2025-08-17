<?php

class ProductUrlGenerator {
	/**
	 * Tạo URL cho sản phẩm
	 *
	 * @param string $name Tên sản phẩm
	 * @param int $id ID sản phẩm
	 * @return string URL của sản phẩm
	 */
	public static function createProductUrl($name, $id) {
		$slug = sanitize_title($name); // Chuyển tên thành slug
		return home_url("$slug-i.$id");
	}

	/**
	 * Tạo URL cho danh mục
	 *
	 * @param string $name Tên danh mục
	 * @param int $id ID danh mục
	 * @return string URL của danh mục
	 */
	public static function createCategoryUrl($name, $id) {
		$slug = sanitize_title($name); // Chuyển tên thành slug
		return home_url("$slug-cat.$id");
	}

	/**
	 * Tạo URL cho sản phẩm với query parameters
	 *
	 * @param string $name Tên sản phẩm
	 * @param int $id ID sản phẩm
	 * @param array $args Tham số query (key => value)
	 * @return string URL của sản phẩm
	 */
	public static function createProductUrlWithQuery($name, $id, $args = []) {
		$url = self::createProductUrl($name, $id); // Lấy URL cơ bản
		if (!empty($args)) {
			$url = add_query_arg($args, $url); // Thêm query parameters
		}
		return $url;
	}

	/**
	 * Tạo URL cho danh mục với query parameters
	 *
	 * @param string $name Tên danh mục
	 * @param int $id ID danh mục
	 * @param array $args Tham số query (key => value)
	 * @return string URL của danh mục
	 */
	public static function createCategoryUrlWithQuery($name, $id, $args = []) {
		$url = self::createCategoryUrl($name, $id); // Lấy URL cơ bản
		if (!empty($args)) {
			$url = add_query_arg($args, $url); // Thêm query parameters
		}
		return $url;
	}
}
