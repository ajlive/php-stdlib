<?php

namespace http;

interface Server
{
	public function responseWriter(): ResponseWriter;
}
