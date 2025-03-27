<?php

namespace HeadlessWP\OpenAPI\Spec;

class Server {

	private string $url;

	public function __construct( $url ) {
		$this->url = $url;
	}

	public function toArray(): array {
		return array(
			'url' => $this->url,
		);
	}
}
