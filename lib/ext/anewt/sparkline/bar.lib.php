<?php

/*
 * Anewt, Almost No Effort Web Toolkit, sparkline module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Bar sparkline. This class can be used to create bar sparkline images from
 * data values. Many options can be set to tweak the results.
 *
 * \todo Shortly describe all properties
 */
class AnewtSparklineImageBar extends AnewtSparklineImage
{
	/**
	 * Initialize a new AnewtSparklineImageBar instance.
	 */
	public function __construct()
	{
		parent::__construct();

		/* Default parameter values */
		$this->_seed(array(

			/* Bars */
			'bar-width' => 2,
			'bar-height' => null,
			'bar-spacing' => 1,

			/* Colors (from the Tango color palette) */
			'positive-bar-color' => $this->color_from_string('#4e9a06'),
			'negative-bar-color' => $this->color_from_string('#a40000'),
			'above-max-color' => $this->color_from_string('#73d216'),
			'below-min-color' => $this->color_from_string('#ef2929'),
		));
	}


	/* Calculations */

	/**
	 * Calculate the color of a bar based on the value. You may override this
	 * method to do custom colors. When this method is invoked, the minimum and
	 * maximum values can be retrieved from the min-value and max-value
	 * properties.
	 *
	 * \param $value
	 *   The value for which the color should be calculated
	 *
	 * \return
	 *   The color to use
	 */
	public function calculate_bar_color($value)
	{
		assert('is_int($value)');

		if ($value >= 0)
		{
			if ($value > $this->_get('max-value'))
				return $this->_get('above-max-color');
			else
				return $this->_get('positive-bar-color');
		} else
		{
			if ($value < $this->_get('min-value'))
				return $this->_get('below-min-color');
			else
				return $this->_get('negative-bar-color');
		}
	}


	/* Rendering */

	/**
	 * Render the sparkline image here.
	 */
	public function pre_output()
	{
		$values = $this->get('scaled-values');
		assert('is_numeric_array($values)');
		$num_values = count($values);

		/* Minimum and maximum values */

		$min_value = $this->getdefault('min-value', null);
		if (is_null($min_value))
		{
			$min_value = min($values);
			$this->_set('min-value', $min_value);
		}
		assert('is_int($min_value)');

		$max_value = $this->getdefault('max-value', null);
		if (is_null($max_value))
		{
			$max_value = max($values);
			$this->_set('max-value', $max_value);
		}
		assert('is_int($max_value)');


		/* Calculate the width and height we need for the final image */

		$image_border_left = $this->get('image-border-left');
		$image_border_right = $this->get('image-border-right');
		$image_border_top = $this->get('image-border-top');
		$image_border_bottom = $this->get('image-border-bottom');

		$width =
			$image_border_left + $image_border_right +
			$num_values * $this->get('bar-width') +
			($num_values - 1) * $this->get('bar-spacing');

		$height =
			$image_border_top + $image_border_bottom +
			($max_value - $min_value);

		$this->set('dimensions', $width, $height);


		/* Background color */

		$this->fill($this->_get('background-color'));

		/* Create drawing context */
		$ctx = $this->create_drawing_context();


		/* Zero axis */
		$zero_axis_y = $image_border_top + $max_value - 1;
		if ($this->get('draw-zero-axis'))
		{
			$ctx->set('color', $this->get('zero-axis-color'));
			$ctx->draw_line(
				$image_border_left, $zero_axis_y,
				$width - $image_border_right - 1, $zero_axis_y);
		}


		/* Draw the values */

		$bar_width = $this->get('bar-width');
		$bar_spacing = $this->get('bar-spacing');

		/* Note about the counter variable $i: starting at -1 and incrementing
		 * directly inside the loop allows the code to break (using 'continue'
		 * statements) without reaching the end of the foreach loop below. */
		$i = -1;
		foreach ($values as $value)
		{
			$i++;

			/* Which color to use for this bar? */
			$color = $this->calculate_bar_color($value, $min_value, $max_value);
			$ctx->set('color', $color);

			/* Crop to largest/smallest value if specified explicitly */
			if ($value > $max_value) $value = $max_value;
			if ($value < $min_value) $value = $min_value;

			if ($value == 0)
				continue;

			/* Bar height. If not set, the value is used as height in pixels.
			 * This results in regular bar graphs. */
			$bar_height =  $this->_get('bar-height');
			if (is_null($bar_height))
				$bar_height = $value;
			else {
				if ($value >= 0)
					$bar_height = min($bar_height, $value);
				else {
					$bar_height = -$bar_height;
					$bar_height = max($bar_height, $value);
				}
			}

			$x = $image_border_left + $i * ($bar_width + $bar_spacing);
			$y = $zero_axis_y - $value;

			/* Shift positive values one pixel to the top, because the values -1
			 * and 1 would be rendered exactly the same otherwise... */
			if ($value > 0) $y++;

			$ctx->draw_filled_rectangle_size(
				$x, $y,
				$bar_width, $bar_height);
		}

		/* Highlight Special value */
		if ($this->get('draw-highlight-max'))
		{
			/* TODO: where and which color? */
		}

		if ($this->get('draw-highlight-min'))
		{
			/* TODO: where and which color? */
		}

		/* For debugging purposes */
		$this->resize_relative($this->_get('debug-resize-factor'));
	}
}

?>
