<?php

/*
 * Anewt, Almost No Effort Web Toolkit, smarty module
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


anewt_include('smarty');


/**
 * Renderer for Smarty templates.
 */
class SmartyTemplateRenderer extends SmartyTemplate {

	/**
	 * Constructor for a new SmartyTemplateRenderer instance
	 *
	 * \param $basetemplate Template name (optional, default is null)
	 * \param $directories Directories to use (optional, see SmartyTemplate documentation for more information)
	 * \param $mode Default render mode (optional, default is null)
	 *
	 * \see SmartyTemplate
	 */
	function SmartyTemplateRenderer($basetemplate=null, $directories=null, $mode=null) {

		/* Handle basetemplate parameter */

		if (is_null($basetemplate)) {
			/* Try to use the class name. Note that the __CLASS__ constant does
			 * not work for inherited classes, which is what we need... :( */
			$basetemplate = str_strip_suffix(strtolower(get_class($this)), 'renderer');
		}

		assert('is_string($basetemplate)');
		assert('strlen($basetemplate) > 0');
		$this->set('basetemplate', $basetemplate);


		/* Handle mode parameter */

		if (!is_null($mode)) {
			assert('is_string($mode)');
			$this->set('mode', $mode);
		}


		/* Call parent constructor */

		parent::SmartyTemplate(null, $directories);
	}


	/**
	 * Renders the current instance. The $mode parameter can be used to specify
	 * which render mode should be used. First, an attempt is made to use
	 * a template. If this fails, a render method is called.
	 *
	 * \param $mode A string denoting the render mode to use (optional).
	 * \param $params Additional parameters can be specified, these will be
	 * passed unchanged to the render methods.
	 *
	 */
	function render($mode=null, $params=null) {

		/* Handle parameters */

		$args = func_get_args();
		$num_args = func_num_args();

		if ($num_args >= 1) {
			$mode = array_shift($args);
			$params = &$args;
		}


		/* Which render mode? */

		if (is_null($mode))
			$mode = $this->getdefault('mode', 'default');

		assert('is_string($mode)');


		/* Check for basetemplate */

		if ($mode === 'default') {
			$template = sprintf('%s.tpl', $this->get('basetemplate'));
		} else {
			$template = sprintf('%s-%s.tpl', $this->get('basetemplate'), $mode);
		}
		$this->set('template', $template);

		if ($this->template_is_available($template)) {
			
			/* Create a reference to the current object the renderer property so
			 * that templates can call methods on it */
			$this->smarty->assign_by_ref('renderer', $this);

			/* TODO: What to do with optional $params? */

			/* Return the rendered template */
			return SmartyTemplate::render();
		}


		/* Fallback to render methods */

		$render_method = 'render_' . str_replace('-', '_', $mode);
		if (!method_exists($this, $render_method)) {
			/* No way to render this object */
			trigger_error(sprintf( '%s::%s(): No way to render this block using
						mode "%s", no template "%s" and no method "%s" found.',
						__CLASS__, __FUNCTION__, $mode, $template,
						$render_method), E_USER_ERROR);
		} else {

			/* Call the render method with appropriate parameters */

			if ($params) {
				return call_user_func_array(array(&$this, $render_method), $params);
			} else {
				return $this->$render_method();
			}

		}


	}
}


?>
