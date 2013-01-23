<?php
/* Infinity Disassembler */

/* Generic Instruction */
abstract class Instruction {
	//Array of bytes as written to the disk
	protected $opcode=array();
	//Array of bytes as written to the disk
	protected $params=array();

	//Position in file
	private $file_pos=0;
	//Position in RAM when loaded
	private $ram_pos=0;
	
	//Instruction comment
	private $commentstr="";
	
	//Reverse-lookup array for instructions to classes
	private static $instructions=array();
	
	//Pointer to the asm file
	protected $asmfile=NULL;
	
	//Key in the ASM instructions array
	protected $index=-1;
	
	//Key in the ASM codeInstructions array
	protected $codeIndex=-1;
	
	//Endianness (this is important for the getASM() and compile functions. Default little endian
	protected $endian=0;
	
	//return the line which will be written to ASM file
	public function toString() {
		return sprintf("%-40s ; %s || %s",$this->getASM(),$this->commentstr,$this->getMetaString());
	}
	//return the textual (nasm) abstraction of the Instruction
	public abstract function getASM();
	//build from a line in ASM file
	public abstract static function fromASM($line);
	//return the string of bytes written to the file
	public function getBytes() {
		$stream=array();
		foreach($this->opcode as $byte)
			$stream[]=chr($byte);
		foreach($this->params as $byte)
			$stream[]=chr($byte);
		return implode("",$stream);
	}
	//returns array of the bytes this Instruction represents (each byte=one integer)
	public function getRawBytes() {
		return array_merge($this->opcode,$this->params);
	}
	//set the comment string
	public function setComment($str) {
		$this->commentstr=$str;
	}
	//get the comment string
	public function getComment() {
		return $this->commentstr;
	}
	//get the metadata string
	public function getMetaString() {
		$rawbytes=array_merge($this->opcode,$this->params);
		foreach($rawbytes as $k=>$v)
			$rawbytes[$k]=sprintf("%02X",$v);
		return sprintf("FPOS: '0x%016x'\tRAMPOS: '0x%016x'\tRAW: '%s'\tSTR: '%s'",$this->file_pos,$this->ram_pos,strtoupper(implode(" ",$rawbytes)),str_sanitize($this->getBytes()));
	}
	//set the file pos of the first byte
	public function setFpos($pos) {
		$this->file_pos=$pos;
	}
	//get the file pos of the first byte
	public function getFpos() {
		return $this->file_pos;
	}
	//set the RAM pos of the first byte
	public function setRpos($pos) {
		$this->ram_pos=$pos;
	}
	//get the RAM pos of the first byte
	public function getRpos() {
		return $this->ram_pos;
	}
	
	//get the length of the instruction in bytes
	public function getByteLength() {
		return sizeof($this->opcode)+sizeof($this->params);
	}
	
	//register an instruction handler
	public static function registerInstruction($begin,$arch,$class) {
		if(!isset(self::$instructions[$begin]))
			self::$instructions[$begin]=array();
		self::$instructions[$begin][]=array("arch"=>$arch,"class"=>$class);
		log_msg("Registered instruction handler %s on %s for %s",$class,$arch,$begin);
	}
	
	public static function getHandler($arch,$line,$endian=0) {
//		log_msg("Getting handler for line '%s' on arch '%s'",$line,$arch);
		$found=NULL;
		foreach(self::$instructions as $begin=>$handlers) {
			if($begin==substr($line,0,strlen($begin))) {
//				log_msg("Found %d handler entries for %s",sizeof($handlers),$begin);
				foreach($handlers as $handler) {
					//skip if architecture doesn't match and is not generic
					if($handler["arch"]!=$arch && $handler["arch"]!="generic")
						continue;
					//skip if already found a handler and now encountered generic
					if($found!==NULL && $handler["arch"]=="generic")
						continue;
					$found=$handler["class"];
				}
				if($found===NULL)
					err_out("No matching handler found for %s",$begin);
			}
		}
		if($found===NULL)
			err_out("Unknown instruction line '%s'",$line);
//		else
//			log_msg("Using handler %s",$found);
		
		//split comment text, we'll assign it later...
		$cpos=strpos($line,";");
		if($cpos!==false && ($found!="Instr_generic_comment")) {	//found a comment token, and the line is not a comment line
			$comment=trim(substr($line,$cpos+1));
			$line=trim(substr($line,0,$cpos));
//			log_msg("Found a comment: '%s', line is '%s'",$comment,$line);
			$infopos=strpos($comment,"||");
			if($infopos!==false) {
				$infoblock=trim(substr($comment,$infopos+2));
				$comment=trim(substr($comment,0,$infopos));
//				log_msg("Found infoblock in comment: '%s', comment is '%s'",$infoblock,$comment);
			}
		} else {
			$comment="";
			$infoblock="";
		}
		$inst=$found::fromASM($line,$endian);
		$inst->setComment($comment);
		if($infoblock!="") {
			if(preg_match("@FPOS: '0x(.*)'@isU",$infoblock,$hit))
				$inst->setFpos(hexdec($hit[1]));
			if(preg_match("@RAMPOS: '0x(.*)'@isU",$infoblock,$hit))
				$inst->setRpos(hexdec($hit[1]));
		}
		return $inst;
	}
	
	//set the ASM object this instruction belongs to. this allows back-referencing e.g. when you need the endianness in "convert-to-x" commands
	public function setASMObj($asm) {
		$this->asmfile=$asm;
	}
	//set the index of this instruction in the parent's Instruction array
	public function setIndex($index) {
		$this->index=$index;
	}
	//set the index of this instruction in the parent's codeInstruction array
	public function setCodeIndex($index) {
		$this->codeIndex=$index;
	}
	public function getIndex() {
		return $this->index;
	}
	public function getCodeIndex() {
		return $this->codeIndex;
	}
}
