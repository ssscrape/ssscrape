<?php

/*
 * Anewt, Almost No Effort Web Toolkit, image module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/* Anti-aliasing is not always available. */
define('ANEWT_IMAGE_DRAWING_CONTEXT_HAVE_ANTIALIAS', function_exists('imageantialias'));


/**
 * Drawing context for AnewtImage. A drawing context keeps some drawing
 * properties and offers drawing methods. Note that not all functions might be
 * available on your particular setup (eg. anti-aliasing).
 *
 * \see AnewtImage
 * \see AnewtImage::create_drawing_context
 *
 * \todo Add proper Doxygen method grouping marks.
 */
class AnewtImageDrawingContext extends Container
{
	/** AnewtImage instance this AnewtImageDrawingContext belongs to */
	private $image = null;

	/**
	 * \private
	 *
	 * Create a new drawing context. Do not instantiate this class directly, use
	 * AnewtImage::create_drawing_context instead.
	 *
	 * \param $image
	 *   A valid AnewtImage instance.
	 *
	 * \see AnewtImage::create_drawing_context
	 */
	function __construct($image)
	{
		/* Note: We keep a reference to the image object, not to the actual GD
		 * image instance, because it might change after e.g. resampling
		 * operations. */
		assert('$image instanceof AnewtImage;');
		$this->image = $image;

		/* Default property values */
		$this->_seed(array(
			'color'          => $image->color_from_rgba(0, 0, 0, 0),
			'line-width'     => 1,
			'alpha-blending' => true,
			'antialias'      => true,
		));
	}


	/**
	 * Set the gd properties on the actual image instance. All drawing methods
	 * should call this method before any GD function is invoked. This method
	 * shouldn't be called by non-drawing methods.
	 */
	private function _install_gd_properties()
	{
		/* FIXME: perhaps cache by setting the $current_context on the
		 * AnewtImage instance. This saves us quite a lot of GD calls if
		 * multiple drawing operations using the same drawing context are
		 * performed immediately after each other (which will often be the
		 * case), at the cost of a single reference check/set. However, this
		 * approach requires a "dirty" flag on the context itself (from setter
		 * methods perhaps?( */

		/* FIXME: thickness only works well for orthogonal lines. We should use
		 * something like the imageline example in the PHP manual to get better
		 * results. */
		imagesetthickness($this->image->__img, $this->_get('line-width'));

		imagealphablending($this->image->__img, $this->_get('alpha-blending'));

		if (ANEWT_IMAGE_DRAWING_CONTEXT_HAVE_ANTIALIAS)
			imageantialias($this->image->__img, $this->_get('antialias'));
	}


	/* Single points */
 
	/**
	 * Return the color value of the specified pixel.
	 *
	 * \param $x X coordinate
	 * \param $y Y coordinate
	 *
	 * \return Color value
	 */
	public function color_at($x, $y)
	{
		assert('is_int($x) && is_int($y)');
		return imagecolorat($this->image->__img, $x, $y);
	}

	/**
	 * Draw a single point on the image.
	 *
	 * \param $x X coordinate
	 * \param $y Y coordinate
	 */
	public function draw_point($x, $y)
	{
		assert('is_int($x) && is_int($y)');
		$this->_install_gd_properties();
		imagesetpixel($this->image->__img, $x, $y, $this->_get('color'));
	}


	/* Lines */

	/**
	 * Draw a line from one point to another point.
	 *
	 * \param $x1 X coordinate of the first point
	 * \param $y1 Y coordinate of the first point
	 * \param $x2 X coordinate of the second point
	 * \param $y2 Y coordinate of the second point
	 */
	public function draw_line($x1, $y1, $x2, $y2)
	{
		assert('is_int($x1) && is_int($y1) && is_int($x2) && is_int($y1)');
		$this->_install_gd_properties();
		imageline($this->image->__img, $x1, $y1, $x2, $y2, $this->_get('color'));
	}


	/* Rectangles */

	/**
	 * Draw a rectangle.
	 *
	 * \param $x1 X coordinate of the top-left point
	 * \param $y1 X coordinate of the top-left point
	 * \param $x2 X coordinate of the bottom-right point
	 * \param $y2 X coordinate of the bottom-right point
	 * \param $filled Whether the rectangle is filled
	 */
	private function _draw_rectangle($x1, $y1, $x2, $y2, $filled)
	{
		assert('is_int($x1) && is_int($y1) && is_int($x2) && is_int($y1)');
		assert('is_bool($filled)');

		/* Swap x values if needed */
		if ($x1 < $x2)
		{
			$top_left_x = $x1;
			$bottom_right_x = $x2;
		} else {
			$top_left_x = $x2;
			$bottom_right_x = $x1;
		}

		/* Swap y values if needed */
		if ($y1 < $y2)
		{
			$top_left_y = $y1;
			$bottom_right_y = $y2;
		} else {
			$top_left_y = $y2;
			$bottom_right_y = $y1;
		}

		$this->_install_gd_properties();

		if ($filled)
			imagefilledrectangle(
				$this->image->__img,
				$top_left_x, $top_left_y,
				$bottom_right_x, $bottom_right_y,
				$this->_get('color'));
		else
			imagerectangle(
				$this->image->__img,
				$top_left_x, $top_left_y,
				$bottom_right_x, $bottom_right_y,
				$this->_get('color'));
	}

	/**
	 * Draw a rectangle of a given width and height. Negative width and height
	 * values cause the rectangle to be drawn from the bottom right coordinate,
	 * extending to the top left.
	 *
	 * \param $x X coordinate of the top-left point
	 * \param $y X coordinate of the top-left point
	 * \param $width Width of the rectangle
	 * \param $height Height of the rectangle
	 * \param $filled Whether the rectangle is filled
	 */
	private function _draw_rectangle_size($x, $y, $width, $height, $filled)
	{
		assert('is_int($x) && is_int($y) && is_int($width) && is_int($height)');
		assert('is_bool($filled)');

		if ($width == 0) return;
		if ($height == 0) return;

		/* Handle both positive and negative x values */
		if ($width > 0)
		{
			$x1 = $x;
			$x2 = $x + $width - 1;
		} else {
			$x1 = $x + $width + 1;
			$x2 = $x;
		}

		/* Handle both positive and negative y values */
		if ($height > 0)
		{
			$y1 = $y;
			$y2 = $y + $height - 1;
		} else {
			$y1 = $y + $height + 1;
			$y2 = $y;
		}

		$this->_draw_rectangle($x1, $y1, $x2, $y2, $filled);
	}

	/**
	 * Draw a rectangle.
	 *
	 * \param $x1 X coordinate of the top-left point
	 * \param $y1 X coordinate of the top-left point
	 * \param $x2 X coordinate of the bottom-right point
	 * \param $y2 X coordinate of the bottom-right point
	 */
	public function draw_rectangle($x1, $y1, $x2, $y2)
	{
		$this->_draw_rectangle($x1, $y1, $x2, $y2, false);
	}

	/**
	 * Draw a filled rectangle.
	 *
	 * \param $x1 X coordinate of the top-left point
	 * \param $y1 X coordinate of the top-left point
	 * \param $x2 X coordinate of the bottom-right point
	 * \param $y2 X coordinate of the bottom-right point
	 */
	public function draw_filled_rectangle($x1, $y1, $x2, $y2)
	{
		$this->_draw_rectangle($x1, $y1, $x2, $y2, true);
	}

	/**
	 * Draw a rectangle of a given width and height. Negative width and height
	 * values cause the rectangle to be drawn from the bottom right coordinate,
	 * extending to the top left.
	 *
	 * \param $x X coordinate of the top-left point
	 * \param $y X coordinate of the top-left point
	 * \param $width Width of the rectangle
	 * \param $height Height of the rectangle
	 */
	public function draw_rectangle_size($x, $y, $width, $height)
	{
		$this->_draw_rectangle_size($x, $y, $width, $height, false);
	}

	/**
	 * Draw a filled rectangle of a given width and height. Negative width and
	 * height values cause the rectangle to be drawn from the bottom right
	 * coordinate, extending to the top left.
	 *
	 * \param $x X coordinate of the top-left point
	 * \param $y X coordinate of the top-left point
	 * \param $width Width of the rectangle
	 * \param $height Height of the rectangle
	 */
	public function draw_filled_rectangle_size($x, $y, $width, $height)
	{
		$this->_draw_rectangle_size($x, $y, $width, $height, true);
	}


	/* Text */

	/**
	 * Draw a simple string at the given point.
	 *
	 * \param $x X coordinate
	 * \param $y y coordinate
	 * \param $str String to draw
	 *
	 * \todo This method doesn't work like it really should
	 */
	public function draw_string($x, $y, $str)
	{
		assert('is_int($x) && is_int($y)');
		assert('is_string($str)');
		$this->_install_gd_properties();
		imagestring(
			$this->image->__img,
			4,
			$x, $y,
			$str,
			$this->_get('color'));
	}
}

?>
