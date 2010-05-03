<?php

/*
 * Anewt, Almost No Effort Web Toolkit, renderer module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Base Renderer class.
 *
 * This class is a base renderer which should be extended to match your own
 * needs. The default render() method dispatches the actual rendering to your
 * own custom functions.
 */
abstract class AnewtRenderer extends AnewtContainer
{
	/**
	 * Render to a string.
	 *
	 * This method queries the <code>render-mode</code> property and uses that
	 * string to call another method to do the actual rendering. The name of the
	 * real rendering method is formed by prepending <code>render_</code> to the
	 * value of the <code>render-mode</code> property. Example: if the
	 * <code>render-mode</code> property is set to <code>list</code>, this
	 * method calls the <code>render_list</code> method. If the
	 * <code>render-mode</code> property is not set, the method
	 * <code>render_default()</code> is used as the default fallback.
	 *
	 * \return
	 *   A string or object, e.g. a AnewtXMLDomNode, intended to be displayed by
	 *   calling to_string().
	 */
	function render()
	{
		$mode = $this->getdefault('render-mode', 'default');
		assert('is_string($mode)');

		$render_method = sprintf('render_%s', str_replace('-', '_', $mode));

		if (!method_exists($this, $render_method))
			throw new AnewtException('Method "%s" does not exist.', $render_method);

		return $this->$render_method();
	}
}

?>
