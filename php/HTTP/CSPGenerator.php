<?php namespace Surikat\HTTP;
/*
Copyright (c) 2014-2015, Tom
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted 
provided that the following conditions are met:
1. Redistributions of source code must retain the above copyright notice, this list of 
   conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
   the following disclaimer in the documentation and/or other materials provided with the distribution.
3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse
   or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR 
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS
BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF 
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Content Security Policy generator.
 */
class CSPGenerator {

    private static $instance;

    private $reportonly = FALSE;

    private $defaultsrc = " 'none'";

    private $stylesrc = " 'self'";

    private $imagesrc = " 'self'";

    private $scriptsrc = " 'self'";

    private $connectsrc = '';

    private $mediasrc = '';

    private $fontsrc = '';

    private $framesrc = '';

    private $objectsrc = '';

    private $plugintypes = '';

    private $formaction = " 'self'";

    private $sandboxoptions = '';

    private $reflectedxss = 'filter';

    private $reporturi = '';

    /**
     * Create a new instance of CSPGenerator class.
     */
    public function __construct() {
    }

    /**
     * Get instance of CSPGenerator class.
     */
    public static function getInstance() {
        if (empty(self::$instance)) {
            self::$instance = new CSPGenerator();
        }

        return self::$instance;
    }

    /**
     * Set the url to where to report violations of the Content Security Policy.
     */
    public function setReporturi($reporturi) {
        $this->reporturi = $reporturi;
    }

    /**
     * Set report only mode.
     */
    public function setReportOnly() {
        $this->reportonly = TRUE;
    }

    /**
     * Set reflected-xss content security policy 1.1>= policy setting. (Experimental directive)
     * @param string $reflectedxss The experimental reflected-xss policy directive. This can be allow, filter(default) or block.
     */
    public function setReflectedxss($reflectedxss) {
        $this->reflectedxss = $reflectedxss;
    }

    /**
     * Parse user-agent header and set proper content security policy header and X-Frame-Options header
     * and X-XSS-Protection header based on the browser and browser version.
     */
    public function Parse() {
        $useragentinfo = $this->getBrowserInfo();
        if ($useragentinfo['browser'] === 'chrome') {
            // Disable content security policy violation reporting if chrome is used
            // because google chrome is causing false positives with google translate translating the page.
            $this->reporturi = NULL;
        }

        $cspheader = 'Content-Security-Policy: ';
        if ($this->reportonly) {
            $cspheader = 'Content-Security-Policy-Report-Only: ';
        }

        if ($useragentinfo['browser'] === 'firefox' && $useragentinfo['version'] <= 22 && $useragentinfo['version'] >= 3.7) {
            if ($this->reportonly) {
                $cspheader = 'X-Content-Security-Policy-Report-Only: ';
            } else {
                $cspheader = 'X-Content-Security-Policy: ';
            }

            // X-Content-Security-Policy: uses allow instead of default-src.
            $cspheader .= 'allow ' . $this->defaultsrc;
        } elseif ( ($useragentinfo['browser'] === 'chrome' && $useragentinfo['version'] <= 24 && $useragentinfo['version'] >= 14) || 
                   ($useragentinfo['browser'] === 'safari' && $useragentinfo['version'] >= 6 && $useragentinfo['version'] < 7) ) {
            // Safari 5.0/5.1 X-WebKit-CSP implementation is badly broken it blocks permited\whitelisted things so it's not usable at all.
            if ($this->reportonly) {
                $cspheader = 'X-WebKit-CSP-Report-Only: ';
            } else {
                $cspheader = 'X-WebKit-CSP: ';
            }

            $cspheader .= 'default-src' . $this->defaultsrc;
        } else {
            $cspheader .= 'default-src' . $this->defaultsrc;
        }

        if (!empty($this->stylesrc)) {
            // The obsolete decreated X-Content-Security-Policy header does not support style-src. This is not implemented.
            $cspheader .= '; style-src' . $this->stylesrc;
        }

        if (!empty($this->imagesrc)) {
            $cspheader .= '; img-src' . $this->imagesrc;
        }

        if (!empty($this->scriptsrc)) {
            $cspheader .= '; script-src' . $this->scriptsrc;
            // for iniline script with the X-Content-Security-Policy header use 'options inline-script'.
            if ($useragentinfo['browser'] === 'firefox' && $useragentinfo['version'] <= 22 && $useragentinfo['version'] >= 3.7) {
                if (strpos($this->scriptsrc, "'unsafe-inline'") >= 0) {
                    $cspheader .= '; options inline-script';
                }
            }
        }

        // Chrome for iOS fails to render page if "connect-src 'self'" is missing.
        if ($useragentinfo['browser'] === 'chrome') {
            $this->addConnectsrc("'self'");
        }

        if (!empty($this->connectsrc)) {
            // The decreated X-Content-Security-Policy header uses xhr-src instead of connect-src.
            if ($useragentinfo['browser'] === 'firefox' && $useragentinfo['version'] <= 22 && $useragentinfo['version'] >= 3.7) {
                $cspheader .= '; xhr-src' . $this->connectsrc;
            } else {
                $cspheader .= '; connect-src' . $this->connectsrc;
            }
        }

        if (!empty($this->mediasrc)) {
            $cspheader .= '; media-src' . $this->mediasrc;
        }

        if (!empty($this->fontsrc)) {
            $cspheader .= '; font-src' . $this->fontsrc;
        }

        if (!empty($this->framesrc)) {
            $cspheader .= '; frame-src' . $this->framesrc;
        }

        if (!empty($this->objectsrc)) {
            $cspheader .= '; object-src' . $this->objectsrc;
        }

        if (!empty($this->plugintypes)) {
            if ($useragentinfo['browser'] === 'opr' && $useragentinfo['version'] >= 20) {
                $cspheader .= '; plugin-types' . $this->plugintypes;
            }
        }

        // Experimental:
        //if (!empty($this->formaction)) {
        //    if ($useragentinfo['browser'] === 'opr' && $useragentinfo['version'] >= 25) {
        //        $cspheader .= '; form-action' . $this->formaction;
        //   }
        //}

        // Experimental:
        //if (!empty($this->reflectedxss)) {
        //    if ($useragentinfo['browser'] === 'opr' && $useragentinfo['version'] >= 25) {
        //        $cspheader .= '; reflected-xss ' . $this->reflectedxss;
        //    }
        //}

        if (!empty($this->reporturi)) {
            if ($useragentinfo['browser'] !== 'firefox' || $useragentinfo['version'] > 22) {
                $cspheader .= '; report-uri ' . $this->reporturi;
            }
        }

        header($cspheader, TRUE);
        // Add X-Frame-Options header based on the content security policy settings.
        if (empty($this->framesrc) || strpos($this->framesrc, "'none'") >= 0) {
            header('X-Frame-Options: DENY', TRUE);
        } elseif (strpos($this->framesrc, "'self'") >= 0) {
            header('X-Frame-Options: SAMEORIGIN', TRUE);
        } else {
            // ALLOW-FROM Not supported in Chrome or Safari or Opera and any Firefox less than version 18.0 and any Internet Explorer browser less than version 9.0. (source: http://erlend.oftedal.no/blog/tools/xframeoptions/)
            if (($useragentinfo['browser'] === 'firefox' && $useragentinfo['version'] >= 18) || 
                ($useragentinfo['browser'] === 'msie' && $useragentinfo['version'] >= 9)) {
                header('X-Frame-Options: ALLOW-FROM ' . $this->framesrc, TRUE);
            }
        }

        // Add X-XSS-Protection header based on CSP 1.1 settings.
        switch ($this->reflectedxss) {
            case 'filter':
                // filter is the prefered one, because mode=block can cause possible insecurity, source: http://homakov.blogspot.nl/2013/02/hacking-with-xss-auditor.html
                header('X-XSS-Protection: 1', TRUE);
                break;
            case 'allow':
                header('X-XSS-Protection: 0', TRUE);
                break;
            case 'block':
                header('X-XSS-Protection: 1; mode=block', TRUE);
                break;
        }
    }

    /**
     * Get browser name and version from user-agent header.
     * @return string[]
     */
    private function getBrowserInfo() {
        // Declare known browsers to look for
        $browsers = array('firefox', 'msie', 'safari', 'webkit', 'chrome', 'opr', 'opera', 'netscape', 'konqueror');

        // Clean up useragent and build regex that matches phrases for known browsers
        // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
        // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return array('browser' => 'unknown', 'version' => '-1.0');
        }

        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $pattern = '#(?<browser>' . join('|', $browsers) .')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';
        // Find all phrases (or return empty array if none found)
        if (!preg_match_all($pattern, $useragent, $matches)) {
            if (strpos($useragent, 'Trident/') >= 0) {
                // IE 11 does not have msie in user-agent header anymore, IE developers want forcing
                // feature detecting with javascript. This is not for HTTP headers possible, 
                // because then the headers are already send. 
                // source: http://blogs.msdn.com/b/ieinternals/archive/2013/09/21/internet-explorer-11-user-agent-string-ua-string-sniffing-compatibility-with-gecko-webkit.aspx
                return array('browser' => 'msie', 'version' => '11');
            } else {
                // Unknow browser.
                return array('browser' => 'unknown', 'version' => '-1.0');
            }
        }

        // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase, Opera 7,8 have a MSIE phrase), 
        // use the last one found (the right-most one in the UA). That's usually the most correct.
        $i = count($matches['browser']) - 1;
        return array('browser' => $matches['browser'][$i], 'version' => $matches['version'][$i]);
    }

    /**
     * Set the default-src content security policy directive. We don't allow empty default policy.
     * @param string $defaultsrc The default-src policy directive. Style-src, image-src, script-src, frame-src, connect-src, font-src, objectsrc and media-src all inherit from this.
     */
    public function setDefaultsrc($defaultsrc) {
        if (empty($defaultsrc)) {
            throw new Exception('CSP default-src policy directive cannot be empty.');
        }

        $this->defaultsrc = $defaultsrc;
    }

    /**
     * Add style-src content security policy directive.
     * @param string $stylesrc The style-src policy directive to add. Where to allow CSS files from use 'unsafe-inline' for style attributes in (X)HTML document.
     */
    public function addStylesrc($stylesrc) {
        if (strpos($this->stylesrc, $stylesrc) === FALSE) {
            $this->stylesrc .= ' ' . $stylesrc;
        }
    }

    /**
     * Add image-src content security policy directive.
     * @param string $imagesrc The image-src policy directive to add. Where to allow images from. Use data: for base64 data url images.
     */
    public function addImagesrc($imagesrc) {
        if (strpos($this->imagesrc, $imagesrc) === FALSE) {
            $this->imagesrc .= ' ' . $imagesrc;
        }
    }

    /**
     * Add script-src content security policy directive.
     * @param string $scriptsrc The script-src policy directive to add. Use 'unsafe-inline' to allow unsafe loading of iniline scripts, use 'unsafe-eval' to allow text-to-JavaScript mechanisms like eval.
     */
    public function addScriptsrc($scriptsrc) {
        if (strpos($this->scriptsrc, $scriptsrc) === FALSE) {
            $this->scriptsrc .= ' ' . $scriptsrc;
        }
    }

    /**
     * Add connect-src content security policy directive.
     * @param string $connectsrc The connect-src policy directive to add. Where to allow XMLHttpRequest to connect to.
     */
    public function addConnectsrc($connectsrc) {
        if (strpos($this->connectsrc, $connectsrc) === FALSE) {
            $this->connectsrc .= ' ' . $connectsrc;
        }
    }

    /**
     * Add media-src content security policy directive.
     * @param string $mediasrc The media-src policy directive to add. Where to allow to load video/audio sources from. Use mediastream: for the MediaStream API. 
     */
    public function addMediasrc($mediasrc) {
        if (strpos($this->mediasrc, $mediasrc) === FALSE) {
            $this->mediasrc .= ' ' . $mediasrc;
        }
    }

    /**
     * Add font-src content security policy directive.
     * @param string $fontsrc The font-src policy directive to add. Where to allow to load font files from.
     */
    public function addFontsrc($fontsrc) {
        if (strpos($this->fontsrc, $fontsrc) === FALSE) {
            $this->fontsrc .= ' ' . $fontsrc;
        }
    }

    /**
     * Add frame-src content security policy directive.
     * @param string $framesrc The frame-src policy directive to add. Where to allow to load frames/iframe from.
     */
    public function addFramesrc($framesrc) {
        if (strpos($this->framesrc, $framesrc) === FALSE) {
            $this->framesrc .= ' ' . $framesrc;
        }
    }

    /**
     * Add object-src content security policy directive.
     * @param string $objectsrc The object-src policy directive to add. Where to allow to load plugins objects like flash/java applets from.
     */
    public function addObjectsrc($objectsrc) {
        if (strpos($this->objectsrc, $objectsrc) === FALSE) {
            $this->objectsrc .= ' ' . $objectsrc;
        }
    }

    /**
     * Add plugin-types content security policy 1.1>= directive. (Experimental Directive)
     * @param string $plugintypes The plugin-types policy directive to add. A list of MIME types (e.g. application/x-shockwave-flash) of plugins allowed to load.
     */
    public function addPlugintypes($plugintypes) {
        if (strpos($this->plugintypes, $plugintypes) === FALSE) {
            $this->plugintypes .= ' ' . $plugintypes;
        }
    }

    /**
     * Add form-action content security policy 1.1>= directive. (Experimental Directive)
     * @param string $formaction The form-action policy directive to add. Restricts which URIs can be used as the action of HTML form elements.
     */
    public function addFormaction($formaction) {
        if (strpos($this->formaction, $formaction) === FALSE) {
            $this->formaction .= ' ' . $formaction;
        }
    }

    /**
     * Add sandbox options to the sandbox content security policy 1.1>= directive.
     * @param string $sandboxoption The sandbox policy directive to add. This can be: allow-forms, allow-pointer-lock, allow-popups, allow-same-origin, allow-scripts or allow-top-navigation.
     */
    public function addSandboxoption($sandboxoption) {
        if (strpos($this->sandboxoptions, $sandboxoption) === FALSE) {
            $this->sandboxoptions .= ' ' . $sandboxoption;
        }
    }
}