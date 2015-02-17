<?php namespace Surikat\Document;

/*
 * **************************
 * *** RTF Class Code PHP ***
 * --------------------------
 * ** Based on a design found in phpclasses.org - By Michele Brodoloni (michele@xtnet.it)
 * ** RTF Generation Class (http://www.phpclasses.org/browse/package/3560.html)
 *
 * Fixes and implementations:
 * ---------------------------
 * 1. Small fixes
 * 2. Implementation of tables
 * 3. Setting the margins
 *
 * ===============================================================================================================
 * IMPORTANT NOTICE:
 * -----------------
 *
 * I am not responsible for any problems caused by class "RTF Class Code PHP"
 *
 * This php class is distributed as-is.
 * So do not bother for broken functions, nor any non-working thing.
 *
 * Hints and suggestions are welcome.
 *
 * ===============================================================================================================
 *
 * ******************************************************************
 * *** Author: Maury Miranda Marques - maurymmarques@gmail.com.br ***
 * ******************************************************************
 */

### START OF COLOR TABLE
define('BLACK', 	0);
define('DARKGRAY',	1);
define('LIGHTBLUE',	2);
define('CYAN',		3);
define('LIGHTGREEN',4);
define('PURPLE',	5);
define('RED', 		6);
define('YELLOW', 	7);
define('WHITE',		8);
define('BLUE', 		9);
define('DARKCYAN',  10);
define('DARKGREEN', 11);
define('DARKPURPLE',12);
define('BROWN',	    13);
define('DARKYELLOW',14);
define('GRAY',		15);
define('LIGHTGRAY', 16);
### END OF COLOR TABLE

class RTF{

	var $MyRTF;
	var $dfl_FontID;
	var $dfl_FontSize = 20;
	var $FontID;
	var $FontIDS = [
		"Times New Roman" => "froman",
		"Arial" => "fswiss",
		"Arial Black" => "fswiss",
		"Verdana" => "fswiss",
		"Tahoma" => "fswiss",
		"Courier New" => "fmodern",
	];
	var $TextDecoration;
    var $previousResult = 0;

	function __construct($addfont=false){

		$this->MyRTF="{\\rtf1\\ansi\n";
        $this->load_color_table();
		$this->load_font_table($addfont);
        $this->config_margin();
        $this->MyRTF .= "\n{\n\n";
	}

	/**
	 * Loads the color table (RGB)
     * @return void
	 */
	function load_color_table(){

		$this->MyRTF.="{\\colortbl;\n".
                      "\\red0\\green0\\blue0;\\red0\\green0\\blue255;\\red0\\green255\\blue255;\n".
                      "\\red0\\green255\\blue0;\\red255\\green0\\blue255;\\red255\green0\\blue0;\n".
                      "\\red255\\green255\\blue0;\\red255\\green255\\blue255;\\red0\green0\\blue128;\n".
                      "\\red0\\green128\\blue128;\\red0\\green128\\blue0;\\red128\\green0\\blue128;\n".
                      "\\red128\\green0\\blue0;\\red128\\green128\\blue0;\\red128\\green128\\blue128;\n".
                      "\\red192\\green192\\blue192;\n".
                      "}\n";
	}

	function load_font_table($addfont=false){
		if(is_array($addfont)){
			foreach($addfont as $k=>$v){
				if(is_numeric($k)){
					$this->FontIDS[$v] = "fswiss";
				}
				else{
					$this->FontIDS[$k] = $v;
				}
			}
		}
		elseif($addfont){
			$this->FontIDS[$addfont] = "fswiss";
		}
		$this->MyRTF.="{\\fonttbl\n";
		$i = 0;
		foreach($this->FontIDS as $k=>$v){
			$this->MyRTF.="{\\f".((string)$i)."\\$v\\fcharset0\\fprq2 $k;}\n";
			$i++;
		}
		$this->MyRTF.="}";
	}
	function cur_date() { return "\\chdate "; }
	function cur_time() { return "\\chtime "; }

	function add_list($array, $align = 'left'){
		if (!is_array($array)) return NULL;
		foreach ($array as $k=>$v){
            $this->align($align);
            $this->MyRTF .= "{ ";
			$this->bullet($v);
			$this->MyRTF .= "} ";
			$this->paragraph();
		}
	}

	function bullet($text){
        $this->TextDecoration .= "\\bullet  "; // 2 spaces are needed at the end for spacing the word from the bullet
        $this->add_text($text);
	}

	function add_text($msg, $align = 'left'){
		/** FIX RITORNI A CAPO **/
		$msg = str_replace("\r", "", $msg);
		$msg = str_replace("\n", "", $msg);

		/** FIX LETTERE ACCENTATE **/
		$msg = str_replace("à", "\\'e0", $msg);
		$msg = str_replace("è", "\\'e8", $msg);
		$msg = str_replace("é", "\\'e9", $msg);
		$msg = str_replace("ì", "\\'ec", $msg);
		$msg = str_replace("ò", "\\'f2", $msg);
		$msg = str_replace("ù", "\\'f9", $msg);
		$this->align($align);
		$this->MyRTF .= "{ ";
		if (empty($this->TextDecoration)){
			$this->TextDecoration .= $this->_font($this->dfl_FontID);
			$this->TextDecoration .= $this->_font_size($this->dfl_FontSize);
		}
        $this->MyRTF .= $this->TextDecoration;
		$this->MyRTF .= $msg;
		$this->MyRTF .= " } ";
		$this->TextDecoration = '';
	}

	function new_line($times = 1){
		for($i=0;$i<$times;$i++) $this->MyRTF .= "\\line\n";
	}
	
	function paragraph()			{ $this->MyRTF .= "\\par\n";  }
	function bold($s = 1)			{ return ($s == 0) ? " \\b0 " : "\\b "; 			}
	function italic($s = 1)			{ return ($s == 0) ? " \\i0 " : "\\i "; 			}
	function underline($s = 1)		{ return ($s == 0) ? " \\ulnone " : "\\ul "; 		}
	function caps($s = 1)			{ return ($s == 0) ? " \\caps0 " : "\\caps "; 		}
	function emboss($s = 1)			{ return ($s == 0) ? " \\embo0 " : "\\embo "; 		}
	function engrave($s = 1)		{ return ($s == 0) ? " \\impr0 " : "\\impr "; 		}
	function outline($s = 1)		{ return ($s == 0) ? " \\outl0 " : "\\outl "; 		}
	function shadow($s = 1)			{ return ($s == 0) ? " \\shad0 " : "\\shad ";	    }
	function sub($s = 1)		   	{ return ($s == 0) ? " \\nosupersub " : "\\shad ";  }
	function super($s = 1)			{ return ($s == 0) ? " \\nosupersub " : "\\super "; }

    function _font($id = 0){
		return ("\\f$id ");
	}

	function _font_size($size = 20){
		return ("\\fs$size ");
	}

	/**
	 * Sets the default font used in the document ( set_default_font() )
	 * used when the font is not assigned using set_font() function before
     * calling the add_text() function. Same thing for set_default_font_size().
     * @arg1		string
	 * @arg2		int
	 * @return 	void
     */
	function set_default_font($font_name, $font_size = 10 ){

		$this->dfl_FontID = $this->get_font_id($font_name);
		$this->set_default_font_size($font_size);
	}

	function set_default_font_size($font_size = 10){
		$this->dfl_FontSize = ($font_size * 2);
	}
	
	function get_font_id($font_name = NULL){
		$i = 0;
		foreach(array_keys($this->FontIDS) as $font){
			if(strtolower($font)==strtolower($font_name)) return $i;
			$i++;
		}
		return 0;
	}

	function set_font_size($size){
		$size *= 2;
		$this->TextDecoration .= $this->_font_size($size);
	}

	/**
	 * Sets the text font and its size
     * @arg1		string	(font name)
     * @arg2		int		(font size)
	 * @return		void
	 */
	function set_font($font, $size = 10){
		$this->FontID = $this->get_font_id($font);
		$this->TextDecoration .= $this->_font($this->FontID);
		$this->set_font_size($size);
	}

	function new_page(){
		$this->MyRTF .= "\\page\n";
	}

	function color($ColorID=0){
		return "\\cf$ColorID ";
	}

	/**
     * Align text and images
     * (This is not intended to be used directly)
     * @arg1		keyword  (left|center|right|justify)
     */
	function align($where='left'){
		switch(strtolower($where)){
			case 'left': 		$this->MyRTF .= "\\ql ";	break;
			case 'center':		$this->MyRTF .= "\\qc ";	break;
			case 'right':		$this->MyRTF .= "\\qr ";	break;
			case 'justify':	    $this->MyRTF .= "\\qj ";	break;
			default:			$this->align('left');		break;
		}
	}

	/**
     * Insert an image and manages its alignment on the document
	 * ** TODO ** :: fix bug on image size handling
     * @arg1		string	(image filename)
	 * @arg2		int		(int 1-100)
	 * @arg3		keyword  (left|center|right|justify)
	 * @return		void
     */
	function add_image($image, $ratio, $align = 'left'){
		$file = @file_get_contents($image);
		if (empty($file)) return NULL;
		$this->align($align);
		$this->MyRTF .= "{";
		$this->MyRTF .= "\\pict\\jpegblip\\picscalex". $ratio ."\\picscaley". $ratio ."\\bliptag132000428 ";
		$this->MyRTF .= trim(bin2hex($file));
		$this->MyRTF .= "\n}\n";
		$this->paragraph();
	}
	
	function display($filename="document.rtf",$download=true){
		$this->MyRTF .= "\n}\n}\n";
		//return;
		if($download==true){
			header("Content-type: application/msword");
			header("Content-Lenght: ". sizeof($this->MyRTF));
	   	    header("Content-Disposition: inline; filename=". $filename);
		}
		print $this->MyRTF;
	}

    function config_margin($margl = 720, $margr = 720, $margt = 720, $margb = 720){
        $this->MyRTF .= "\\paperw12240\\paperh15840\n";
        $this->MyRTF .= "\\margl".$margl."\\margr".$margr."\\margt".$margt."\\margb".$margb."\n";
    }

	function set_table_font($font_name, $size){
		$size = $size*2;
		$this->MyRTF .= "\\f".$this->get_font_id($font_name)."\\fs".$size."\n";
	}

    function open_line(){
        $this->MyRTF .= "\\trowd\\trgaph35\\trautofit1\\trpaddfr3\\trpaddfl3\\trpaddft3\\trpaddfb3\\trpaddr55\\trpaddl55\\trpaddb15\\trpaddt35\n";
    }

    function close_line(){
        $this->MyRTF .= "\\pard \\intbl \\row\\pard\n";
        $this->previousResult=0;
    }

    function ln($times=1){
        for($i=0;$i<$times;$i++) $this->MyRTF .= "\\par\n";
    }

    function tab($times = 1){
        for($i=0;$i<$times;$i++) $this->MyRTF .= "\\tab\n";
    }

    /**
     * Method for creating the cells of the table
     * @arg1		text
     * @arg2		size of the cell
	 * @arg3		keyword  (left|center|right|justify)
	 * @arg4		background colour of the cell
	 * @return		void
     */
    function cell($msg, $sizeCell, $align="left", $cellColorId = NULL, $border=true){

		if($border) $this->MyRTF .= "\clbrdrt\brdrs\brdrw10
\clbrdrl\brdrs\brdrw10
\clbrdrb\brdrs\brdrw10
\clbrdrr\brdrs\brdrw10
";
        $sizeCell = (10800*($sizeCell/100)) + $this->previousResult;
        $this->previousResult = $sizeCell;
        if($cellColorId){$this->MyRTF .= "\\clcfpatraw".$cellColorId."\\clshdngraw10000\n";}
        $this->MyRTF .= "\\cellx".$sizeCell."\n";
        $this->MyRTF .= "\\pard \\intbl ";
        $this->align($align);        
		$this->MyRTF .= $msg." \\cell\n";
    }
}
