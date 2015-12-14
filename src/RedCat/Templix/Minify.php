<?php
namespace RedCat\Templix;
class Minify {
	
	static function PHP($src){
		return (new static())->minifyPHP($src);
	}
	static function JS($src){
		return (new static())->minifyJS($src);
	}
	static function HTML($src){
		return (new static())->minifyHTML($src);
	}
	static function CSS($src){
		return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    ',"\ r \ n", "\ r", "\ n", "\ t"],'',preg_replace( '! / \ *[^*]* \ *+([^/][^*]* \ *+)*/!','',preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$src)));
	}
	
	var $minifyHTML;
	private $tokens = [];
	private $result;
	function minifyPHP($text,$minifyHTML=true){
		$this->tokens = [];
		$this->result = null;
		$this->minifyHTML = $minifyHTML;
		$this->add_tokens($text);
		return str_replace('?><?php','',$this);
	}
	function __toString() {
		$this->remove_public_modifier();
		$str = $this->generate_result();
		return "$str";
	}
	private function compressLoop(){
		foreach($this->tokens as $t) {
			$text = $t[1];
			if(!strlen($text))
				continue;
			$l = strlen($this->result)-1;
			if(preg_match("~^\\w\\w$~", (isset($this->result[$l])?$this->result[$l]:'').$text[0]))
				$this->result .= " ";
			$this->result .= $text;
		}
	}
	private function compressLoopHTML(){
		$php = false;
		$html = '';
		foreach($this->tokens as $t){
			$text = $t[1];
			$l = strlen($this->result)-1;
			if(strlen($text)&&preg_match("~^\\w\\w$~",(isset($this->result[$l])?$this->result[$l]:'').$text[0]))
				$this->result .= ' ';
			if(($tt=trim($text))=='?>'){
				$this->result .= $text;
				$php = false;
			}
			elseif($tt=='<?php'||$tt=='<?'){
				$php = true;
				$this->result .= $text;
				if(substr($text,-1)!=' ')
					$this->result .= ' ';
			}
			else{
				if(!$php){
					$tmp = $this->minifyHTML($text);
					if(preg_match("/\\s/",substr($text,-1)))
						$tmp .= ' ';
					if(preg_match("/\\s/",substr($text,0,1)))
						$tmp = ' '.$tmp;
					$text = $tmp;
				}
				$this->result .= $text;
			}
		}
	}
	private function generate_result() {
		$this->result = "";
		if($this->minifyHTML)
			$this->compressLoopHTML();
		else
			$this->compressLoop();
		return $this->result;
	}
	private function remove_public_modifier() { 
		for($i = 0; $i < count($this->tokens) - 1; $i++) {
			if($this->tokens[$i][0] == T_PUBLIC){
				if($this->tokens[$i-1][0] == T_STATIC){
					$this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? ["", ""] : [-1, ""];
				}
				else{
					$this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? [T_VAR, "var"] : [-1, ""];
				}
			}
		}            
	}
	private function add_tokens($text) {            
		$tokens = token_get_all(trim($text));
		$pending_whitespace = count($this->tokens) ? "\n" : "";
		foreach($tokens as $t) {
			if(!is_array($t))
				$t = [-1, $t];
			if($t[0] == T_COMMENT || $t[0] == T_DOC_COMMENT)
				continue;
			if($t[0] == T_WHITESPACE) {
				$pending_whitespace .= $t[1];
				continue;
			}				
			$this->tokens[] = $t;        
			$pending_whitespace = "";
		}
	}	
	
	protected $_isXhtml = null;
    protected $_replacementHash = null;
    protected $_placeholders = [];
	protected $_html;
    function minifyHTML($html){
        $this->_html = str_replace("\r\n", "\n", trim($html));
        if ($this->_isXhtml === null)
            $this->_isXhtml = (false !== strpos($this->_html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        $this->_replacementHash = 'MINIFYHTML' . md5($_SERVER['REQUEST_TIME']);
        $this->_placeholders = [];
        // replace SCRIPTs (and minify) with placeholders
        $this->_html = preg_replace_callback(
            '/(\\s*)(<script\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i'
            ,[$this, '_removeScriptCB']
            ,$this->_html);
        // replace STYLEs (and minify) with placeholders
        $this->_html = preg_replace_callback(
            '/\\s*(<style\\b[^>]*?>)([\\s\\S]*?)<\\/style>\\s*/i'
            ,[$this, '_removeStyleCB']
            ,$this->_html);
        // remove HTML comments (not containing IE conditional comments).
        $this->_html = preg_replace_callback(
            '/<!--([\\s\\S]*?)-->/'
            ,[$this, '_commentCB']
            ,$this->_html);
        // replace PREs with placeholders
        $this->_html = preg_replace_callback('/\\s*(<pre\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i'
            ,[$this, '_removePreCB']
            ,$this->_html);
        // replace TEXTAREAs with placeholders
        $this->_html = preg_replace_callback(
            '/\\s*(<textarea\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i'
            ,[$this, '_removeTextareaCB']
            ,$this->_html);
        // trim each line.
        // @todo take into account attribute values that span multiple lines.
        $this->_html = preg_replace('/^\\s+|\\s+$/m', '', $this->_html);
        // remove ws around block/undisplayed elements
        $this->_html = preg_replace('/\\s+(<\\/?(?:area|base(?:font)?|blockquote|body'
            .'|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form'
            .'|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta'
            .'|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)'
            .'|ul)\\b[^>]*>)/i', '$1', $this->_html);
        // remove ws outside of all elements
        $this->_html = preg_replace_callback(
            '/>([^<]+)</'
            ,[$this, '_outsideTagCB']
            ,$this->_html);
        // use newlines before 1st attribute in open tags (to limit line lengths)
        $this->_html = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i', "$1\n$2", $this->_html);
        // fill placeholders
        $this->_html = str_replace(
            array_keys($this->_placeholders)
            ,array_values($this->_placeholders)
            ,$this->_html
        );
        return str_replace("\n",' ',$this->_html);
    }
    protected function _commentCB($m){
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<!['))? $m[0]: '';
    }
    protected function _reservePlace($content){
        $placeholder = '%' . $this->_replacementHash . count($this->_placeholders) . '%';
        $this->_placeholders[$placeholder] = $content;
        return $placeholder;
    }
    protected function _outsideTagCB($m){
        return '>' . preg_replace('/^\\s+|\\s+$/', ' ', $m[1]) . '<';
    }
    protected function _removePreCB($m){
        return $this->_reservePlace($m[1]);
    }
    protected function _removeTextareaCB($m){
        return $this->_reservePlace($m[1]);
    }
    protected function _removeStyleCB($m){
        $openStyle = $m[1];
        $css = $m[2];
        // remove HTML comments
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);
        // remove CDATA section markers
        $css = $this->_removeCdata($css);
        // minify
        $css = self::CSS($css);
        return $this->_reservePlace($this->_needsCdata($css)
            ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
            : "{$openStyle}{$css}</style>"
        );
    }
    protected function _removeScriptCB($m){
        $openScript = $m[2];
        $js = $m[3];
        // whitespace surrounding? preserve at least one space
        $ws1 = ($m[1] === '') ? '' : ' ';
        $ws2 = ($m[4] === '') ? '' : ' ';
        // remove HTML comments (and ending "//" if present)
        $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);
        // remove CDATA section markers
        $js = $this->_removeCdata($js);
        // minify
        $js = $this->minifyJS($js);
        return $this->_reservePlace($this->_needsCdata($js)
            ? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}"
            : "{$ws1}{$openScript}{$js}</script>{$ws2}"
        );
    }
    protected function _removeCdata($str){
        return (false !== strpos($str, '<![CDATA['))? str_replace(['<![CDATA[', ']]>'], '', $str): $str;
    }
    protected function _needsCdata($str){
        return ($this->_isXhtml && preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
    
    
    
    const ORD_LF            = 10;
    const ORD_SPACE         = 32;
    const ACTION_KEEP_A     = 1;
    const ACTION_DELETE_A   = 2;
    const ACTION_DELETE_A_B = 3;
    protected $a           = "\n";
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $output      = '';
    protected $lastByteOut  = '';
	protected $preserve_important_comments = true;
	function minifyJS($input,$pic=false){
        $this->a           = "\n";
		$this->b           = '';
		$this->inputIndex  = 0;
		$this->inputLength = 0;
		$this->lookAhead   = null;
		$this->output      = '';
		$this->lastByteOut  = '';
		
		$this->preserve_important_comments = $pic;
        $this->input = $input;

        $mbIntEnc = null;
        if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('8bit');
        }
        $this->input = str_replace("\r\n", "\n", $this->input);
        $this->inputLength = strlen($this->input);

        $this->action(self::ACTION_DELETE_A_B);

        while ($this->a !== null) {
            // determine next command
            $command = self::ACTION_KEEP_A; // default
            if ($this->a === ' ') {
                if (($this->lastByteOut === '+' || $this->lastByteOut === '-') 
                    && ($this->b === $this->lastByteOut)) {
                    // Don't delete this space. If we do, the addition/subtraction
                    // could be parsed as a post-increment
                } elseif (! $this->isAlphaNum($this->b)) {
                    $command = self::ACTION_DELETE_A;
                }
            } elseif ($this->a === "\n") {
                if ($this->b === ' ') {
                    $command = self::ACTION_DELETE_A_B;
                // in case of mbstring.func_overload & 2, must check for null b,
                // otherwise mb_strpos will give WARNING
                } elseif ($this->b === null
                          || (false === strpos('{[(+-', $this->b)
                              && ! $this->isAlphaNum($this->b))) {
                    $command = self::ACTION_DELETE_A;
                }
            } elseif (! $this->isAlphaNum($this->a)) {
                if ($this->b === ' '
                    || ($this->b === "\n" 
                        && (false === strpos('}])+-"\'', $this->a)))) {
                    $command = self::ACTION_DELETE_A_B;
                }
            }
            $this->action($command);
        }
        $this->output = trim($this->output);

        if ($mbIntEnc !== null) {
            mb_internal_encoding($mbIntEnc);
        }
        return $this->output;
    }

    /**
     * ACTION_KEEP_A = Output A. Copy B to A. Get the next B.
     * ACTION_DELETE_A = Copy B to A. Get the next B.
     * ACTION_DELETE_A_B = Get the next B.
     */
    protected function action($command)
    {
        if ($command === self::ACTION_DELETE_A_B 
            && $this->b === ' '
            && ($this->a === '+' || $this->a === '-')) {
            // Note: we're at an addition/substraction operator; the inputIndex
            // will certainly be a valid index
            if ($this->input[$this->inputIndex] === $this->a) {
                // This is "+ +" or "- -". Don't delete the space.
                $command = self::ACTION_KEEP_A;
            }
        }
        switch ($command) {
            case self::ACTION_KEEP_A:
                $this->output .= $this->a;
                $this->lastByteOut = $this->a;
                
                // fallthrough
            case self::ACTION_DELETE_A:
                $this->a = $this->b;
                if ($this->a === "'" || $this->a === '"') { // string literal
                    $str = $this->a; // in case needed for exception
                    while (true) {
                        $this->output .= $this->a;
                        $this->lastByteOut = $this->a;
                        
                        $this->a       = $this->get();
                        if ($this->a === $this->b) { // end quote
                            break;
                        }
                        if (ord($this->a) <= self::ORD_LF) {
                            throw new \Exception(
                                "JSMin: Unterminated String at byte "
                                . $this->inputIndex . ": {$str}");
                        }
                        $str .= $this->a;
                        if ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->lastByteOut = $this->a;
                            
                            $this->a       = $this->get();
                            $str .= $this->a;
                        }
                    }
                }
                // fallthrough
            case self::ACTION_DELETE_A_B:
                $this->b = $this->next();
                if ($this->b === '/' && $this->isRegexpLiteral()) { // RegExp literal
                    $this->output .= $this->a . $this->b;
                    $pattern = '/'; // in case needed for exception
                    while (true) {
                        $this->a = $this->get();
                        $pattern .= $this->a;
                        if ($this->a === '/') { // end pattern
                            break; // while (true)
                        } elseif ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a       = $this->get();
                            $pattern      .= $this->a;
                        } elseif (ord($this->a) <= self::ORD_LF) {
                            throw new \Exception(
                                "JSMin: Unterminated RegExp at byte "
                                . $this->inputIndex .": {$pattern}");
                        }
                        $this->output .= $this->a;
                        $this->lastByteOut = $this->a;
                    }
                    $this->b = $this->next();
                }
            // end case ACTION_DELETE_A_B
        }
    }

    protected function isRegexpLiteral()
    {
        if (false !== strpos("\n{;(,=:[!&|?", $this->a)) { // we aren't dividing
            return true;
        }
        if (' ' === $this->a) {
            $length = strlen($this->output);
            if ($length < 2) { // weird edge case
                return true;
            }
            // you can't divide a keyword
            if (preg_match('/(?:case|else|in|return|typeof)$/', $this->output, $m)) {
                if ($this->output === $m[0]) { // odd but could happen
                    return true;
                }
                // make sure it's a keyword, not end of an identifier
                $charBeforeKeyword = substr($this->output, $length - strlen($m[0]) - 1, 1);
                if (! $this->isAlphaNum($charBeforeKeyword)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get next char. Convert ctrl char to space.
     */
    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = null;
        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex += 1;
            } else {
                return null;
            }
        }
        if ($c === "\r" || $c === "\n") {
            return "\n";
        }
        if (ord($c) < self::ORD_SPACE) { // control char
            return ' ';
        }
        return $c;
    }

    /**
     * Get next char. If is ctrl character, translate to a space or newline.
     */
    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

    /**
     * Is $c a letter, digit, underscore, dollar sign, escape, or non-ASCII?
     */
    protected function isAlphaNum($c)
    {
        return (preg_match('/^[0-9a-zA-Z_\\$\\\\]$/', $c) || ord($c) > 126);
    }

    protected function singleLineComment()
    {
        $comment = '';
        while (true) {
            $get = $this->get();
            $comment .= $get;
            if (ord($get) <= self::ORD_LF) { // EOL reached
                // if IE conditional comment
                if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                    return "/{$comment}";
                }
                return $get;
            }
        }
    }

    protected function multipleLineComment()
    {
        $this->get();
        $comment = '';
        while (true) {
            $get = $this->get();
            if ($get === '*') {
                if ($this->peek() === '/') { // end of comment reached
                    $this->get();
                    // if comment preserved by YUI Compressor
                    if (0 === strpos($comment, '!')) {
                        if($this->preserve_important_comments){
							return "\n/*!" . substr($comment, 1) . "*/\n";
						}
						else{
							return "\n";
						}
                    }
                    // if IE conditional comment
                    if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                        return "/*{$comment}*/";
                    }
                    return ' ';
                }
            } elseif ($get === null) {
                throw new \Exception(
                    "JSMin: Unterminated comment at byte "
                    . $this->inputIndex . ": /*{$comment}");
            }
            $comment .= $get;
        }
    }
    protected function next()
    {
        $get = $this->get();
        if ($get !== '/') {
            return $get;
        }
        switch ($this->peek()) {
            case '/': return $this->singleLineComment();
            case '*': return $this->multipleLineComment();
            default: return $get;
        }
    }
}