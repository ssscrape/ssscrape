// Get the url of the current request. The shorter
// Request::url() method does exactly the same as
// Request::relative_url(), but saves you a bit of typing.
// Example result: /path/to/page
$url = Request::url();
$url = Request::relative_url();

// This yields the canonical url. Example:
// http://example.com/path/to/page
$url = Request::canonical_url();

// Find out the request method:
$method = Request::method();    // "GET" or "POST"
$somevar = Request::is_get();   // true or false
$somevar = Request::is_post();  // true or false
