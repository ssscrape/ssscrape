// Get the url of the current request. The shorter
// AnewtRequest::url() method does exactly the same as
// AnewtRequest::relative_url(), but saves you a bit of typing.
// Example result: /path/to/page
$url = AnewtRequest::url();
$url = AnewtRequest::relative_url();

// This yields the canonical url. Example:
// http://example.com/path/to/page
$url = AnewtRequest::canonical_url();

// Find out the request method:
$method = AnewtRequest::method();    // "GET" or "POST"
$somevar = AnewtRequest::is_get();   // true or false
$somevar = AnewtRequest::is_post();  // true or false
