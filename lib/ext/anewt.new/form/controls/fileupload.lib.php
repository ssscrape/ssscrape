<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Extended file upload form control with some automatic file and error handling.
 */
class AnewtFormControlFileUpload extends AnewtFormControl
{
	/**
	 * Create a new file upload control.
	 *
	 * \param $name
	 *   The name of this control.
	 *
	 * \param $uploaddir
	 *   The directory to upload the files to.
	 */
	function __construct($name, $uploaddir)
	{
		parent::__construct($name);
		$this->_seed(array(
			'value'      => '',
			'size'       => null,
			'maxlength'  => null,
			'max_file_size' => null,
			'uploaddir'  => $uploaddir,
			'show-img-preview' => false,	// Shows a preview of the current image. 'preview-dir' must also be set.
			'show-link-preview' => false,	// Shows a link to the target
			'preview-dir' => false,
			'add-timestamp' => false,
			'ensure-unique' => true,	// Makes sure the filename is unique by adding numbered postfixes to the filename.
			'remove-label' => '',		// If set to non-empty string, shows a checkbox for removal with this label.
			'delete-on-change' => false,	// Physically deletes file on removal or replacement.
							// Use with caution and perhaps only with 'add-timestamp' or 'ensure-unique'.
		));
	}

	function build_widget()
	{
		$value = $this->_get('value');
		if (is_null($value))
			$value = "";

		assert('is_string($value); // only plain strings can be used as field value: type: '. gettype($value));

		$name = $this->get('name');

		$widgets = array();

		if ($this->get('show-img-preview') && $value) {
			$widgets[] = ax_div_class(ax_img_src($this->get('preview-dir') . $value), 'preview');
		} elseif ($this->get('show-link-preview') && $value) {
			$widgets[] = ax_div_class(ax_a_href($value, $this->get('preview-dir') . $value), 'preview');
		}

		$remove_label = $this->_get('remove-label');
		if ($remove_label && $value) {
			$subattr = array('type' => 'checkbox', 'name' => $name . '-remove');
			$widgets[] = new AnewtXHTMLInput(null, $subattr);

			$subattr = array('for' => $name . '-remove');
			$widgets[] = new AnewtXHTMLLabel($remove_label, $subattr);

			$widgets[] = ax_br();
		}

		/* XML tag attributes used both for single line and multiline */

		$attr = array(
			'name'     => $this->get('name'),
			'id'       => $this->get('id'),
			'type'     => 'file',
		);

		if ($this->_get('readonly'))
			$attr['readonly'] = 'readonly';

		if ($this->_get('disabled'))
			$attr['disabled'] = 'disabled';

		$size = $this->_get('size');
		if (!is_null($size))
		{
			assert('is_int($size);');
			$attr['size'] = (string) $size;
		}

		$maxlength = $this->_get('maxlength');
		if (!is_null($maxlength))
		{
			assert('is_int($maxlength);');
			$attr['maxlength'] = (string) $maxlength;
		}

		$max_file_size = $this->_get('max_file_size');
		if (!is_null($max_file_size))
		{
			$subattr = array('type' => 'hidden', 'name' => 'MAX_FILE_SIZE', 'value' => (string)$max_file_size);
			$widgets[] = new AnewtXHMTLInput(null, $subattr);
		}

		$widget = new AnewtXHTMLInput(null, $attr);

		/* Styling */

		$widget->add_class('fileupload');

		if (!$this->_get('required'))
			$widget->add_class('optional');

		/* Optional extra class value */
		$class = $this->_get('class');
		if (!is_null($class))
			$widget->add_class($class);

		/* Help text, if any */
		$help = $this->_get('help');
		if (!is_null($help))
		{
			$help_text = to_string($help);
			$widget->set_attribute('title', $help_text);
			$widget->add_class('with-help');
		}

		$widgets[] = $widget;

		/* Add secondary label, if any */
		$secondary_label = $this->_get('secondary-label');
		if (!is_null($secondary_label))
			$widgets[] = $secondary_label;

		$out = ax_fragment($widgets);
		return $out;
	}

	/** \{
	 * \name Validation methods
	 */

	function is_valid()
	{
		$fi = $this->get('fileinfo');

		if (!is_null($fi)) {
			$error = $fi['error'];
			switch ($error) {
			case UPLOAD_ERR_OK:
			case UPLOAD_ERR_NO_FILE:
				$errormsg = '';
				break;
			case UPLOAD_ERR_INI_SIZE:
				$errormsg = "The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$errormsg = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
				break;
			case UPLOAD_ERR_PARTIAL:
				$errormsg = "The uploaded file was only partially uploaded.";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$errormsg = "Missing a temporary folder.";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$errormsg = "Failed to write file to disk";
				break;
			default:
				$errormsg = "Unknown File Error";
			}

			if( strlen($errormsg) > 0 ) {
				$this->set('error', $errormsg);
				$this->set('valid', false);
				return false;
			}
	 
		}

		/* Now get along with validators and perhaps other validation */
		return parent::is_valid();
	}

	/** \} */

	/**
	 * Returns information about the upload. This will be the contents of $_FILES[$control_name]
	 * or null if that does not exist.
	 */
	function get_fileinfo() {
		if (isset($_FILES[$this->get('name')]))
			return $_FILES[$this->get('name')];

		return null;
	}

	/**
	 * Returns the name of the file uploaded by the user.
	 */
	function get_filename() {
		$fi = $this->get('fileinfo');
		if (isset($fi) && $fi['error'] == UPLOAD_ERR_OK) {
			if ($this->get('add-timestamp')) {
				$filename = time() . $fi['name'];
			} elseif ($this->get('ensure-unique')) {
				$filename = AnewtFormControlFileUpload::make_unique($this->get('uploaddir'), $fi['name']);
				if (!$filename)
					trigger_error('Could not generate a unique filename for '.$fi['name']);
			} else {
				$filename = $fi['name'];
			}
			return $filename;
		}

		return null;
	}

	/**
	 * Process this fileupload. This will put the file in the $uploaddir directory.
	 */
	function process() {
		// Process pending file removal
		if ( $this->_isset('_remove') && $this->is_set('uploaddir') ) {
			$remove_file = URL::join($this->get('uploaddir'), $this->_get('_remove'));
			if (file_exists($remove_file))
				unlink($remove_file);
		}

                $fi = $this->get('fileinfo');

                $tmp_name = $fi['tmp_name'];
                $error = $fi['error'];

                if( $error == UPLOAD_ERR_NO_FILE )
                        return true;

		$name = $this->get('filename');

                if( $this->is_set('uploaddir') ) {

                        // move it to $uploaddir/$name
                        $destfile = URL::join($this->get('uploaddir'), $name);
                        if( move_uploaded_file($tmp_name, $destfile) ) {
				$this->set('value', $name);			// Re-set it, in case the removal of the old filename has reopened the position.
                                return true;
                        } else {
				$this->set('value', '');
				$this->set('error', 'Couldn\'t move file to upload location');
				$this->set('valid', false);
			}
                } else {
			$this->set('value', '');
			$this->set('error', 'Don\'t know where to move uploaded file to');
			$this->set('valid', false);
		}
                return false;
	}

	/**
	 * Set the value by the filename of the uploaded file, but only if a file is uploaded.
	 */
	function fill($values) {
		$filename = $this->get('filename');

		// Check for the 'remove' checkbox.
		$remove = array_has_key($values, $this->get('name') . '-remove');

		// If removed or replaced, mark file for deletion.
		if (($remove || $filename) && $this->get('delete-on-change') && $this->get('value')) {
			$this->_set('_remove', $this->get('value'));
		}

		// Unset the value if removed.
		if ($remove)
			$this->set('value', '');

		// Set new value if replaced.
		if ($filename)
			$this->set('value', $filename);

		return true;
	}

	/**
	 * Returns a filename which is unique in the given path.
	 * FIXME: might not be the proper place for this function.
	 *
	 * \param $path
	 *	The path where to check the file.
	 * \param $filename
	 *	The base filename.
	 * \param $max
	 *	The maximum number of iterations to try. Defaults to 99.
	 *
	 * \return
	 *	If the file doesn't already exist, it will return $filename,
	 *	otherwise it will return basename.N.ext where N is the lowest
	 *	value for which the a filename doesn't exist yet.
	 *
	 *	Will return false if N were to grow larger than $max.
	 */
	static function make_unique($path, $filename, $max=99) {
		if (!file_exists(URL::join($path, $filename)))
			return $filename;

		$dotloc = strrpos($filename, '.');
		if ($dotloc !== false) {
			$basename = substr($filename, 0, $dotloc);
			$ext = substr($filename, $dotloc);
		} else {
			$basename = $filename;
			$ext = '';
		}
		$n = 0;
		while ($n <= $max) {
			$filename = $basename.'.'.$n.$ext;
			if (!file_exists(URL::join($path, $filename)))
				return $filename;
			$n++;
		}
		return false;
	}
}

?>
