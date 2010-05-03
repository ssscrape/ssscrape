<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:anewt="http://anewt.net/ns/2008/04/documentation"
	xmlns:exsl="http://exslt.org/common"
	xmlns:date="http://exslt.org/dates-and-times"
	extension-element-prefixes="anewt exsl date"
	>

	<xsl:include href="common.xsl" />
	<xsl:include href="text.xsl" />


	<xsl:template match="/manual">

		<!-- Create a toc file -->

		<xsl:call-template name="output-to-html">
			<xsl:with-param name="title">Table of Contents</xsl:with-param>
			<xsl:with-param name="href">toc</xsl:with-param>
			<xsl:with-param name="content">
				<xsl:call-template name="toc"/>
			</xsl:with-param>
		</xsl:call-template>


		<!-- Main file -->

		<xsl:call-template name="output-to-html">
			<xsl:with-param name="title">Manual</xsl:with-param>
			<xsl:with-param name="href">index</xsl:with-param>
			<xsl:with-param name="content">

				<div class="title">
					<h1>Anewt Manual</h1>
					<p class="subtitle">Almost No Effort Web Toolkit</p>

					<ul class="authorlist">
						<xsl:for-each select="authors/author">
							<li><xsl:value-of select="."/></li>
						</xsl:for-each>
					</ul>

					<p>Copyright &#xa9; 2006–<xsl:value-of select="date:year()"/></p>
				</div>

				<p>This is the work-in-progress manual for Anewt.</p>

				<xsl:call-template name="toc"/>

			</xsl:with-param>

		</xsl:call-template>


		<!-- Process each chapter -->


		<xsl:for-each select="content/chapter">
			<xsl:message>Processing chapter <xsl:value-of select="@ref"/>...</xsl:message>
			<xsl:call-template name="output-to-html">
				<xsl:with-param name="title">Manual</xsl:with-param>
				<xsl:with-param name="href"><xsl:value-of select="concat('chapter-', @ref)"/></xsl:with-param>
				<xsl:with-param name="content">
					<xsl:apply-templates select="document(concat('chapter-', @ref, '.xml'))/anewt:chapter"/>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:for-each>


		<!-- Process each module -->

		<xsl:for-each select="content/module">
			<xsl:message>Processing module <xsl:value-of select="@ref"/>...</xsl:message>
			<xsl:call-template name="output-to-html">
				<xsl:with-param name="title">Manual</xsl:with-param>
				<xsl:with-param name="href"><xsl:value-of select="concat('module-', @ref)"/></xsl:with-param>
				<xsl:with-param name="content">
					<xsl:apply-templates select="document(concat('../../', @ref, '/module.doc.xml'))/anewt:module"/>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:for-each>


		<!-- Build module index -->

		<xsl:call-template name="output-to-html">
			<xsl:with-param name="title">Module Index</xsl:with-param>
			<xsl:with-param name="href">modules</xsl:with-param>
			<xsl:with-param name="content">

				<h1>Module Index</h1>
				<xsl:call-template name="back-to-index-link"/>

				<p>Below is a list of all documented Anewt modules.</p>

				<ul>
					<xsl:for-each select="content/module">
						<li>
							<a href="module-{@ref}.html">
								<xsl:value-of select="@title"/>
								<xsl:text> (</xsl:text>
								<code><xsl:value-of select="@ref"/></code>
								<xsl:text>)</xsl:text>
							</a>
						</li>
					</xsl:for-each>
				</ul>

			</xsl:with-param>
		</xsl:call-template>


		<!-- Build class index -->

		<xsl:call-template name="output-to-html">
			<xsl:with-param name="title">Class Index</xsl:with-param>
			<xsl:with-param name="href">classes</xsl:with-param>
			<xsl:with-param name="content">

				<h1>Class Index</h1>
				<xsl:call-template name="back-to-index-link"/>

				<xsl:variable name="class-compounds" select="document('../doxygen/xml/index.xml')/doxygenindex/compound[@kind='class']"/>

				<p>Below is a list of all <xsl:value-of select="count($class-compounds)"/> documented Anewt classes.</p>

				<ul>
					<xsl:for-each select="$class-compounds">
						<li>
							<a>
								<xsl:attribute name="href">
									<xsl:text>class-</xsl:text>
									<xsl:value-of select="name"/>
									<xsl:text>.html</xsl:text>
								</xsl:attribute>

								<code><xsl:value-of select="name"/></code>
							</a>
						</li>
					</xsl:for-each>
			</ul>


			</xsl:with-param>
		</xsl:call-template>

		<!-- TODO -->

	</xsl:template>


	<!-- Table of contents -->

	<xsl:template name="toc">
		<div class="toc" id="toc">
			<h1>Contents</h1>
			<ol>
				<xsl:apply-templates select="content" mode="toc"/>
			</ol>
		</div>
	</xsl:template>

	<xsl:template match="content/*" mode="toc">

		<xsl:choose>

			<xsl:when test="name() = 'module'">
				<xsl:variable name="module" select="document(concat('../../', @ref, '/module.doc.xml'))/anewt:module"/>
				<xsl:variable name="text" select="$module/anewt:title"/>
				<xsl:variable name="href" select="concat('module-', @ref, '.html')"/>
				<li><a href="{$href}"><xsl:value-of select="$text"/></a></li>
			</xsl:when>

			<xsl:when test="name() = 'chapter'">
				<xsl:variable name="chapter" select="document(concat('chapter-', @ref, '.xml'))/anewt:chapter"/>
				<xsl:variable name="text" select="$chapter/anewt:title"/>
				<xsl:variable name="href" select="concat('chapter-', @ref, '.html')"/>
				<li><a href="{$href}"><xsl:value-of select="$text"/></a></li>
			</xsl:when>

			<xsl:otherwise>
				<xsl:message>Unknown content in manual: <xsl:value-of select="name()"/></xsl:message>
			</xsl:otherwise>

		</xsl:choose>

	</xsl:template>


	<!-- Show messages for unhandled nodes -->

	<xsl:template match="*">
		<xsl:message>Error: unhandled node <xsl:value-of select="name()"/></xsl:message>
	</xsl:template>

</xsl:stylesheet>
