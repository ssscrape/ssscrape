<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:exsl="http://exslt.org/common"
	>

	<xsl:include href="common.xsl" />


	<!-- Starting template. Selects the compound -->

	<xsl:template match="/">
		<xsl:apply-templates select="doxygen/compounddef"/>
	</xsl:template>


	<!-- Keys -->

	<xsl:key name="members-by-id" match="memberdef" use="@id" />


	<!-- Classes -->

	<xsl:template match="/doxygen/compounddef[@kind='class']">
		<xsl:call-template name="build-class-page"/>
	</xsl:template>

	<xsl:template name="build-class-page">
		<xsl:call-template name="output-to-html">
			<xsl:with-param name="title"><xsl:value-of select="/doxygen/compounddef/compoundname"/></xsl:with-param>
			<xsl:with-param name="href">class-<xsl:value-of select="compoundname"/></xsl:with-param>
			<xsl:with-param name="content">

				<h1><xsl:value-of select="compoundname"/> class</h1>
				<div class="subtitle">
					<xsl:apply-templates select="briefdescription"/>
				</div>

				<xsl:call-template name="class-toc"/>

				<h2 id="overview">Class Overview</h2>

				<xsl:apply-templates select="briefdescription"/>
				<xsl:apply-templates select="detaileddescription"/>

				<!-- FIXME -->
				<!--
				<p>Defined in <xsl:value-of select="location/@file"/> at line <xsl:value-of
						select="location/@line"/></p>
				-->

				<!--
				<xsl:apply-templates select="collaborationgraph"/>
				-->


				<!-- Show the sections in a useful order. XXX keep in sync with "class-toc" -->
				<xsl:apply-templates select="sectiondef[@kind='public-static-func']"/>
				<xsl:apply-templates select="sectiondef[@kind='public-func']"/>
				<xsl:apply-templates select="sectiondef[@kind='user-defined']"/>
				<xsl:apply-templates select="sectiondef[@kind='protected-static-func']"/>
				<xsl:apply-templates select="sectiondef[@kind='protected-func']"/>
				<xsl:apply-templates select="sectiondef[@kind='private-static-func']"/>
				<xsl:apply-templates select="sectiondef[@kind='private-func']"/>
				<xsl:apply-templates select="sectiondef[@kind='public-attrib']"/>
				<xsl:apply-templates select="sectiondef[@kind='protected-attrib']"/>
				<xsl:apply-templates select="sectiondef[@kind='private-attrib']"/>


				<xsl:if test="basecompoundref">
					<!-- This class has one or more base classes -->

					<h2 id="inheritance">Inheritance</h2>

					<h3>Base Classes</h3>
					<xsl:call-template name="base-classes" />

					<h3>Inherited members</h3>
					<xsl:call-template name="inherited-members" />

				</xsl:if>

			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>


	<!-- Class tables of contents -->

	<xsl:template name="class-toc">
		<div class="toc chapter-toc">
			<h2>Page Contents</h2>
			<ol>
				<li><a href="#overview">Class Overview</a></li>
				<!-- Show the sections in a useful order. XXX keep in sync with "class" -->
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='public-static-func']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='public-func']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='user-defined']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='protected-static-func']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='protected-func']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='private-static-func']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='private-func']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='public-attrib']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='protected-attrib']"/>
				<xsl:apply-templates mode="class-toc-section-item" select="sectiondef[@kind='private-attrib']"/>
				<xsl:if test="basecompoundref">
					<li><a href="#inheritance">Inheritance</a></li>
				</xsl:if>
			</ol>
		</div>
	</xsl:template>

	<xsl:template match="sectiondef" mode="class-toc-section-item">
		<xsl:variable name="section-name">
			<xsl:call-template name="build-section-name"/>
		</xsl:variable>

		<li>
			<a>
				<xsl:attribute name="href">
					<xsl:text>#</xsl:text>
					<xsl:call-template name="build-string-id">
						<xsl:with-param name="content" select="exsl:node-set($section-name)"/>
					</xsl:call-template>
				</xsl:attribute>
				<xsl:value-of select="$section-name"/>
			</a>
		</li>
	</xsl:template>


	<!-- Sections -->

	<xsl:template match="sectiondef">

		<xsl:variable name="section-name">
			<xsl:call-template name="build-section-name"/>
		</xsl:variable>
		<xsl:variable name="section-id">
			<xsl:call-template name="build-string-id">
				<xsl:with-param name="content" select="exsl:node-set($section-name)"/>
			</xsl:call-template>
		</xsl:variable>

		<h2 id="{$section-id}">
			<xsl:value-of select="$section-name"/>
			<xsl:text> </xsl:text>
			<a href="#{$section-id}" class="permalink">
				<xsl:text>¶</xsl:text>
			</a>
		</h2>

		<xsl:if test="description">
			<xsl:apply-templates select="description"/>
		</xsl:if>

		<xsl:call-template name="build-section-overview"/>
	</xsl:template>

	<xsl:template match="description|briefdescription|detaileddescription">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template name="build-section-name">
		<xsl:choose>

			<!-- Public -->
			<xsl:when test="@kind='public-static-func'">
				<xsl:text>Public Static Methods</xsl:text>
			</xsl:when>
			<xsl:when test="@kind='public-func'">
				<xsl:text>Public Methods</xsl:text>
			</xsl:when>
			<xsl:when test="@kind='public-attrib'">
				<xsl:text>Public Attributes</xsl:text>
			</xsl:when>

			<!-- Protected -->
			<xsl:when test="@kind='protected-static-func'">
				<xsl:text>Protected Static Methods</xsl:text>
			</xsl:when>
			<xsl:when test="@kind='protected-func'">
				<xsl:text>Protected Methods</xsl:text>
			</xsl:when>
			<xsl:when test="@kind='protected-attrib'">
				<xsl:text>Protected Attributes</xsl:text>
			</xsl:when>

			<!-- Private -->
			<xsl:when test="@kind='private-static-func'">
				<xsl:text>Private Static Methods</xsl:text>
			</xsl:when>
			<xsl:when test="@kind='private-func'">
				<xsl:text>Private Methods</xsl:text>
			</xsl:when>
			<xsl:when test="@kind='private-attrib'">
				<xsl:text>Private Attributes</xsl:text>
			</xsl:when>

			<!-- Misc -->
			<xsl:when test="@kind='user-defined'">
				<xsl:value-of select="header"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:message>FIXME: unknown sectiondef type</xsl:message>
				<xsl:text>FIXME: unknown sectiondef type</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="build-section-overview">

		<!-- Show function listing (if any) -->

		<xsl:for-each select="memberdef">

			<xsl:variable name="member-id">
				<xsl:call-template name="build-string-id">
					<xsl:with-param name="content" select="."/>
				</xsl:call-template>
			</xsl:variable>

			<div class="class-member" id="{$member-id}">
				<h3>
					<strong>
						<a href="#{$member-id}">

							<xsl:if test="@kind='function'">
								<!-- Build a tooltip with short parameter reference -->
								<xsl:attribute name="title">
									<xsl:choose>
										<xsl:when test=".//parameterlist/parameteritem">
											<xsl:text>Parameters:&#10;</xsl:text>
											<xsl:for-each select=".//parameterlist/parameteritem">
												<xsl:value-of select="normalize-space(parameternamelist)"/>
												<xsl:text>: </xsl:text>
												<xsl:variable name="parameterdescription" select="normalize-space(parameterdescription)"/>
												<xsl:choose>
													<xsl:when test="string-length($parameterdescription) > 30">
														<xsl:value-of select="substring($parameterdescription, 0, 30)"/>
														<xsl:text>…</xsl:text>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="$parameterdescription"/>
													</xsl:otherwise>
												</xsl:choose>
												<xsl:text>&#10;</xsl:text>
											</xsl:for-each>
										</xsl:when>
										<xsl:otherwise>
											<xsl:text>Function takes no parameters or no documentation available.</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:attribute>
							</xsl:if>


							<!-- Show the function name and argument string, or the variable name -->
							<code>
								<xsl:value-of select="type"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="name"/>
							</code>
						</a>
					</strong>

					<xsl:if test="argsstring">
						<code><xsl:value-of select="argsstring"/></code>
					</xsl:if>


					<!-- Add [private] or [protected], if applicable -->
					<xsl:if test="@prot!='public'">
						<xsl:text> [</xsl:text>
						<xsl:value-of select="@prot"/>
						<xsl:text>]</xsl:text>
					</xsl:if>

					<!-- Add [static] if applicable -->
					<xsl:if test="@static='yes'">
						<xsl:text> [static]</xsl:text>
					</xsl:if>

					<!-- Add permanent link -->
					<xsl:text> </xsl:text>
					<a class="permalink" title="Permanent link" href="#{$member-id}">
						<xsl:text>¶</xsl:text>
					</a>
				</h3>


				<!-- Show the brief (one-line) description -->

				<xsl:apply-templates select="briefdescription"/>


				<!-- Show the detailed description, including detailed parameter reference -->

				<xsl:if test="normalize-space(detaileddescription)">
					<div class="class-member-detail">
						<!-- TODO params and so on -->
						<!-- TODO javascript to expand/collapse -->
						<xsl:apply-templates select="detaileddescription"/>
					</div>
				</xsl:if>

			</div>
		</xsl:for-each>

	</xsl:template>


	<!-- Parameter listings -->

	<xsl:template match="parameterlist[@kind='param']">
		<h4>Parameters</h4>
		<dl>
			<xsl:for-each select="parameteritem">
				<dt>
					<code>
						<xsl:value-of select="parameternamelist/parametername"/>
					</code>
				</dt>
				<dd>
					<xsl:apply-templates select="parameterdescription/*"/>
				</dd>
			</xsl:for-each>
		</dl>
	</xsl:template>


	<!-- Textual content -->

	<xsl:template match="para">
		<xsl:choose>

			<xsl:when test="itemizedlist|parameterlist|simplesect|xrefsect">
				<!-- This para has subparas regular paragraph of text -->
				<xsl:for-each select="./*">
					<xsl:choose>
						<xsl:when test="name() = 'itemizedlist' or name() = 'parameterlist' or name() = 'simplesect' or name() = 'xrefsect'">
							<xsl:apply-templates select="."/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:message>nee: <xsl:value-of select="name()"/></xsl:message>
							<p><xsl:apply-templates/></p>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:for-each>
			</xsl:when>

			<xsl:otherwise>
				<!-- This seems a regular paragraph of text -->
				<p><xsl:apply-templates/></p>
			</xsl:otherwise>

		</xsl:choose>

	</xsl:template>

	<xsl:template match="simplesect[@kind='return']">
		<h4>Return value</h4>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="simplesect[@kind='see']">
		<h4>See also</h4>
		<ul>
			<xsl:for-each select="*">
				<li><xsl:apply-templates/></li>
			</xsl:for-each>
		</ul>
	</xsl:template>

	<xsl:template match="para//ref[@kindref='member']">
		<!-- TODO check for () in node value to check for function/variable -->
		<code>
			<a>
				<xsl:attribute name="href">
					<xsl:text>#</xsl:text>
					<xsl:call-template name="build-string-id">
						<xsl:with-param name="content" select="//memberdef[@id=current()/@refid]"/>
					</xsl:call-template>
				</xsl:attribute>
				<xsl:apply-templates/>
			</a>
		</code>
	</xsl:template>

	<xsl:template match="para//ref[@kindref='compound']">
		<code>
			<a>
				<xsl:attribute name="href">
					<xsl:text>class-</xsl:text>
					<xsl:value-of select="."/>
					<xsl:text>.html</xsl:text>
				</xsl:attribute>
				<xsl:apply-templates/>
			</a>
		</code>
	</xsl:template>

	<xsl:template match="itemizedlist">
		<ul>
			<xsl:for-each select="listitem">
				<li>
					<xsl:apply-templates/>
				</li>
			</xsl:for-each>
		</ul>
	</xsl:template>

	<xsl:template match="computeroutput">
		<code><xsl:apply-templates/></code>
	</xsl:template>

	<xsl:template match="argsstring|inbodydescription|location">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="memberdef/type|memberdef/definition|memberdef/name|memberdef/argsstring">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="ulink">
		<a href="{@url}"><xsl:apply-templates/></a>
	</xsl:template>


	<!-- Just copy text nodes over -->

	<xsl:template match="text()">
		<xsl:value-of select="."/>
	</xsl:template>


	<!-- Inheritance -->

	<xsl:template name="base-classes">
		<xsl:variable name="current-member-node" select="//inheritancegraph/node[link[@refid=//compounddef/@id]]"/>
		<ul>
			<xsl:apply-templates select="$current-member-node" mode="child"/>
		</ul>
	</xsl:template>

	<xsl:template match="inheritancegraph/node">
		<a>
			<xsl:attribute name="href">
				<xsl:text>class-</xsl:text>
				<xsl:value-of select="label"/>
				<xsl:text>.html</xsl:text>
			</xsl:attribute>
			<code><xsl:value-of select="label"/></code>
		</a>
	</xsl:template>

	<xsl:template match="inheritancegraph/node" mode="child">
		<li>
			<xsl:apply-templates select="."/>
		</li>
		<xsl:if test="childnode">
			<xsl:apply-templates select="childnode" mode="child"/>
		</xsl:if>
	</xsl:template>

	<xsl:template match="inheritancegraph/node/childnode" mode="child">
		<xsl:apply-templates select="//inheritancegraph/node[@id=current()/@refid]" mode="child"/>
	</xsl:template>

	<xsl:template name="inherited-members">
		<ul>
			<xsl:for-each select="//listofallmembers/member[not(@refid = key('members-by-id', @refid)/@id)]">
				<xsl:sort select="@refid"/>
				<xsl:variable name="class" select="substring-after(substring(@refid, 0, string-length(@refid) - 33), 'class')"/>
				<li>
					<a>
						<xsl:attribute name="href">
							<xsl:text>class-</xsl:text>
							<xsl:value-of select="$class"/>
							<xsl:text>.html</xsl:text>
							<xsl:choose>
								<xsl:when test="starts-with(name, '$')">
									<xsl:text>#variable-</xsl:text>
									<xsl:call-template name="build-string-id">
										<xsl:with-param name="content" select="exsl:node-set(substring-after(name, '$'))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>#function-</xsl:text>
									<xsl:call-template name="build-string-id">
										<xsl:with-param name="content" select="name"/>
									</xsl:call-template>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
						<xsl:attribute name="title">
							<xsl:text>Inherited from </xsl:text>
							<xsl:value-of select="$class"/>
						</xsl:attribute>
						<code>
							<xsl:value-of select="$class"/>
							<xsl:text>::</xsl:text>
							<xsl:value-of select="name"/>
							<xsl:if test="not(starts-with(name, '$'))">
								<xsl:text>()</xsl:text>
							</xsl:if>
						</code>
					</a>
				</li>
			</xsl:for-each>
		</ul>
	</xsl:template>


	<!-- Output FIXME for unhandled nodes -->

	<xsl:template match="*">
		<xsl:message>FIXME: <xsl:value-of select="name()"/></xsl:message>
		FIXME: <xsl:value-of select="name()"/>
	</xsl:template>


	<!-- FIXME templates below should be reviewed/updated -->

	<xsl:template match="simplesect">
		<p>
			<xsl:choose>
				<xsl:when test="@kind='return'">Returns:</xsl:when>
				<xsl:when test="@kind='see'">See also:</xsl:when>
				<xsl:otherwise>
					<xsl:message>FIXME: unknown simplesect kind</xsl:message>
					FIXME: unknown simplesect kind
				</xsl:otherwise>
			</xsl:choose>
		</p>

		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="xrefsect">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="xrefsect/xreftitle">
		<h3><xsl:apply-templates/></h3>
	</xsl:template>

	<xsl:template match="xrefsect/xrefdescription">
		<xsl:apply-templates/>
	</xsl:template>


</xsl:stylesheet>
