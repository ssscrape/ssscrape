<?php

/*
 * Anewt, Almost No Effort Web Toolkit, sparkline module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Line sparkline. This class can be used to create line sparkline images from
 * data values. Many options can be set to tweak the results.
 *
 * \todo Finish implementation. This code is a work in progress.
 *
 * \todo Shortly describe all properties
 */
class AnewtSparklineImageLine extends AnewtSparklineImage
{
	/**
	 * Initialize a new AnewtSparklineImageLine instance.
	 */
	public function __construct()
	{
		parent::__construct();

		/* Default parameter values */
		$this->_seed(array(

			/* Points */
			'point-spacing' => 3,

			/* Colors (from the Tango color palette) */
			'line-color' => $this->color_from_string('#4e9a06'),
			'point-color' => $this->color_from_string('#a40000'),
		));
	}


	/* Rendering */

	/**
	 * Render the sparkline image here.
	 */
	public function pre_output()
	{
		$values = $this->get('values');
		assert('is_numeric_array($values)');
		$num_values = count($values);


		/* Minimum and maximum values */

		$min_value = min($values);
		$max_value = max($values);


		/* Scale factor for all values */

		$value_scale = $this->get('value-scale');
		/* FIXME: scaling doesn't work correctly for non-integer values*/
		assert('is_numeric($value_scale)');


		/* Calculate the width and height we need for the final image */

		$point_spacing = $this->get('point-spacing');
		assert('is_int($point_spacing) && ($point_spacing > 0)');

		$image_border_left = $this->get('image-border-left');
		$image_border_right = $this->get('image-border-right');
		$image_border_top = $this->get('image-border-top');
		$image_border_bottom = $this->get('image-border-bottom');

		$width =
			$image_border_left + $image_border_right +
			$num_values * $point_spacing + 1; // '+ 1' is for last point

		$height =
			$image_border_top + $image_border_bottom +
			$value_scale * ($max_value - $min_value);

		$this->set_dimensions($width, $height);


		/* Background color */

		$this->fill($this->_get('background-color'));

		/* Create drawing context */
		$ctx = $this->create_drawing_context();


		/* Zero axis */
		$zero_axis_y = $image_border_top + ($value_scale * $max_value) - 1;
		if ($this->get('draw-zero-axis'))
		{
			$ctx->set('color', $this->get('zero-axis-color'));
			$ctx->set('color', $this->color_from_string('#0f0'));
			$ctx->draw_line(
				$image_border_left, $zero_axis_y,
				$width - $image_border_right - 1, $zero_axis_y);
		}


		/* Draw the lines */

		$ctx->set('color', $this->_get('line-color'));

		/* Note about the counter variable $i: starting at -1 and incrementing
		 * directly inside the loop allows the code to break (using 'continue'
		 * statements) without reaching the end of the foreach loop below. */
		/*
		$i = -1;
		foreach ($values as $value)
		{
			$i++;
			$scaled_value = $value_scale * $value;
			$x = $image_border_left + $i * ($bar_width + $bar_spacing);
			$y2 = $image_border_top + $value
			$ctx->draw_line($x1, $y1, $x2, $y2);
		}
		*/

		/* Draw the points */
		$ctx->set('color', $this->_get('point-color'));
		$i = -1;
		foreach ($values as $value)
		{
			$i++;
			$scaled_value = $value_scale * $value;
			$x = $image_border_left + $i * $point_spacing;
			$y = - $scaled_value + $zero_axis_y;

			$ctx->draw_point($x, $y);
		}


		/* For debugging purposes */
		$this->resize_relative($this->_get('debug-resize-factor'));
	}
}

?>
