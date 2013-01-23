<?php
/* Infinity Disassembler */

/* ASM class abstracting a ASM file */
class ASM {
	//All instructions (it is assumed that each line is one instruction)
	private $instructions=array();
	//All non-comment and non-blank instructions - entries are refs to entries of ASM->instructions
	private $codeInstructions=array();
	//For each byte, this array gives a ref to the instruction that creates it
	private $fileInstructions=array();
	//Meta-block instructions which get inserted in the IDA header block
	private $metaInstructions=array();
	//Array to store references to a location in a file
	private $xrefsTo=array();
	
	//ASM file name
	private $fname="";
	//Architecture => field IDA06
	private $arch="generic";
	//Target file type => field IDA04
	private $type="raw";
	//Target (or source) file name => field IDA01
	private $bin_name="";
	//MD5 sum of target (this is checked after writing and ensures that the ASM representation still equals the input) => Field IDA02
	private $bin_md5="";
	//File version => Field IDA00
	private $ver=IDA_VERSION;
	//File size of input => Field IDA03
	private $bin_size=0;
	//Endianness for anything bigger than bytes. 0 is little endian (default), 1 big endian. LE means that dword 0x11223344 gets stored in RAM as 44332211. => Field IDA05
	private $endian=0;
	//Sections
	private $sections=array();
	
	//FileType instance
	private $ftInst=null;
	
	//Construct an empty ASM object
	private function __construct($filename) {
		$this->fname=$filename;
	}
	
	//Create a new, blank ASM in file $file
	public static function createInFile($file) {
		$inst=new ASM($file);
		$inst->write();
		return $inst;
	}
	
	//Create an ASM object with the contents in $file
	public static function createFromFile($file) {
		$inst=new ASM($file);
		$inst->read();
		return $inst;
	}
	
	//Write the object to the file
	public function write($target=NULL) {
		if($target===NULL) $target=$this->fname;
		$out=fopen($target,"w");
		if($out===false)
			err_out("ASM: can't open %s for writing",$target);
		
		$metablock=array();
		$metablock[]=new Instr_generic_comment("IDA00: Infinity Disassembler '%s'",$this->ver);
		$metablock[]=new Instr_generic_comment("IDA01: Input file:\t'%s'",$this->bin_name);
		$metablock[]=new Instr_generic_comment("IDA02: Input MD5:\t'%s'",$this->bin_md5);
		$metablock[]=new Instr_generic_comment("IDA03: Input size:\t'%d'",$this->bin_size);
		$metablock[]=new Instr_generic_comment("IDA04: File type:\t'%s'",$this->type);
		$metablock[]=new Instr_generic_comment("IDA05: File endianness:\t'%s'",(($this->endian==1)?"big":"little"));
		$metablock[]=new Instr_generic_comment("IDA06: File architecture:\t'%s'",$this->arch);
		
		foreach($metablock as $instr)
			fwrite($out,$instr->toString()."\n");
		foreach($this->metaInstructions as $instr)
			fwrite($out,$instr->toString()."\n");
		foreach($this->instructions as $instr)
			fwrite($out,$instr->toString()."\n");
		fclose($out);
	}
	
	//Read the instructions in the file into the object
	private function read() {
		$in=fopen($this->fname,"r");
		if($in===false)
			err_out("ASM: can't open %s for reading",$this->fname);
		$lines=0;
		while(!feof($in)) {
			$line=trim(fgets($in));
			if($line=="")
				continue;
			$inst=Instruction::getHandler($this->arch,$line,$this->endian);
			if($inst instanceof Instr_generic_comment && substr($inst->toString(),1,3)=="IDA") {
				$str=$inst->toString();
				$key=substr($str,4,2);
				preg_match("@'(.*)'@isU",$str,$hit);
				$val=$hit[1];
//				log_msg("Got IDA config comment '%s' for key %d, val is '%s'",$inst->toString(),$key,$val);
				switch($key) {
					case 0: //version, ignore for now. might be useful later for incompatible changes...
					break;
					case 1: //input file name
						$this->bin_name=$val;
					break;
					case 2: //input file MD5
						$this->bin_md5=$val;
					break;
					case 3: //input file size
						$this->bin_size=$val;
					break;
					case 4: //input file type
						$this->type=$val;
					break;
					case 5: //endianness
						if($val=="big")
							$this->endian=1;
						else
							$this->endian=0;
					break;
					case 6: //arch
						$this->arch=$val;
					break;
					case 98://FileType information with its own keys -> pass to ftype handler
					break;
					default:
						err_out("Unknown key %d in config, line '%s'",$key,$line);
				}
			} else {
				$this->appendInstruction($inst);
			}
			$lines++;
//			if($lines>5) break;
		}
		fclose($in);
	}
	
	//set metadata
	public function setMeta($bin_name,$bin_md5,$bin_size) {
		$this->bin_name=$bin_name;
		$this->bin_md5=$bin_md5;
		$this->bin_size=$bin_size;
	}
	
	//append a code instruction to the end
	public function appendInstruction($obj) {
		$index=sizeof($this->instructions);
		$obj->setASMObj($this);
		$obj->setIndex($index);
		$this->instructions[$index]=$obj;
		$this->codeInstructions[]=&$this->instructions[$index];
		$numBytes=strlen($obj->getBytes());
		$startpos=$obj->getFpos();
		for($i=$startpos;$i<$startpos+$numBytes;$i++)
			$this->fileInstructions[$i]=&$this->instructions[$index];
	}
	
	//write the machine code in the file $target
	public function compile($target) {
		$out=fopen($target,"wb");
		if($out===false)
			err_out("ASM: can't open %s for writing",$target);
		foreach($this->instructions as $instr)
			fwrite($out,$instr->getBytes());
		fclose($out);
	}
	
	//given a file offset, this returns the instruction whose byte encodes it
	public function getInstructionFromFileOffset($offset) {
		return $this->fileInstructions[$offset];
	}
	
	//get $length raw bytes (as int-array), starting at $offset
	public function getRawBytes($offset,$length) {
		$bytes=array();
		for($i=$offset;$i<$offset+$length;$i++) {
			$instBytes=$this->getInstructionFromFileOffset($i)->getRawBytes();
			$bytes=array_merge($bytes,$instBytes);
		}
		return array_slice($bytes,0,$length);
	}
	
	//set the file type handler
	public function setFileType($type) {
		$this->type=$type;
		$this->ftInst=new $type;
	}
	
	//instantiate a ftype object and let it write the information into the headerblock
	public function getFtypeInfo() {
		if($this->ftInst===null)
			err_out("FT is null!");
		$this->ftInst->populateASM();
	}
	
	//destroy all the Instruction objects to free memory
	public function destroy() {
		foreach($this->instructions as $k=>$v)
			unset($this->instructions[$k]);
	}
	
	//get the endianness
	public function getEndian() {
		return $this->endian;
	}
}
