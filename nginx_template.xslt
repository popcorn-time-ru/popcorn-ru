<?xml version="1.0"?>
<!--
  dirlist.xslt - transform nginx's into lighttpd look-alike dirlistings

  I'm currently switching over completely from lighttpd to nginx. If you come
  up with a prettier stylesheet or other improvements, please tell me :)

-->
<!--
   Copyright (c) 2016 by Moritz Wilhelmy <mw@barfooze.de>
   All rights reserved

   Redistribution and use in source and binary forms, with or without
   modification, are permitted providing that the following conditions
   are met:
   1. Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
   2. Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.

   THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
   IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
   WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
   ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY
   DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
   DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
   OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
   HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
   STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
   IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
   POSSIBILITY OF SUCH DAMAGE.
-->
<!DOCTYPE fnord [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xhtml="http://www.w3.org/1999/xhtml"
                xmlns:func="http://exslt.org/functions" xmlns="http://www.w3.org/1999/xhtml" version="1.0"
                exclude-result-prefixes="xhtml" extension-element-prefixes="func">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" doctype-public="-//W3C//DTD XHTML 1.1//EN"
                doctype-system="http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd" indent="yes"
                media-type="application/xhtml+xml"/>
    <xsl:strip-space elements="*"/>

    <xsl:template name="size">
        <!-- transform a size in bytes into a human readable representation -->
        <xsl:param name="bytes"/>
        <xsl:choose>
            <xsl:when test="$bytes &lt; 1000"><xsl:value-of select="$bytes"/>B
            </xsl:when>
            <xsl:when test="$bytes &lt; 1048576"><xsl:value-of select="format-number($bytes div 1024, '0.0')"/>K
            </xsl:when>
            <xsl:when test="$bytes &lt; 1073741824"><xsl:value-of select="format-number($bytes div 1048576, '0.0')"/>M
            </xsl:when>
            <xsl:otherwise><xsl:value-of select="format-number(($bytes div 1073741824), '0.00')"/>G
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="timestamp">
        <!-- transform an ISO 8601 timestamp into a human readable representation -->
        <xsl:param name="iso-timestamp"/>
        <xsl:value-of select="concat(substring($iso-timestamp, 0, 11), ' ', substring($iso-timestamp, 12, 5))"/>
    </xsl:template>

    <xsl:template name="breadcrumb">
        <xsl:param name="list"/>
        <xsl:param name="delimiter" select="'/'"/>
        <xsl:param name="reminder" select="$list"/>
        <xsl:variable name="newlist">
            <xsl:choose>
                <xsl:when test="contains($list, $delimiter)">
                    <xsl:value-of select="normalize-space($list)"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="concat(normalize-space($list), $delimiter)"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="first" select="substring-before($newlist, $delimiter)"/>
        <xsl:variable name="remaining" select="substring-after($newlist, $delimiter)"/>
        <xsl:variable name="current" select="substring-before($reminder, $remaining)"/>

        <xsl:choose>
            <xsl:when test="$remaining">
                <xsl:choose>
                    <xsl:when test="$first = ''">
                        <li class="breadcrumb-item">
                            <i class="fas fa-home"></i>
                            <a href="/">Home</a>
                        </li>
                    </xsl:when>
                    <xsl:otherwise>
                        <li class="breadcrumb-item">
                            <a href="{$current}">
                                <xsl:value-of select="$first"/>
                            </a>
                        </li>
                    </xsl:otherwise>
                </xsl:choose>

                <xsl:call-template name="breadcrumb">
                    <xsl:with-param name="list" select="$remaining"/>
                    <xsl:with-param name="delimiter" select="$delimiter"/>
                    <xsl:with-param name="reminder" select="$reminder"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:choose>
                    <xsl:when test="$first = ''">
                        <li class="breadcrumb-item">
                            <i class="fas fa-home"></i>
                            <a href="/">Home</a>
                        </li>
                    </xsl:when>
                    <xsl:otherwise>
                        <li class="breadcrumb-item active">
                            <a href="{$current}">
                                <xsl:value-of select="$first"/>
                            </a>
                        </li>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="directory">
        <tr>
            <td class="n">
                <a href="{current()}/">
                    <code>
                        <i class="far fa-folder" style="padding-right: 5px;"></i>
                        <xsl:value-of select="."/>
                    </code>
                </a>
            </td>
            <td class="m">
                <code>
                    <xsl:call-template name="timestamp">
                        <xsl:with-param name="iso-timestamp" select="@mtime"/>
                    </xsl:call-template>
                </code>
            </td>
            <td class="s">- &nbsp;</td>
        </tr>
    </xsl:template>

    <xsl:template name="icon">
        <xsl:param name="path"/>
        <xsl:variable name="extension">
            <xsl:call-template name="get-file-extension">
                <xsl:with-param name="path" select="$path"/>
            </xsl:call-template>
        </xsl:variable>

        <xsl:choose>
            <xsl:when test="$extension = 'bz2'">
                <i class="far fa-file-archive" style="padding-right: 5px;"></i>
            </xsl:when>
            <xsl:when test="$extension = 'gz'">
                <i class="far fa-file-archive" style="padding-right: 5px;"></i>
            </xsl:when>
            <xsl:when test="$extension = 'xz'">
                <i class="far fa-file-archive" style="padding-right: 5px;"></i>
            </xsl:when>
            <xsl:when test="$extension = 'zip'">
                <i class="far fa-file-archive" style="padding-right: 5px;"></i>
            </xsl:when>
            <xsl:otherwise>
                <i class="far fa-file" style="padding-right: 5px;"></i>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="get-file-extension">
        <xsl:param name="path"/>
        <xsl:choose>
            <xsl:when test="contains($path, '/')">
                <xsl:call-template name="get-file-extension">
                    <xsl:with-param name="path" select="substring-after($path, '/')"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:when test="contains($path, '.')">
                <xsl:call-template name="get-file-extension">
                    <xsl:with-param name="path" select="substring-after($path, '.')"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$path"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="file">
        <tr>
            <td class="n">
                <a href="{current()}">
                    <code>
                        <xsl:call-template name="icon">
                            <xsl:with-param name="path" select="."/>
                        </xsl:call-template>
                        <xsl:value-of select="."/>
                    </code>
                </a>
            </td>
            <td class="m">
                <code>
                    <xsl:call-template name="timestamp">
                        <xsl:with-param name="iso-timestamp" select="@mtime"/>
                    </xsl:call-template>
                </code>
            </td>
            <td class="s">
                <code>
                    <xsl:call-template name="size">
                        <xsl:with-param name="bytes" select="@size"/>
                    </xsl:call-template>
                </code>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="/">
        <html>
            <head>
                <title>Popcorn Time</title>
                <meta charset="utf-8"/>
                <link rel="stylesheet"
                      href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css"
                      integrity="sha256-L/W5Wfqfa0sdBNIKN9cG6QA5F2qx4qICmU2VgLruv9Y=" crossorigin="anonymous"/>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0/css/all.min.css"
                      integrity="sha256-ybRkN9dBjhcS2qrW1z+hfCxq+1aBdwyQM5wlQoQVt/0=" crossorigin="anonymous"/>
                <style type="text/css">
                    /* Sticky footer styles
                    -------------------------------------------------- */
                    html {
                        position: relative;
                        min-height: 100%;
                    }

                    body {
                        margin-bottom: 60px; /* Margin bottom by footer height */
                    }

                    .footer {
                        position: absolute;
                        bottom: 0;
                        width: 100%;
                        height: 60px; /* Set the fixed height of the footer here */
                        line-height: 60px; /* Vertically center the text there */
                        background-color: #f5f5f5;
                    }

                    /* Custom page CSS
                    -------------------------------------------------- */
                    .container {
                        width: auto;
                        max-width: 980px;
                        padding: 0 15px;
                    }
                </style>
            </head>
            <body>
                <!-- Begin page content -->
                <main role="main" class="container">
                    <h1 class="mt-5">Popcorn Time</h1>
                    <h2>Слава Украине! Героям слава!</h2>
                    <ol class="breadcrumb">
                        <xsl:call-template name="breadcrumb">
                            <xsl:with-param name="list" select="$path"/>
                        </xsl:call-template>
                    </ol>
                    <div class="list">
                        <table class="table" summary="Directory Listing" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="n">Name</th>
                                    <th class="m">Last Modified</th>
                                    <th class="s">Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="n">
                                        <a href="../"><i class="fas fa-level-up-alt" style="padding-right: 5px;"></i>
                                            Parent Directory/
                                        </a>
                                    </td>
                                    <td class="m">&nbsp;</td>
                                    <td class="s">- &nbsp;</td>
                                </tr>
                                <xsl:apply-templates/>
                            </tbody>
                        </table>
                    </div>
                </main>
                <script async="async" src="https://www.googletagmanager.com/gtag/js?id=UA-33769655-2"></script>
                <script>
	<![CDATA[
                    window.dataLayer = window.dataLayer || [];

                    function gtag() {
                        dataLayer.push(arguments);
                    }

                    gtag('js', new Date());

                    gtag('config', 'UA-33769655-2');
                    ]]>
    </script>

            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
