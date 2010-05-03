// Load some modules
anewt_include('database');
anewt_include('xhtml');
anewt_include('textformatting');

// Load a submodule. Some modules do not load all
// their functionality by default. Make sure to
// load the parent module first!
anewt_include('validators');
anewt_include('validators/nl');
anewt_include('validators/nl/dutchzipcodevalidator');
