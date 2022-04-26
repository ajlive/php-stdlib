<?php

namespace http;

interface Handler
{
	public function serve(ResponseWriter $w, Request $r): void;
}
