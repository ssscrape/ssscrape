<?php

/*
 * Anewt, Almost No Effort Web Toolkit, sparkline module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('image');


/**
 * Base sparkline image class.
 */
abstract class AnewtSparklineImage extends AnewtImage
{
	/**
	 * Create a new Sparkline instance.
	 */
	public function __construct()
	{
		/* Initial size doesn't matter, the images resize themselves anyway */
		parent::__construct(1, 1);

		/* Default parameter values */
		$this->_seed(array(

			/* Borders, spacing and dimensions */
			'image-border-top' => 0,
			'image-border-right' => 0,
			'image-border-bottom' => 0,
			'image-border-left' => 0,

			/* Scale factor for values */
			'value-scale' => 1,

			/* Which elements to draw? */
			'draw-zero-axis' => true,
			'draw-highlight-max' => false,
			'draw-highlight-min' => false,

			/* Colors (from the Tango color palette) */
			'background-color' => $this->color_from_string('#fff'),
			'zero-axis-color' => $this->color_from_string('#d3d7cf'),

			/* Debugging */
			'debug-resize-factor' => 1,
		));
	}


	/* Special getters and setters */

	/**
	 * Return a list of all values, scaled to the current scale factor and
	 * rounded to integers.
	 *
	 * \return
	 *   Scaled and rounced values
	 */
	protected function get_scaled_values()
	{
		$values = $this->_get('values');
		$value_scale = $this->get('value-scale');
		assert('is_numeric($value_scale) && ($value_scale > 0)');
		$scaled_values = array();

		foreach ($values as $value)
			$scaled_values[] = (int) round($value_scale * $value);

		return $scaled_values;
	}

	/**
	 * Set the border of the image. Omission of values works just like CSS. Note
	 * that something like $image->set('image-border', 3, null, 4, 2) doesn't
	 * work. This is intentional, since it makes no sense for a shorthand
	 * method. Single values can be specified using the image-border-top,
	 * image-border-right, image-border-bottom, and image-border-left
	 * properties.
	 *
	 * \param $top Top image border
	 * \param $right Right image border (optional)
	 * \param $bottom Bottom image border (optional)
	 * \param $left Left image border (optional)
	 */
	public function set_image_border($top, $right=null, $bottom=null, $left=null)
	{
		/* Only one value specified */
		if (is_null($right))
			$right = $bottom = $left = $top;

		/* Two values specified */
		elseif (is_null($bottom))
		{
			$bottom = $top;
			$left = $right;
		}

		/* Three values specified */
		elseif (is_null($left))
			$left = $right;

		assert('is_int($top)');
		assert('is_int($right)');
		assert('is_int($bottom)');
		assert('is_int($left)');

		$this->_seed(array(
			'image-border-top'    => $top,
			'image-border-right'  => $right,
			'image-border-bottom' => $bottom,
			'image-border-left'   => $left,
		));
	}
}

?>
