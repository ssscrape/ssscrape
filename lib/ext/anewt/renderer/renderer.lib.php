<?php

/*
 * Anewt, Almost No Effort Web Toolkit, renderer module
 *
 * Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA+
 */


/**
 * Base Renderer class. This class is a base renderer which should be extended
 * to match your own needs. The default render() method dispatches the actual
 * rendering to your own custom functions.
 */
class Renderer extends Container {

	/**
	 * Render to a string. This method queries the 'render-mode' property and
	 * uses that string to call another method to do the actual rendering. The
	 * name of the real rendering method is formed by prepending 'render_' to
	 * the value of the render-mode property. Example: if the render-mode
	 * proprety is set to 'list', this method calls the render_list method. If
	 * the render-mode property is not set, the method render_default() is
	 * used as the default fallback.
	 *
	 * \return
	 *   A string intended to be displayed.
	 */
	function render() {
		$mode = $this->getdefault('render-mode', 'default');
		assert('is_string($mode)');
		$render_func = 'render_'.str_replace('-', '_', $mode);

		if(!method_exists($this, $render_func)) {
			trigger_error(sprintf(
				'%s::%s(): Method "%s" does not exist.',
				__CLASS__, __FUNCTION__, $render_func));
		}

		return $this->$render_func();
	}

}

?>
