<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:anewt="http://anewt.net/ns/2008/04/documentation"
	>

	<!--

	Given a module documentation file, this transformation lists all the classes
	that are claimed to be documented in that module documentation file.

	-->

	<xsl:output method="text" />
	<xsl:strip-space elements="*" />

	<xsl:template match="/">
		<xsl:apply-templates select="/anewt:module/anewt:classes/anewt:class"/>
	</xsl:template>

	<xsl:template match="/anewt:module/anewt:classes/anewt:class">
		<xsl:value-of select="."/>
		<xsl:text>&#10;</xsl:text>
	</xsl:template>

</xsl:stylesheet>
