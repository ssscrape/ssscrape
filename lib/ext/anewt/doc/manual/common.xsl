<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:exsl="http://exslt.org/common"
	xmlns:date="http://exslt.org/dates-and-times"
	xmlns:anewt="http://anewt.net/ns/2008/04/documentation"
	extension-element-prefixes="exsl date anewt"
	>

	<xsl:param name="outputdir" />


	<!-- Helper template to output a separate HTML document -->

	<xsl:template name="output-to-html">
		<xsl:param name="title" />
		<xsl:param name="href" />
		<xsl:param name="content" />

		<exsl:document
			href="{$outputdir}/{$href}.html"
			method="xml"
			indent="yes"
			omit-xml-declaration="yes"
			media-type="application/xhtml+xml"
			doctype-public = "-//W3C//DTD XHTML 1.0 Strict//EN"
			doctype-system=  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
			>
			<html>
				<head>
					<title>Anewt: <xsl:value-of select="$title"/></title>
					<link href="assets/style.css" rel="stylesheet" media="all" type="text/css" />
					<script type="text/javascript" src="assets/jquery-1.2.3.min.js"></script>
					<script type="text/javascript" src="assets/jquery.scrollTo-1.3.3-min.js"></script>
					<script type="text/javascript" src="assets/anewt-manual.js"></script>
				</head>
				<body>
					<div id="wrapper">

						<div id="header">
							<p class="navigation">
								<a href="index.html">Documentation home</a>
								<a href="toc.html">Table of contents</a>
								<a href="modules.html">Module index</a>
								<a href="classes.html">Class index</a>
							</p>
						</div>

						<div id="body">
							<xsl:copy-of select="$content"/>
						</div>

						<div class="footer">
							<p>
								<strong>
									<xsl:text>Anewt. Almost No Effort Web Toolkit.</xsl:text>
								</strong>
							</p>
							<p>
								<xsl:text>© 2006–</xsl:text>
								<xsl:value-of select="date:year()"/>
								<xsl:text>. This page was generated at </xsl:text>
								<xsl:value-of select="date:date-time()"/>
								<xsl:text>.</xsl:text>
							</p>
						</div>
					</div>
				</body>
			</html>
		</exsl:document>
	</xsl:template>


	<!-- Common output elements -->

	<xsl:template name="back-to-index-link">
		<p class="toclink"><a href="index.html">Back to the overview page</a></p>
	</xsl:template>


	<!-- String id builder templates -->

	<xsl:template name="build-string-id">

		<xsl:param name="content" />

		<!--
		<xsl:message>
			<xsl:text>DEBUG: exsl:object-type($content): </xsl:text>
			<xsl:value-of select="exsl:object-type($content)"/>
			<xsl:text> ### </xsl:text>
			<xsl:value-of select="name($content)"/>
		</xsl:message>
		-->

		<xsl:choose>

			<!-- Use explicit id, if given -->
			<xsl:when test="$content[@id] and name($content) != 'memberdef'">
				<xsl:value-of select="$content/@id"/>
			</xsl:when>

			<!-- Build an id -->
			<xsl:otherwise>


				<xsl:choose>

					<!-- Handle text sections -->
					<xsl:when test="name($content) = 'anewt:section'">
						<xsl:call-template name="clean-string">
							<xsl:with-param name="text" select="anewt:title" />
						</xsl:call-template>
					</xsl:when>

					<!-- Handle member definitions -->
					<xsl:when test="name($content) = 'memberdef'">
						<xsl:choose>
							<xsl:when test="$content[@kind='function']">
								<xsl:call-template name="clean-string">
									<xsl:with-param name="text" select="concat('function-', string($content/name))" />
								</xsl:call-template>
							</xsl:when>
							<xsl:when test="$content[@kind='variable']">
								<xsl:call-template name="clean-string">
									<xsl:with-param name="text" select="concat('variable-', substring-after(string($content/name), '$'))"/>
								</xsl:call-template>
							</xsl:when>
						</xsl:choose>
					</xsl:when>

					<!-- Fallback to cleaned text -->
					<xsl:otherwise>
						<xsl:call-template name="clean-string">
							<xsl:with-param name="text" select="string($content)"/>
						</xsl:call-template>
					</xsl:otherwise>

				</xsl:choose>

			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>

	<xsl:template name="clean-string">
		<xsl:param name="text" />
		<xsl:value-of select="translate(normalize-space($text), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ_ ,?', 'abcdefghijklmnopqrstuvwxyz--')"/>
	</xsl:template>

</xsl:stylesheet>
