<?php
/* Infinity Disassembler */

/* Instruction places raw bytes */
class Instr_generic_data extends Instruction {
	//this stores which bytes belong to which section, as the "data" instruction also handles "array-like" stuff
	protected $parts=array();
	
	//this stores the basic unit type (byte, word, dword, (qword))
	protected $width=0;
	
	function __construct($vals,$parts,$width,$endian) {
		$this->endian=$endian;
		$this->opcode=$vals;
		$this->parts=$parts;
		$this->width=$width;
	}
	public function getASM() {
		
	}
	
	//construct a dX object from the asm instruction, using the file endianness to read the byte order
	//!!! because of this, do not change the byte order after stage 2 where it is determined, else you WILL break stuff seriously !!!
	public static function fromASM($line,$endian=0) {
		$line=trim($line);		//remove spaces
		$data=substr($line,3);	//remove the "db " instruction part
		if($data=="")
			err_out("no data supplied to data instruction");
		
		$width=substr($line,1,1);
		switch($width) {
			case "b": $width=1; $unitname="BYTE"; break;
			case "w": $width=2; $unitname="WORD"; break;
			case "d": $width=4; $unitname="DWORD"; break;
			default: err_out("Unknown width specifier %s for data instruction",$width);
		}
		log_msg("Parsing data '%s', width is %d",$data,$width);
		
		$parts=array(0=>array("type"=>"","data"=>""));
		$el=0;
		$pos=0;
		$mode=0;
		//tokenize into parts
		while($data!="") {
			$char=substr($data,0,1);
			$data=substr($data,1);
//			log_msg("Parsing character %s from position %d, element is %d and mode is %d",$char,$pos,$el,$mode);
			switch($char) {
				case "\"": //start a new string
					if($mode==0) {
//						log_msg("Encountered a string begin");
						$parts[$el]["data"]="";
						$parts[$el]["type"]="string";
						$mode=1;
					} elseif($mode==1) {
//						log_msg("Encountered a string end, final string is '%s'",$parts[$el]["data"]);
						$mode=0;
					} else
						err_out("Invalid input during parsing '%s'",$line);
				break;
				case ",": //new element, unless we're in string-detection mode
					if($mode==1) {
//						log_msg("Detected comma inside string");
						$parts[$el]["data"].=$char;
					} else {
//						log_msg("Detected comma outside string");
						$el++;
						$parts[$el]=array("type"=>"","data"=>"");
					}
				break;
				case "\\": //escape sequence. valid only in strings
					if($mode=="1") {
//						log_msg("hit an escape sequence");
						if($data[0]=="\\") { //escape a \
							log_msg("Escaped \\");
							$parts[$el]["data"].="\\";
							$data=substr($data,1);
						} elseif($data[0]=="\"") { //escape a "
//							log_msg("Escaped \"");
							$parts[$el]["data"].="\"";
							$data=substr($data,1);
						} elseif($data[0]=="x") { //escape hex sequence (unprintable character)
							$hexcode=substr($data,1,2);
							if(preg_match("@[^0-9A-F]@isU",$hexcode))	
								err_out("Invalid characters in escaped hex sequence '%s'",$hexcode);
//							log_msg("Escaping hex sequence '%s'",$hexcode);
							$parts[$el]["data"].=chr(hexdec($hexcode));
							$data=substr($data,3);
						} elseif($data[0]=="r") { //escaped linebreak
//							log_msg("Escaped \\r");
							$parts[$el]["data"].="\r";
							$data=substr($data,1);
						} elseif($data[0]=="n") { //escaped linebreak
//							log_msg("Escaped \\n");
							$parts[$el]["data"].="\n";
							$data=substr($data,1);
						} elseif($data[0]=="t") { //escaped tab
//							log_msg("Escaped \\t");
							$parts[$el]["data"].="\t";
							$data=substr($data,1);
						} elseif($data[0]=="0") { //escaped nullbyte
//							log_msg("Escaped \\0");
							$parts[$el]["data"].="\0";
							$data=substr($data,1);
						}
					} else
						err_out("Encountered unexpected \\ outside of a string!");
				break;
				default:
					$parts[$el]["data"].=$char;
			}
			$pos++;
		}
		
		//determine type of unknown tokens and construct the DATAUNIT object which then gives us the raw values
		$allbytes=array();
		foreach($parts as $idx=>$part) {
			if($part["type"]=="") { //Unknown type
				switch(substr($part["data"],-1,1)) {
					case "o": $part["type"]="oct"; break;
					case "b": $part["type"]="bin"; break;
					case "h": $part["type"]="hex"; break;
					default: $part["type"]="int"; break;
				}
			}
			switch($part["type"]) {
				case "oct": $part["obj"]=$unitname::fromOct($part["data"],$endian); break;
				case "bin": $part["obj"]=$unitname::fromBin($part["data"],$endian); break;
				case "hex": $part["obj"]=$unitname::fromHex($part["data"],$endian); break;
				case "int": $part["obj"]=$unitname::fromInt($part["data"],$endian); break;
				case "string": $part["obj"]=$unitname::fromString($part["data"],1); break; //strings are always big-endian, they're mapped 1:1 into the file
			}
			$part["bytes"]=$part["obj"]->toArray(1);
			unset($part["obj"]); //no need to waste more memory than we already do
			$allbytes=array_merge($allbytes,$part["bytes"]);
			$parts[$idx]=$part;
		}
		
		print_r($parts);
		print_r($allbytes);
	}
}
Instruction::registerInstruction("db","generic","Instr_generic_data");
Instruction::registerInstruction("dw","generic","Instr_generic_data");
Instruction::registerInstruction("dd","generic","Instr_generic_data");
//no 64-bit support yet, but keep it for later. actually should be trivial to implement but... fuck it
//Instruction::registerInstruction("dq","generic","Instr_generic_data");