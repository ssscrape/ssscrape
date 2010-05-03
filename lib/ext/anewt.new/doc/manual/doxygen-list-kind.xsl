<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<!--

	This transformation extracts a list of all classes/functions from a Doxygen
	index file, based on the 'kind' parameter that shouldbe provided on the
	command line.

	-->


	<xsl:output method="text" />
	<xsl:strip-space elements="*" />

	<xsl:param name="kind" />

	<xsl:template match="/">
		<xsl:apply-templates select=".//*[@kind=$kind]"/>
	</xsl:template>

	<xsl:template match="member|compound">
		<xsl:value-of select="name"/>
		<xsl:text>&#10;</xsl:text>
	</xsl:template>

</xsl:stylesheet>
