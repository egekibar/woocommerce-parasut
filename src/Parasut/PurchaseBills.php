<?php

namespace Plugin\Parasut;

class PurchaseBills {
	public $connector;

	/**
	 * Invoices constructor.
	 *
	 * @param Authorization $connector
	 */
	public function __construct( Authorization $connector ) {
		$this->connector = $connector;
	}

	/**
	 * @param int $page
	 * @param int $size
	 *
	 * @return array|\stdClass
	 */
	public function list_purchase_bills( $page = 1, $size = 25 ) {
		return $this->connector->request(
			"purchase_bills?page[number]=$page&page[size]=$size",
			[],
			'GET'
		);
	}

	/**
	 * @return mixed
	 */
	public function count_purchase_bills() {
		return $this->connector->request(
			"purchase_bills?page[number]=1&page[size]=2",
			[],
			'GET'
		)->result->meta->total_count;
	}

	/**
	 * @param array $data
	 *
	 * @return array|\stdClass
	 */
	public function search( $data = [] ) {
		$filter = null;
		foreach ( $data as $key => $value ) {
			if ( end( $data ) == $value ) {
				$filter .= "filter[$key]=" . urlencode( $value );
			} else {
				$filter .= "filter[$key]=" . urlencode( $value ) . "&";
			}
		}

		return $this->connector->request(
			"purchase_bills?$filter",
			[],
			'GET'
		);
	}

	/**
	 * @param $invoice_id
	 *
	 * @return array|\stdClass
	 */
	public function show( $invoice_id ) {
		return $this->connector->request(
			"purchase_bills/$invoice_id?include=active_e_document,contact,details.product",
			[],
			'GET'
		);
	}


	/**
	 * @param $data
	 *
	 * @return array|\stdClass
	 */
	public function create( $data ) {
		return $this->connector->request(
			"purchase_bills?include=active_e_document",
			$data,
			'POST'
		);
	}

	/**
	 * @param $invoice_id
	 * @param $data
	 *
	 * @return array|\stdClass
	 */
	public function edit( $invoice_id, $data ) {
		return $this->connector->request(
			"purchase_bills/$invoice_id",
			$data,
			'PUT'
		);
	}

	/**
	 * @param $invoice_id
	 *
	 * @return array|\stdClass
	 */
	public function delete( $invoice_id ) {
		return $this->connector->request(
			"purchase_bills/$invoice_id",
			[],
			'DELETE'
		);
	}

	/**
	 * @param $invoice_id
	 *
	 * @return array|\stdClass
	 */
	public function cancel( $invoice_id ) {
		return $this->connector->request(
			"purchase_bills/$invoice_id/cancel",
			[],
			'DELETE'
		);
	}
}
