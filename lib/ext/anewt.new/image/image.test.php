<?php

require_once '../anewt.lib.php';

anewt_include('image');

class AnewtImageTestCases {

	/** Print GD library information */
	static function gd_library_information()
	{
		header('Content-type: text/plain');
		echo 'GD Library Information';
		echo "\n\n";
		echo array_format(gd_info());
	}

	/** Load a PNG image from disk */
	static function load_png_image()
	{
		$img = AnewtImage::from_png('test.png');
		$img->flush();
		$img->destroy();
	}

	/** Load an image from disk */
	static function load_image()
	{
		$img = AnewtImage::from_file('test.png');
		$img->flush();
		$img->destroy();
	}

	/** Convert image to low quality JPEG */
	static function load_and_convert_to_low_quality_jpeg()
	{
		$img = AnewtImage::from_png('test.png');
		$img->set('quality', 5);
		$img->flush_jpeg();
		$img->destroy();
	}

	/** Resize image */
	static function resize()
	{
		$img = AnewtImage::from_png('test.png');
		$img->resize(600, 150);
		$img->flush_png();
		$img->destroy();
	}

	/** Resize image without resampling */
	static function resize_without_resampling()
	{
		$img = AnewtImage::from_png('test.png');
		$img->resize(600, 150, false);
		$img->flush_png();
		$img->destroy();
	}

	/** Resize image relatively */
	static function resize_relative()
	{
		$img = AnewtImage::from_png('test.png');
		$img->resize_relative(.75);
		$img->flush_png();
		$img->destroy();
	}

	/** Resize image to specified width */
	static function resize_width()
	{
		$img = AnewtImage::from_png('test.png');
		$img->resize_width(300);
		$img->flush_png();
		$img->destroy();
	}

	/** Resize image within specified bounding box */
	static function resize_within()
	{
		$img = AnewtImage::from_png('test.png');
		$img->resize_within(300, 300);
		$img->flush_png();
		$img->destroy();
	}

	/** Crop image */
	static function new_dimensions()
	{
		$img = AnewtImage::from_png('test.png');
		$img->set_dimensions(400, 200);
		$img->flush_png();
		$img->destroy();
	}

	/** Change width */
	static function change_width()
	{
		$img = AnewtImage::from_png('test.png');
		$img->set('width', 200);
		$img->flush_png();
		$img->destroy();
	}

	/** Change height */
	static function change_height()
	{
		$img = AnewtImage::from_png('test.png');
		$img->set('height', 200);
		$img->flush_png();
		$img->destroy();
	}

	/** Crop image */
	static function crop()
	{
		$img = AnewtImage::from_png('test.png');
		$img->crop(50, 60, 400, 200);
		$img->flush_png();
		$img->destroy();
	}

	/** Test color conversion methods */
	static function color_reprentation_conversions()
	{
		header('Content-type: text/plain');
		$img = new AnewtImage(5, 5);

		printf("The values below should be the same:\n");
		var_dump($img->color_from_string('#ff0000'));
		var_dump($img->color_from_string('#f00'));
		var_dump($img->color_from_rgb(255, 0, 0));
		print("\n");
		printf("The values below should be the same as well:\n");
		var_dump($img->color_from_string('#ff00000a'));
		var_dump($img->color_from_rgba(255, 0, 0, 10));
		print("\n");
	}

	/** Show red image */
	static function fill_red()
	{
		$img = new AnewtImage(300, 300);
		$img->fill($img->color_from_rgb(0xff, 0x00, 0x00));
		$img->set('filename', 'foo.png');
		$img->flush();
		$img->destroy();
	}

	/** Show dark red image made using alpha blending red and black */
	static function fill_dark_red_alpha()
	{
		$img = new AnewtImage(300, 300);
		$img->fill($img->color_from_rgb(0xff, 0x00, 0x00));
		$img->fill($img->color_from_rgba(0x00, 0x00, 0x00, 0x50));
		$img->flush_png();
		$img->destroy();
	}

	/** Test drawing functions */
	static function draw_lines_and_shapes()
	{
		$img = new AnewtImage(40, 40);

		$img->fill($img->color_from_rgb(83, 17, 43));

		$ctx1 = $img->create_drawing_context();
		$ctx2 = $img->create_drawing_context();

		/* First context */
		$ctx1->set('color', $img->color_from_string('#ff0'));
		$ctx1->draw_line(18, 18, 21, 18);
		$ctx1->set('color', $img->color_from_rgb(203, 143, 107));
		$ctx1->draw_string(0, 26, 'Anewt');

		/* Other context */
		$ctx2->set('color', $img->color_from_string('#933'));
		$ctx2->set('line-width', 5);
		$ctx2->draw_line(23, 23, 27, 27);
		$ctx2->draw_point(2, 20);
		$ctx2->draw_point(2, 22);
		$ctx2->draw_point(2, 24);

		/* Back to first context */
		$ctx1->draw_line(13, 13, 8, 17);
		$ctx1->draw_filled_rectangle_size(2, 2, 6, 6);
		$ctx1->set('color', $img->color_from_string('#3c3'));
		$ctx1->draw_filled_rectangle_size(3, 3, 4, 3);
		$ctx1->set('color', $img->color_from_string('#969'));
		$ctx1->draw_rectangle(3, 3, 6, 7);
		$ctx1->draw_filled_rectangle_size(15, 2, 2, 2);
		$ctx1->draw_filled_rectangle_size(15, 2, -1, -1);
		$col = $img->color_from_string('#36c');
		$ctx1->set('color', $col);
		$ctx1->draw_filled_rectangle(20, 2, 18, 3);
		assert('$ctx1->color_at(19, 2) == $col');

		/* Blow up so we can count the pixels in the result */
		$img->resize_relative(10, false);

		$img->flush_png();
		$img->destroy();
	}
}


$test = AnewtRequest::get_string('test');

if (is_null($test))
{
	/* Show test chooser */

	anewt_include('page');
	$p = new AnewtPage();
	$p->set('title', 'Choose a test');
	$p->append(ax_h1('Choose a test'));
	foreach (get_class_methods('AnewtImageTestCases') as $name)
	{
		$url = AnewtURL::build(
			AnewtRequest::relative_url(),
			array('test' => $name));
		$p->append(ax_p(ax_a_href(sprintf('Test: %s', $name), $url)));
	}
	$p->flush();

} else
	/* Invoke test function */
	AnewtImageTestCases::$test();

?>
