<?php
/* Infinity Disassembler */

/* Data types: BYTE, WORD, DWORD, (QWORD) */

abstract class DATAUNIT_MB {
	//width of the element (1=BYTE,2=WORD,4=DWORD,8=QWORD (QWORD not supported on 32-bit host platforms!)
	//protected $width=-1;
	//the bytes in the multibyte structure, big-endian order (0x11223344 will be 0=11 1=22 3=33 4=44)
	protected $bytes=array();
	
	protected function __construct($bytes) {
		$this->bytes=$bytes;
	}
	//populate and return an instance with the bytes. If shorter, pad with \0 at the end
	public static function fromString($str,$endian=0) {
//		log_msg("Creating %d-wide multibyte from string '%s' (len: %d) in order %s",static::$width,str_sanitize($str),strlen($str),(($endian==1)?"big":"little"));
		
		//Sanity check: length
		if(strlen($str)<static::$width) {
			$str=str_pad($str,static::$width,"\0");
//			log_msg("Padded string to %d bytes, now is '%s'",static::$width,str_sanitize($str));
		} elseif(strlen($str)>static::$width)
			err_out("Tried to initialize %d-wide multibyte with %d-wide string '%s'",static::$width,strlen($str),str_sanitize($str));
		
		//Endianness swap
		if($endian==0) {
			$str=strrev($str);
//			log_msg("Reversed string order, now is '%s'",str_sanitize($str));
		}
		
		$bytes=array();
		for($i=0;$i<static::$width;$i++) {
			$bytes[$i]=ord(substr($str,$i,1));
		}
		return static::getInst($bytes);
	}
	public function toRawString($endian=0) {
		if($endian==0) {
//			log_msg("Reversed string order for output!");
			$bytes=array_reverse($this->bytes);
		} else {
			$bytes=$this->bytes;
		}
		
		$dstr="";	//assembled string for debug-display
		for($i=0;$i<static::$width;$i++)
			$dstr.=chr($this->bytes[$i]);
			
		$str="";	//assembled string for return
		for($i=0;$i<static::$width;$i++) {
			// Only print harmless characters, but avoid expensive regexes, we're already crappy enough in performance!
			if($bytes[$i]<0x30 || $bytes[$i]>0x7A || ($bytes[$i]>0x39 && $bytes[$i]<0x41) || $bytes[$i]==0x5C || $bytes[$i]==0x60)
				$str.=sprintf("\\%02X",$bytes[$i]);
			else
				$str.=chr($bytes[$i]);
		}
//		log_msg("Requested %d-wide string '%s' (BE) in order %s",static::$width,str_sanitize($dstr),(($endian==1)?"big":"little"));
		return $str;
	}
	public function toString($endian) {
		return "\"".$this->toRawString($endian)."\"";
	}
	
	public static function fromInt($str,$endian=0) {
		//int/oct can, in contrast to bin/str/hex not easily be splitted into multiple bytes - so convert the given value to hex and let fromHex do the job
		//check if there's anything other than 0-9 in the string, is_numeric and is_integer won't work here
		if(preg_match("@[^0-9]@isU",$str)!==0)
			err_out("'%s' is not a valid integer string",str_sanitize($str));
//		log_msg("Trying to load int %s as %d-wide multibyte in order %s",$str,static::$width,(($endian==1)?"big":"little"));
		//wrap in the format fromHex expects
		return static::fromHex("0".dechex(intval($str))."h",$endian);
	}
	public	function toInt($endian=0) {
		//I know, this is strange. The point is that the math stuff down below needs it reversed...
		if($endian==0) {
			$bytes=$this->bytes;
//			log_msg("Reversed byte order for output!");
		} else {
			$bytes=array_reverse($this->bytes);
		}
		
		$dstr="";	//assembled string for debug-display
		for($i=0;$i<static::$width;$i++)
			$dstr.=chr($this->bytes[$i]);
			
		$int=0;
		for($i=0;$i<static::$width;$i++) {
			$int+=$bytes[$i]*pow(2,8*$i);
		}
//		log_msg("Requested %d-wide string '%s' (BE) as int %d in order %s",static::$width,str_sanitize($dstr),$int,(($endian==1)?"big":"little"));
		return $int;
	}
	public static function fromHex($str,$endian=0) {
//		log_msg("Trying to load hex %s as %d-wide multibyte in order %s",$str,static::$width,(($endian==1)?"big":"little"));
		$str=trim($str);
		$val=substr($str,0,-1);
		
		//check if it's supposed to be a hex string, check if it begins with a number (Ah-Fh is invalid, must start with 0 and not a letter!) and check if it contains anything but hex numbers
		if(substr($str,-1,1)!="h" ||  preg_match("@^[A-F]@isU",$val)!==0 || preg_match("@[^0-9A-F]@isU",$val)!==0)
			err_out("'%s' is not a valid hex string",str_sanitize($str));
		
		//remove unneeded 0s from the beginning
		while(substr($val,0,1)=="0") {
			$val=substr($val,1);
		}
		
		//check if we're too long
		if(strlen($val)>2*static::$width)
			err_out("Value %d (0x%02X, %d bytes long) is too large (maximum: %d bytes)",hexdec($val),hexdec($val),ceil((strlen($val)/2)),static::$width);
		
		//pad up to even length
		$val=str_pad($val,static::$width*2,"0",STR_PAD_LEFT);
		
		//split
		$bytes=array();
		for($i=0;$i<static::$width;$i++) {
			$bytes[]=hexdec(substr($val,$i*2,2));
		}
		
		//if needed, swap order
		if($endian==0) {
//			log_msg("Reversed string order at creation!");
			$bytes=array_reverse($bytes);
		}
		return static::getInst($bytes);
	}
	public function toRawHex($endian) {
		return dechex($this->toInt($endian));
	}
	public function toHex($endian) {
		return $this->toRawHex($endian)."h";
	}
	public static function fromOct($str,$endian=0) {
		//int/oct can, in contrast to bin/str/hex not easily be splitted into multiple bytes - so convert the given value to hex and let fromHex do the job
		//check if there's anything other than 0-7 in the string, is_numeric and is_integer won't work here
		$str=trim($str);
		$val=substr($str,0,-1);
		if(substr($str,-1,1)!="o" || preg_match("@[^0-7]@isU",$val)!==0)
			err_out("'%s' is not a valid octal string",str_sanitize($str));
//		log_msg("Trying to load oct %s as %d-wide multibyte in order %s",$str,static::$width,(($endian==1)?"big":"little"));
		//wrap in the format fromHex expects
		return static::fromHex("0".dechex(octdec($val))."h",$endian);
	}
	public function toRawOct($endian) {
		return decoct($this->toInt($endian));
	}
	public function toOct($endian) {
		return $this->toRawOct($endian)."o";
	}
	public static function fromBin($str,$endian=0) {
		//convert it to hex to keep down the conversion code...
		//check if there's anything other than 0-1 in the string
		$str=trim($str);
		$val=substr($str,0,-1);
		if(substr($str,-1,1)!="b" || preg_match("@[^01]@isU",$val)!==0)
			err_out("'%s' is not a valid binary string",str_sanitize($str));
//		log_msg("Trying to load bin %s as %d-wide multibyte in order %s",$str,static::$width,(($endian==1)?"big":"little"));
		//wrap in the format fromHex expects
		return static::fromHex("0".dechex(bindec($val))."h",$endian);
	}
	public function toRawBin($endian) {
		return decbin($this->toInt($endian));
	}
	public function toBin($endian) {
		return $this->toRawBin($endian)."h";
	}
	public function toArray($endian=0) {
		if($endian==0)
			$bytes=array_reverse($this->bytes);
		else
			$bytes=$this->bytes;
		return $bytes;
	}
	public static function fromArray($str) {
	}
}

class BYTE extends DATAUNIT_MB {
	protected static $width=1;
	public static function getInst($val) {
		return new BYTE($val);
	}
}

class WORD extends DATAUNIT_MB {
	protected static $width=2;

	public static function getInst($val) {
		return new WORD($val);
	}
}

class DWORD extends DATAUNIT_MB {
	protected static $width=4;
	
	public static function getInst($val) {
		return new DWORD($val);
	}
}