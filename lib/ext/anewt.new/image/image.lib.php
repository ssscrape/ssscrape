<?php

/*
 * Anewt, Almost No Effort Web Toolkit, image module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Generic image class. This class supports loading (PNG, JPEG, GIF) and saving
 * of (PNG, JPEG) images. This class heavily uses the image functions from the
 * GD2 library bundled with PHP, but provides a decent API and many convenience
 * methods for loading, saving, resampling, resizing and drawing.
 */
class AnewtImage extends AnewtContainer
{
	/* Static methods */

	/**
	 * Load an image from a file. This method tries to detect the filetype
	 * automatically and will abort if the automatic detection failed.
	 *
	 * \param $filename
	 *   Filename of the image to load.
	 *
	 * \return
	 *   A new AnewtImage instance
	 */
	public static function from_file($filename)
	{
		assert('is_string($filename)');

		if (AnewtImage::_filename_looks_like_png($filename))
			$img = AnewtImage::from_png($filename);
		elseif (AnewtImage::_filename_looks_like_jpeg($filename))
			$img = AnewtImage::from_jpeg($filename);
		else
			trigger_error(sprintf(
				'AnewtImage::from_file(): Cannot load image, filename "%s" does not have a supported extension', $filename), E_USER_ERROR);

		return $img;
	}

	/**
	 * Load a PNG image from a file.
	 *
	 * \param $filename
	 *   Filename of the image to load.
	 *
	 * \return
	 *   A new AnewtImage instance
	 */
	public static function from_png($filename)
	{
		assert('is_string($filename)');
		$img = new AnewtImage(null, null, imagecreatefrompng($filename));
		$img->set('filename', $filename);
		return $img;
	}

	/**
	 * Load a JPEG image from a file.
	 *
	 * \param $filename
	 *   Filename of the image to load.
	 *
	 * \return
	 *   A new AnewtImage instance
	 */
	public static function from_jpeg($filename)
	{
		assert('is_string($filename)');
		$img = new AnewtImage(null, null, imagecreatefromjpeg($filename));
		$img->set('filename', $filename);
		return $img;
	}

	/**
	 * Load a GIF image from a file.
	 *
	 * \param $filename
	 *   Filename of the image to load.
	 *
	 * \return
	 *   A new AnewtImage instance
	 */
	public static function from_gif($filename)
	{
		assert('is_string($filename)');
		$img = new AnewtImage(null, null, imagecreatefromgif($filename));
		$img->set('filename', $filename);
		return $img;
	}

	/**
	 * Load an image from a string. The image type is automatically detected.
	 *
	 * \param $str
	 *   The str holding the image data.
	 *
	 * \return
	 *   A new AnewtImage instance
	 */
	public static function from_string($str)
	{
		assert('is_string($str)');
		$img = new AnewtImage(null, null, imagecreatefromstring($str));
		return $img;
	}

	/**
	 * Check if filename looks like a PNG filename.
	 *
	 * \param $filename
	 *   Filename to test.
	 *
	 * \return
	 *   True if the filename looks like a PNG filename, false otherwise.
	 */
	private static function _filename_looks_like_png($filename)
	{
		return str_has_suffix($filename, '.png')
			|| str_has_suffix($filename, '.PNG');
	}

	/**
	 * Check if filename looks like a JPEG filename.
	 *
	 * \param $filename
	 *   Filename to test.
	 *
	 * \return
	 *   True if the filename looks like a JPEG filename, false otherwise.
	 */
	private static function _filename_looks_like_jpeg($filename)
	{
		return str_has_suffix($filename, '.jpg')
			|| str_has_suffix($filename, '.JPG')
			|| str_has_suffix($filename, '.jpeg')
			|| str_has_suffix($filename, '.JPEG');
	}


	/* Instance variables */

	/**
	 * Image instance.
	 *
	 * Though not marked as such, this is a private variable that should
	 * only be accessed from within this module.
	 */
	public $__img = null;

	/**
	 * List of allocated colors
	 */
	private $__allocated_colors = array();


	/* Initialization */

	/**
	 * Create a new image instance. This method seems to accept three
	 * parameters, but you should really only provide the width and height. The
	 * third parameter is for internal usage only!
	 *
	 * \param $width
	 *   The width of the image (in pixels)
	 * \param $height
	 *   The height of the image (in pixels)
	 * \param $img
	 *   Internal parameter for private internal use. Do not provide this.
	 */
	public function __construct($width, $height, $img=null)
	{
		/* Special (null, null, img) invocation is used by static loading
		 * methods. This shouldn't be used from the outside. */
		if (is_null($width) && is_null($height))
		{
			assert('!is_null($img)');
			$this->__img = &$img;
			return;
		}

		/* Check input */
		assert('is_int($width) && ($width > 0)');
		assert('is_int($height) && ($height > 0)');

		/* Create a new image */
		$this->__img = imagecreatetruecolor($width, $height);

		/* Black by default */
		$this->fill($this->color_from_rgb(0, 0, 0));
	}

	/**
	 * Destroy this image and all allocated resources. Do not invoke any
	 * instance methods after this function has been called.
	 */
	public function destroy()
	{
		foreach ($this->__allocated_colors as $color)
			imagecolordeallocate($this->__img, $color);

		$this->__allocated_colors = array();
		imagedestroy($this->__img);
	}

	/**
	 * Default image quality. This is mainly used for JPEG images and returns
	 * the value 85.
	 * 
	 * \return
	 *   The number 85.
	 */
	protected function get_quality_()
	{
		return 85;
	}


	/* Dimension and resizing methods */

	/**
	 * Return dimensions of this image.
	 *
	 * \return
	 *   Array with (width, height) integer values.
	 */
	public function get_dimensions()
	{
		return array(imagesx($this->__img), imagesy($this->__img));
	}

	/**
	 * Set the image dimension to the specified width and height. Note that this
	 * crops the image from the top left corner.
	 *
	 * \param $width
	 *   The width of the image (in pixels)
	 * \param $height
	 *   The height of the image (in pixels)
	 *
	 * \see AnewtImage::crop
	 */
	public function set_dimensions($width, $height)
	{
		$this->crop(0, 0, $width, $height);
	}

	/**
	 * Crop the image to the specified dimensions.
	 *
	 * \param $x
	 *   The x coordinate of the rectangle to crop to (in pixels)
	 * \param $y
	 *   The y coordinate of the rectangle to crop to (in pixels)
	 * \param $width
	 *   The width of the rectangle to crop to (in pixels)
	 * \param $height
	 *   The height of the rectangle to crop to (in pixels)
	 *
	 * \see AnewtImage::set_dimensions
	 */
	public function crop($x, $y, $width, $height)
	{
		$new_img = imagecreatetruecolor($width, $height);
		imagecopy(
				$new_img, $this->__img,
				0, 0,
				$x, $y,
				$width, $height);
		imagedestroy($this->__img);

		$this->__img = $new_img;
	}

	/**
	 * Get the width of the current image.
	 *
	 * \return
	 *   Width of the image
	 */
	public function get_width()
	{
		return imagesx($this->__img);
	}

	/**
	 * Set the width of the current image. This crops the image if the new size
	 * is smaller than the current value.
	 *
	 * \param $new_width
	 *   New width of the image.
	 */
	public function set_width($new_width)
	{
		$this->set_dimensions($new_width, $this->get('height'));
	}

	/**
	 * Get the height of the current image.
	 *
	 * \return
	 *   Height of the image
	 */
	public function get_height()
	{
		return imagesy($this->__img);
	}

	/**
	 * Set the height of the current image. This crops the image if the new size
	 * is smaller than the current value.
	 *
	 * \param $new_height
	 *   New height of the image.
	 */
	public function set_height($new_height)
	{
		$this->set_dimensions($this->get('width'), $new_height);
	}

	/**
	 * Resize image to the specified width and height.
	 *
	 * \param $width
	 *   The new width
	 *
	 * \param $height
	 *   The new height
	 *
	 * \param $resample
	 *   Whether to use image resampling (default is true)
	 */
	public function resize($width, $height, $resample=null)
	{
		/* Default value (useful to have the default in one place instead of in
		 * all default arguments for all resize_* methods) */
		if (is_null($resample))
			$resample = true;

		/* Input checking */
		assert('is_numeric($width)');
		assert('is_numeric($height)');
		assert('is_bool($resample)');

		/* Clamp to positive integer values */
		$width = max((int) $width, 1);
		$height = max((int) $height, 1);

		$new_img = imagecreatetruecolor($width, $height);
		if ($resample)
			imagecopyresampled(
					$new_img, $this->__img,
					0, 0,
					0, 0,
					$width, $height,
					$this->get('width'), $this->get('height'));
		else
			imagecopyresized(
					$new_img, $this->__img,
					0, 0,
					0, 0,
					$width, $height,
					$this->get('width'), $this->get('height'));

		imagedestroy($this->__img);

		$this->__img = $new_img;
	}

	/**
	 * Resize image relative to its current size
	 *
	 * \param $scale_factor
	 *   Scale factor. Values less than 0 shrink the image, values above
	 *   1 enlarge the image.
	 *
	 * \param $resample
	 *   Whether to use image resampling (default is true)
	 */
	public function resize_relative($scale_factor, $resample=null)
	{
		assert('is_numeric($scale_factor) && ($scale_factor > 0)');

		/* Calculate new width and height */
		$new_width = (int) ((float) $this->get('width') * $scale_factor);
		$new_height = (int) ((float) $this->get('height') * $scale_factor);

		/* Resize */
		$this->resize($new_width, $new_height, $resample);
	}

	/**
	 * Resize image relative to its current size by providing a percentage.
	 *
	 * \param $scale_percentage
	 *   Scale factor as percentage. Values less than 100 shrink the image, values above
	 *   100 enlarge the image.
	 *
	 * \param $resample
	 *   Whether to use image resampling (default is true)
	 */
	public function resize_percentage($scale_percentage, $resample=null)
	{
		assert('is_numeric($scale_percentage) && ($scale_percentage > 0)');
		$this->resize_relative($scale_percentage / 100.0, $resample);
	}

	/**
	 * Resize image to the specified width. The height is adjusted
	 * automatically, keeping the same aspect ratio.
	 *
	 * \param $width
	 *   The new width
	 *
	 * \param $resample
	 *   Whether to use image resampling (default is true)
	 */
	public function resize_width($width, $resample=null)
	{
		assert('is_int($width) && ($width > 0)');
		$height = (int) ($this->get('height') * $width / $this->get('width'));
		$this->resize($width, $height, $resample);
	}

	/**
	 * Resize image to the specified height. The width is adjusted
	 * automatically, keeping the same aspect ratio.
	 *
	 * \param $height
	 *   The new height
	 *
	 * \param $resample
	 *   Whether to use image resampling (default is true)
	 */
	public function resize_height($height, $resample=null)
	{
		assert('is_int($height) && ($height > 0)');
		$width = (int) ($this->get('width') * $height / $this->get('height'));
		$this->resize($width, $height, $resample);
	}

	/**
	 * Resize image so that it fits the specified dimensions.
	 * Aspect ratio is preserved.
	 *
	 * \param $width
	 *   The maximum width to fit in.
	 *
	 * \param $height
	 *   The maximum height to fit in.
	 *
	 * \param $scale_up
	 *   Whether to scale up if both existing dimensions are smaller than
	 *   the specified dimensions (default is false).
	 *
	 * \param $resample
	 *   Whether to use image resampling (default is true)
	 */
	public function resize_within($width, $height, $scale_up=null, $resample=null)
	{
		if (is_null($scale_up))
			$scale_up = false;

		assert('is_int($width) && $width > 0');
		assert('is_int($height) && $height > 0');
		assert('is_bool($scale_up)');

		if ($width > $this->get('width') && $height > $this->get('height') && !$scale_up)
			return;

		if ($width / $this->get('width') < $height / $this->get('height'))
			$this->resize_width($width, $resample);
		else
			$this->resize_height($height, $resample);
	}

	/* Color methods */

	/**
	 * Allocates a color in the current image.
	 *
	 * \param $red Red component value
	 * \param $green Green component value
	 * \param $blue Blue component value
	 * \param $alpha Alpha component value
	 *
	 * \return Allocated color value
	 */
	private function _allocate_color($red, $green, $blue, $alpha=null)
	{
		assert('is_int($red)');
		assert('is_int($green)');
		assert('is_int($blue)');

		if (is_null($alpha))
			$color = imagecolorallocate($this->__img, $red, $green, $blue);
		else
		{
			assert('is_int($alpha)');
			$color = imagecolorallocatealpha($this->__img, $red, $green, $blue, $alpha);
		}
		assert('$color !== -1');
		assert('$color !== false');

		$this->__allocated_colors[] = $color;
		return $color;
	}

	/* FIXME: Perhaps all operations that create a new GD image instance should
	 * trigger a reallocation of all currently allocated colors... I'm not
	 * totally convinced this is necessary for truecolor images, though. It
	 * definitely is for palette-based images, but they're not supported right
	 * now and support is not planned either. */
	/*
	private function _reallocate_colors()
	{
	}
	*/

	/**
	 * Parses a color from a string. Hexadecimal values (HTML/CSS format) are
	 * converted to a color value, e.g. \#ff0000 for red. The alpha channel
	 * (transparency) can be provided as well by adding two more letters, e.g.
	 * \#ff000012. Values consisting of only 3 letters are accepted as well and
	 * interpreted according to the same semantics as HTML/CSS uses.
	 *
	 * The leading # character is optional.
	 *
	 * \param $str
	 *   The string to parse.
	 *
	 * \return Allocated color value
	 *
	 * \see AnewtImage::color_from_rgb
	 * \see AnewtImage::color_from_rgba
	 */
	public function color_from_string($str)
	{
		assert('is_string($str)');
		$str = str_strip_prefix($str, '#');
		
		/* Accept CSS-valid values with only 3 letters as well */
		if (strlen($str) == 3)
			$str = join('', array(
				$str{0}, $str{0},
				$str{1}, $str{1},
				$str{2}, $str{2}));

		/* The string should have either 6 or 8 characters. */
		assert('(strlen($str)==6) || (strlen($str)==8)');

		$red = hexdec(substr($str, 0, 2));
		$green = hexdec(substr($str, 2, 2));
		$blue = hexdec(substr($str, 4, 2));

		if (strlen($str) == 6)
			$alpha = null;
		else
			$alpha = hexdec(substr($str, 6, 2));

		return $this->_allocate_color($red, $green, $blue, $alpha);
	}

	/**
	 * Allocates as color based on red, green and blue values. All values should
	 * be between 0 and 255.
	 *
	 * \param $red Red component value
	 * \param $green Green component value
	 * \param $blue Blue component value
	 *
	 * \return Allocated color value
	 *
	 * \see AnewtImage::color_from_string
	 * \see AnewtImage::color_from_rgba
	 */
	public function color_from_rgb($red, $green, $blue)
	{
		/* allocate_color() does type checking */
		return $this->_allocate_color($red, $green, $blue);
	}

	/**
	 * Allocates as color based on red, green, blue and alpha values. All values
	 * should be between 0 and 255.
	 *
	 * \param $red Red component value
	 * \param $green Green component value
	 * \param $blue Blue component value
	 * \param $alpha Alpha component value
	 *
	 * \return Allocated color value
	 *
	 * \see AnewtImage::color_from_string
	 * \see AnewtImage::color_from_rgb
	 */
	public function color_from_rgba($red, $green, $blue, $alpha)
	{
		/* allocate_color() does type checking */
		return $this->_allocate_color($red, $green, $blue, $alpha);
	}


	/**
	 * Convert a color to hexadecimal representation suitable for usage in HTML
	 * and CSS. The resulting string includes a leading # character.
	 *
	 * \param $color
	 *   The color to convert
	 *
	 * \return
	 *   String representation of the color
	 */
	public static function color_to_string($color)
	{
		assert('is_int($color)');
		/* FIXME: what to do with alpha values? */
		return sprintf('#%6x', $color);
	}

	/**
	 * Convert a color to a (red, green, blue) tuple.
	 *
	 * \param $color
	 *   The color to convert
	 *
	 * \return
	 *   Array with three integer values for the red, green and blue component.
	 */
	public static function color_to_rgb($color)
	{
		assert('is_int($color)');
		$red = ($color >> 16) & 0xFF;
		$green = ($color >> 8) & 0xFF;
		$blue = $color & 0xFF;

		/* FIXME: what to do with alpha values? */
		$alpha = ($color >> 24) & 0xFF; // FIXME: is this correct?

		return array($red, $green, $blue);
	}


	/* Drawing and morphing methods */

	/**
	 * Fills the complete image with the specified color. This sets the
	 * background of the image if called early, and overwrites the current image
	 * contents if called after any loading or drawing operations. Note that,
	 * when using a color with alpha blending, the current contents of the image
	 * are effectively colorized with the specified color.
	 *
	 * \param $color
	 *   The color to use. This color may include an alpha component.
	 */
	public function fill($color)
	{
		imagefilledrectangle(
			$this->__img,
			0, 0,
			$this->get('width') - 1, $this->get('height') - 1,
			$color);
	}

	/**
	 * Create a new drawing context for this image. You should set some
	 * properties and call drawing methods on the returned object instance to
	 * create dynamic images.
	 *
	 * \return
	 *   A new AnewtImageDrawingContext instance.
	 */
	public function create_drawing_context()
	{
		/* Lazy load the AnewtImageDrawingContext class, since it is not needed
		 * at all for many basic operations like image rescaling. */
		anewt_include('image/drawingcontext');
		$dc = new AnewtImageDrawingContext($this);
		return $dc;
	}


	/* Output methods */

	/**
	 * Save a previously loaded image back to disk.
	 *
	 * \see AnewtImage::save_png
	 * \see AnewtImage::save_jpeg
	 * \see AnewtImage::flush
	 */
	public function save()
	{
		assert('$this->is_set("filename")');
		$filename = $this->get('filename');
		if (AnewtImage::_filename_looks_like_png($filename))
			$this->save_png($filename);
		elseif (AnewtImage::_filename_looks_like_jpeg($filename))
			$this->save_jpeg($filename, $this->get('quality'));
		else
			trigger_error(sprintf(
				'AnewtImage::save(): Cannot save image, filename "%s" does not have a supported extension', $filename), E_USER_ERROR);
	}

	/**
	 * Flush the output to the browser. The 'filename' property is inspected to
	 * see what file type to use, e.g. PNG or JPEG.
	 *
	 * \see AnewtImage::flush_png
	 * \see AnewtImage::flush_jpeg
	 * \see AnewtImage::save
	 */
	public function flush()
	{
		assert('$this->is_set("filename")');
		$filename = $this->get('filename');
		if (AnewtImage::_filename_looks_like_png($filename))
			$this->flush_png($filename);
		elseif (AnewtImage::_filename_looks_like_jpeg($filename))
			$this->flush_jpeg($this->get('quality'));
		else
			trigger_error(sprintf(
				'AnewtImage::save(): Cannot flush image, filename "%s" does not have a supported extension', $filename), E_USER_ERROR);
	}

	/**
	 * Send a Content-type header.
	 *
	 * \param $content_type
	 *   The content-type to send.
	 */
	private function _content_type($content_type)
	{
		assert('is_string($content_type)');
		header(sprintf('Content-Type: %s', $content_type));
	}

	/**
	 * Flush this image as a PNG image.
	 *
	 * \see AnewtImage::flush
	 * \see AnewtImage::save_png
	 */
	public function flush_png()
	{
		$this->pre_output();
		$this->_content_type(image_type_to_mime_type(IMAGETYPE_PNG));
		imagepng($this->__img);
		$this->post_output();
	}

	/**
	 * Save this image as a PNG image.
	 *
	 * \param $filename
	 *   The filename to save to.
	 *
	 * \see AnewtImage::save
	 * \see AnewtImage::flush_png
	 */
	public function save_png($filename)
	{
		assert('is_string($filename)');

		$this->pre_output();
		imagepng($this->__img, $filename);
		$this->post_output();
	}

	/**
	 * Flush this image as a JPEG image.
	 *
	 * \param $quality
	 *   The JPEG quality to use for saving (optional).
	 *
	 * \see AnewtImage::flush
	 * \see AnewtImage::save_jpeg
	 */
	public function flush_jpeg($quality=null)
	{
		if (is_null($quality))
			$quality = $this->get('quality');

		assert('is_int($quality)');
		assert('($quality >= 0) && ($quality <= 100)');

		$this->pre_output();
		$this->_content_type(image_type_to_mime_type(IMAGETYPE_JPEG));
		imagejpeg($this->__img, '', $quality);
		$this->post_output();
	}

	/**
	 * Save this image as a JPEG image.
	 *
	 * \param $filename
	 *   The filename to save to.
	 * \param $quality
	 *   The JPEG quality to use for saving (optional). This does not affect the
	 *   image contents itself; that happens only after opening the file from
	 *   disk after writing.
	 *
	 * \see AnewtImage::save
	 * \see AnewtImage::flush_jpeg
	 */
	public function save_jpeg($filename, $quality=null)
	{
		if (is_null($quality))
			$quality = $this->get('quality');

		assert('is_string($filename)');
		assert('is_int($quality)');
		assert('($quality >= 0) && ($quality <= 100)');

		$this->pre_output();
		imagejpeg($this->__img, $filename, $quality);
		$this->post_output();
	}


	/* Callbacks */

	/**
	 * Callback prior to image output. Override this method if you want to
	 * change the image before it is output, e.g. draw an overlay or even draw
	 * a sparkline...
	 */
	protected function pre_output() {}

	/**
	 * Callback after image output. Override this method if you want to do
	 * postprocessing, e.g. some cleaning up routines could go here. Note that
	 * this will not have any effect on the image; it is already saved to a file
	 * or sent to the browser.
	 */
	protected function post_output() {}
}

?>
