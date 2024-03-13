<?php

namespace Plugin\Parasut;

class Tags {
	public $connector;

	/**
	 * Categories constructor.
	 * @param Authorization $connector
	 */
	public function __construct(Authorization $connector)
	{
		$this->connector = $connector;
	}

	/**
	 * @param int $page
	 * @param int $size
	 * @return array|\stdClass
	 */
	public function list_tags($page = 1, $size = 25)
	{
		return $this->connector->request(
			"tags?page[number]=$page&page[size]=$size",
			[],
			"GET"
		);
	}

	/**
	 * @return mixed
	 */
	public function count_tags()
	{
		return $this->connector->request(
			"tags?page[number]=1&page[size]=2",
			[],
			"GET"
		)->result->meta->total_count;
	}

	/**
	 * @param $tag_id
	 * @return array|\stdClass
	 */
	public function show($tag_id)
	{
		return $this->connector->request(
			"tags/$tag_id?include=parent_tag,subtags",
			[],
			"GET"
		);
	}

	/**
	 * @param array $data
	 * @return array|\stdClass
	 */
	public function search($data)
	{
		$filter = null;
		foreach ($data as $key => $value)
		{
			if (end($data) == $value)
				$filter .= "filter[$key]=".urlencode($value);
			else
				$filter .= "filter[$key]=".urlencode($value)."&";
		}

		return $this->connector->request(
			"tags?$filter",
			[],
			"GET"
		);
	}

	/**
	 * @param $data
	 * @return array|\stdClass
	 */
	public function create($data)
	{
		return $this->connector->request(
			"tags",
			$data,
			"POST"
		);
	}

	/**
	 * @param $tag_id
	 * @param array $data
	 * @return array|\stdClass
	 */
	public function edit($tag_id, $data = [])
	{
		return $this->connector->request(
			"tags/$tag_id",
			$data,
			"PUT"
		);
	}

	/**
	 * @param $tag_id
	 * @return array|\stdClass
	 */
	public function delete($tag_id)
	{
		return $this->connector->request(
			"item_tags/$tag_id",
			[],
			"DELETE"
		);
	}
}