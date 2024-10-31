<?php

namespace Outpace\Lib;

class SitemapGenerator
{
    /**
     * Name of sitemap file
     * @var string
     * @access public
     */
    public $sitemapFileName = "sitemap.xml";
    /**
     * Name of sitemap index file
     * @var string
     * @access public
     */

    public $sitemapIndexFileName = "sitemap-index.xml";
    /**
     * Quantity of URLs per single sitemap file.
     * According to specification max value is 50.000.
     * If Your links are very long, sitemap file can be bigger than 10MB,
     * in this case use smaller value.
     * @var int
     * @access public
     */
    public $maxURLsPerSitemap = 50000;
    /**
     * URL to Your site.
     * Script will use it to send sitemaps to search engines.
     * @var string
     * @access private
     */
    private $baseURL;
    /**
     * Base path. Relative to script location.
     * Use this if Your sitemap and robots files should be stored in other
     * directory then script.
     * @var string
     * @access private
     */
    private $basePath;

    /**
     * Array with urls
     * @var array of strings
     * @access private
     */
    private $urls;
    /**
     * Array with sitemap
     * @var array of strings
     * @access private
     */

    private $sitemaps;
    /**
     * Array with sitemap index
     * @var array of strings
     * @access private
     */

    private $sitemapIndex;
    /**
     * Current sitemap full URL
     * @var string
     * @access private
     */
    private $sitemapFullURL;

    /**
     * Constructor.
     *
     * @param string $baseURL You site URL, with / at the end.
     * @param string|null $basePath Relative path where sitemap and robots should be stored.
     */
    public function __construct($baseURL, $basePath = "")
    {
        $this->baseURL  = $baseURL;
        $this->basePath = $basePath;
    }

    /**
     * Use this to add many URL at one time.
     * Each inside array can have 1 to 4 fields.
     *
     * @param array of arrays of strings $urlsArray
     */
    public function addUrls($urlsArray)
    {
        if (!is_array($urlsArray)) {
            throw new \InvalidArgumentException("Array as argument should be given.");
        }
        foreach ($urlsArray as $url) {
            $this->addUrl(
                isset($url[0]) ? $url[0] : null,
                isset($url[1]) ? $url[1] : null
            );
        }
    }

    /**
     * Use this to add single URL to sitemap.
     *
     * @param string $url URL
     * @param string $lastModified When it was modified, use ISO 8601
     * @param string $changeFrequency How often search engines should revisit this URL
     * @param string $priority Priority of URL on You site
     *
     * @see http://en.wikipedia.org/wiki/ISO_8601
     * @see http://php.net/manual/en/function.date.php
     */
    public function addUrl($url, $lastModified = null)
    {
        if ($url == null) {
            throw new \InvalidArgumentException("URL is mandatory. At least one argument should be given.");
        }
        $urlLenght = extension_loaded('mbstring') ? mb_strlen($url) : strlen($url);
        if ($urlLenght > 2048) {
            throw new \InvalidArgumentException("URL lenght can't be bigger than 2048 characters.
                                                Note, that precise url length check is guaranteed only using mb_string extension.
                                                Make sure Your server allow to use mbstring extension.");
        }
        $tmp        = array();
        $tmp['loc'] = $url;
        if (isset($lastModified)) {
            $tmp['lastmod'] = $lastModified;
        }
        $this->urls[] = $tmp;
    }

    /**
     * Create sitemap in memory.
     */
    public function createSitemap()
    {

        if (!isset($this->urls)) {
            throw new \BadMethodCallException("To create sitemap, call addUrl or addUrls function first.");
        }
        if ($this->maxURLsPerSitemap > 50000) {
            throw new \InvalidArgumentException("More than 50,000 URLs per single sitemap is not allowed.");
        }

        $generatorInfo      = '<!-- generator="OutpaceSEOSitemapGenerator" -->
								<!-- sitemap-generator-url="https://outpaceseo.com" sitemap-generator-version="1.0" -->';

        $sitemapHeader      = '<?xml version="1.0" encoding="UTF-8" ?>
								<?xml-stylesheet type="text/xsl" href="' . get_home_url() . '/sitemap.xsl"?>' . $generatorInfo . '
								<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        $sitemapIndexHeader = '<?xml version="1.0" encoding="UTF-8"?>' . $generatorInfo . '
								<sitemapindex
								xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
								xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
								xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
								</sitemapindex>';
        foreach (array_chunk($this->urls, $this->maxURLsPerSitemap) as $sitemap) {
            $xml = new \SimpleXMLElement($sitemapHeader);
            foreach ($sitemap as $url) {
                $row = $xml->addChild('url');
                $row->addChild('loc', htmlspecialchars($url['loc'], ENT_QUOTES, 'UTF-8'));
                if (isset($url['lastmod'])) {
                    $row->addChild('lastmod', $url['lastmod']);
                }
            }
            $this->sitemaps[] = $xml->asXML();
        }

        if (sizeof($this->sitemaps) > 1000) {
            throw new \LengthException("Sitemap index can contains 1000 single sitemaps.
                Perhaps You trying to submit too many URLs.");
        }
        if (sizeof($this->sitemaps) > 1) {
            for ($i = 0; $i < sizeof($this->sitemaps); $i++) {
                $this->sitemaps[$i] = array(
                    str_replace(".xml", ($i + 1) . ".xml.gz", $this->sitemapFileName),
                    $this->sitemaps[$i]
                );
            }
            $xml = new \SimpleXMLElement($sitemapIndexHeader);
            foreach ($this->sitemaps as $sitemap) {
                $row = $xml->addChild('sitemap');
                $row->addChild('loc', $this->baseURL . htmlentities($sitemap[0]));
                $row->addChild('lastmod', date('c'));
            }
            $this->sitemapFullURL = $this->baseURL . $this->sitemapIndexFileName;
            $this->sitemapIndex   = array(
                $this->sitemapIndexFileName,
                $xml->asXML()
            );
        } else {
            $this->sitemaps[0] = array(
                $this->sitemapFileName,
                $this->sitemaps[0]
            );
        }
    }

    /**
     * Returns created sitemaps as array of strings.
     * Use it You want to work with sitemap without saving it as files.
     * @return array of strings
     * @access public
     */
    public function toArray()
    {
        if (isset($this->sitemapIndex)) {
            return array_merge(array($this->sitemapIndex), $this->sitemaps);
        } else {
            return $this->sitemaps;
        }
    }

    /**
     * Will print sitemaps.
     * @access public
     */
    public function outputSitemap()
    {
        ob_get_clean();

        ob_start();

        header('Content-Type: text/xml; charset=utf-8');

        echo $this->sitemaps[0][1];

        ob_end_flush();
    }

    public static function outputSitemapXsl()
    {
        ob_get_clean();

        header('Content-Type: text/xsl; charset=utf-8');

        ob_start();

        echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
        <xsl:stylesheet version="1.0" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
            <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" />
            <xsl:template match="/">
                <html xmlns="http://www.w3.org/1999/xhtml">

                <head>
                    <title>Outpace XML Sitemap</title>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <meta name="robots" content="index,follow" />
                    <style type="text/css">
                        body {
                            font-family: 'Lucida Grande', 'Lucida Sans Unicode', Tahoma, Verdana, Arial, sans-serif;
                            font-size: 12px;
                        }

                        #content {
                            display: flex;
                            align-content: center;
                            flex-wrap: wrap;
                            flex-direction: column;
                            justify-content: flex-start;
                            align-items: center;
                        }

                        a {
                            text-decoration: none;
                            color: #b84164;
                        }

                        table {
                            margin-bottom: 20px;
                            font-size: 12px;
                        }

                        table tr:nth-child(odd) {
                            background-color: #F5F5F5;
                        }

                        table tr th {
                            min-width: 80px;
                            padding: 5px 7px;
                            text-align: left;
                        }

                        table tr td {
                            padding: 5px 7px;
                        }

                        table tr td a {
                            color: #b84164;
                        }

                        table tr td a:hover {
                            color: red;
                        }

                        .header {
                            padding: 0;
                            margin: 10px 0 20px;
                        }
                    </style>
                </head>

                <body>
                    <xsl:apply-templates></xsl:apply-templates>
                </body>

                </html>
            </xsl:template>
            <xsl:template match="sitemap:urlset">
                <div id="content">
                    <h1>Sitemap XML</h1>
                    <table cellspacing="3">
                        <tr>
                            <th>Page URL</th>
                            <th>Last Modified</th>
                        </tr>
                        <xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'" />
                        <xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />
                        <xsl:for-each select="./sitemap:url">
                            <tr>
                                <xsl:if test="position() mod 2 != 1">
                                    <xsl:attribute name="class">high</xsl:attribute>
                                </xsl:if>
                                <td>
                                    <xsl:variable name="page">
                                        <xsl:value-of select="sitemap:loc" />
                                    </xsl:variable>
                                    <a target="_blank" href="{$page}">
                                        <xsl:value-of select="sitemap:loc" />
                                    </a>
                                </td>
                                <td>
                                    <xsl:value-of select="sitemap:lastmod" />
                                </td>
                            </tr>
                        </xsl:for-each>
                    </table>
                    <div class="footer">Generated by Outpace SEO Plugin -
                        <a href="https://outpaceseo.com/wordpress-seo-plugin/">Outpace SEO Plugin</a>
                    </div>
                </div>
            </xsl:template>
        </xsl:stylesheet>
<?php echo  "\n";

        ob_end_flush();
    }
}
