<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:anewt="http://anewt.net/ns/2008/04/documentation"
	>

	<xsl:preserve-space elements="pre programlisting" />


	<!-- Table of contents -->

	<xsl:template name="chapter-toc">
		<div class="toc chapter-toc">
			<h2>Page Contents</h2>
			<ol>
				<xsl:apply-templates select="anewt:section" mode="chapter-toc"/>
				<li><a href="#classes">Class listing</a></li>
			</ol>
		</div>
	</xsl:template>

	<xsl:template match="anewt:section" mode="chapter-toc">
		<li>
			<a>
				<xsl:attribute name="href">
					<xsl:text>#</xsl:text>
					<xsl:call-template name="build-string-id">
						<xsl:with-param name="content" select="."/>
					</xsl:call-template>
				</xsl:attribute>
				<xsl:value-of select="anewt:title"/>
			</a>
		</li>
	</xsl:template>


	<!-- Modules, chapters, sections -->

	<xsl:template match="anewt:chapter|anewt:module">
		<h1 id="title"><xsl:value-of select="anewt:title"/></h1>

		<xsl:if test="anewt:subtitle">
			<p class="subtitle"><xsl:value-of select="anewt:subtitle"/></p>
		</xsl:if>

		<xsl:call-template name="back-to-index-link"/>
		<xsl:call-template name="chapter-toc"/>
		<xsl:apply-templates/>


		<xsl:if test="local-name() = 'module'">
			<h2 id="classes">Class listing</h2>
			<p>This module provides the following classes:</p>
			<xsl:if test="anewt:classes">
				<ol>
					<xsl:for-each select="anewt:classes/anewt:class">
						<li>
							<code>
								<xsl:call-template name="create-cross-reference">
									<xsl:with-param name="type" select="'class'" />
									<xsl:with-param name="content" select="." />
								</xsl:call-template>
							</code>
						</li>
					</xsl:for-each>
				</ol>
			</xsl:if>
		</xsl:if>

		<xsl:call-template name="back-to-index-link"/>

	</xsl:template>

	<xsl:template match="anewt:section">
		<h2>
			<xsl:attribute name="id">
				<xsl:call-template name="build-string-id">
					<xsl:with-param name="content" select="."/>
				</xsl:call-template>
			</xsl:attribute>

			<xsl:value-of select="anewt:title"/>
		</h2>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="anewt:section/anewt:section">
		<h3><xsl:value-of select="anewt:title"/></h3>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="anewt:title|anewt:subtitle">
		<!-- Do nothing, titles are taken care of elsewhere -->
	</xsl:template>

	<xsl:template match="anewt:module/anewt:classes">
		<!-- Do nothing, class listings are taken care of elsewhere -->
	</xsl:template>


	<!-- Admonitions -->

	<xsl:template match="anewt:caution|anewt:important|anewt:note|anewt:tip|anewt:warning">
		<div class="admonition {local-name()}">
			<xsl:apply-templates/>
		</div>
	</xsl:template>


	<!-- Copy some allowed HTML nodes -->

	<xsl:template match="p|em|strong|a|code|ol|ul|li|dl|dt|dd|pre|pre//span">
		<xsl:copy>
			<xsl:for-each select="@*">
				<xsl:copy/>
			</xsl:for-each>
			<xsl:apply-templates/>
		</xsl:copy>
	</xsl:template>


	<!-- Cross references -->

	<xsl:template name="create-cross-reference">
		<xsl:param name="type"/>
		<xsl:param name="content"/>
		<a>
			<xsl:attribute name="href">
				<xsl:choose>
					<xsl:when test="contains($content, '(')">
						<!--  Only use the part up to the first ( character, so content
						like do_something() is hyperlinked correctly. -->
						<xsl:value-of select="concat($type, '-', substring-before($content, '('), '.html')"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="concat($type, '-', $content, '.html')"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:value-of select="$content"/>
		</a>
	</xsl:template>

	<xsl:template match="anewt:moduleref|anewt:classref|anewt:functionref|anewt:fileref">
		<code>
			<xsl:variable name="type" select="substring(local-name(.), 0, string-length(local-name(.)) - string-length('ref') + 1)"/>

			<xsl:attribute name="class">
				<xsl:value-of select="$type"/>
			</xsl:attribute>

			<xsl:call-template name="create-cross-reference">
				<xsl:with-param name="type" select="$type" />
				<xsl:with-param name="content" select="." />
			</xsl:call-template>

		</code>
	</xsl:template>


	<!-- Module, class, function and file names -->

	<xsl:template match="anewt:modulename|anewt:classname|anewt:functionname|anewt:filename">
		<code>
			<xsl:attribute name="class">
				<!-- Strip off "name" -->
				<xsl:value-of select="substring(local-name(.), 0, string-length(local-name(.) - string-length('name')))"/>
			</xsl:attribute>
			<xsl:apply-templates/>
		</code>
	</xsl:template>


	<!-- Property listings -->

	<xsl:template match="anewt:properties">
		<dl class="properties">
			<xsl:for-each select="anewt:property">
				<dt><code><xsl:value-of select="@name"/></code></dt>
				<dd><xsl:apply-templates/></dd>
			</xsl:for-each>
		</dl>
	</xsl:template>


	<!-- Examples -->

	<xsl:template match="anewt:example">
		<div class="example">

			<!-- Title is optional. If there is one, output a caption with
			the title and a link to this example. -->
			<xsl:if test="anewt:title">
				<xsl:attribute name="id"><xsl:value-of select="concat('example-', @src)"/></xsl:attribute>
				<p class="caption">
					<span class="numbering">
						<a href="#example-{@src}" title="(click for permanent link)">
							<xsl:text>Example </xsl:text>
							<xsl:number level="any" count="anewt:example"/>
						</a>
						<xsl:text>: </xsl:text>
					</span>
					<xsl:value-of select="anewt:title"/>
				</p>
			</xsl:if>

			<!-- Include the example code -->
			<xsl:apply-templates select="document(concat('examples/', @src, '.xml'))"/>

		</div>
	</xsl:template>


	<!-- Show messages for unhandled nodes -->

	<xsl:template match="*">
		<xsl:message>Error: unhandled node <xsl:value-of select="name()"/></xsl:message>
	</xsl:template>


</xsl:stylesheet>
