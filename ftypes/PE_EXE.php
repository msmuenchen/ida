<?
/* Infinity Disassembler */

/* MS PE EXE loader */
/* Resource: Microsoft Portable Executable and Common Object File Format Specification, v8.2 */
class FileType_PE_EXE extends FileType {
	
	private static $pe_machines=array(0=>		"IMAGE_FILE_MACHINE_UNKNOWN",
	                                  0x1d3=>	"IMAGE_FILE_MACHINE_AM33",
									  0x8664=>	"IMAGE_FILE_MACHINE_AMD64",
									  0x1c0=>	"IMAGE_FILE_MACHINE_ARM",
									  0x1c4=>	"IMAGE_FILE_MACHINE_ARMV7",
									  0xebc=>	"IMAGE_FILE_MACHINE_EBC",
									  0x14c=>	"IMAGE_FILE_MACHINE_I386",
									  0x200=>	"IMAGE_FILE_MACHINE_IA64",
									  0x9041=>	"IMAGE_FILE_MACHINE_M32R",
									  0x266=>	"IMAGE_FILE_MACHINE_MIPS16",
									  0x366=>	"IMAGE_FILE_MACHINE_MIPSFPU",
									  0x466=>	"IMAGE_FILE_MACHINE_MIPSFPU16",
									  0x1f0=>	"IMAGE_FILE_MACHINE_POWERPC",
									  0x1f1=>	"IMAGE_FILE_MACHINE_POWERPCFP",
									  0x166=>	"IMAGE_FILE_MACHINE_R4000",
									  0x1a2=>	"IMAGE_FILE_MACHINE_SH3",
									  0x1a3=>	"IMAGE_FILE_MACHINE_SH3DSP",
									  0x1a6=>	"IMAGE_FILE_MACHINE_SH4",
									  0x1a8=>	"IMAGE_FILE_MACHINE_SH5",
									  0x1c2=>	"IMAGE_FILE_MACHINE_THUMB",
									  0x169=>	"IMAGE_FILE_MACHINE_WCEMIPSV2");
	
	//test if this is a valid PE EXE
	//Check for the MZ signature and the PE signature at the given offset
	public static function test($asm) {
		//MZ signature: MZ as first two bytes of file
		$sig=$asm->getRawBytes(0,2);
		if(chr($sig[0])!="M" || chr($sig[1])!="Z")
			return false;
		
		//Offset to PE header, TODO: Is this really just a WORD?!
		$pe_offset_raw=$asm->getRawBytes(0x3C,2);
		$pe_offset=le_to_int($pe_offset_raw[0],$pe_offset_raw[1]);
		log_msg("Offset of PE header: 0x%04x",$pe_offset);
		
		//PE header signature: PE\0\0
		$pe_sig=$asm->getRawBytes($pe_offset,4);
		if(chr($pe_sig[0])!="P" || chr($pe_sig[1])!="E" || $pe_sig[2]!=0 || $pe_sig[3]!=0)
			return false;
		
		return true;
	}
	public function readSections() {
	}
	
	//parse what we can get out of the asm file and format the structures
	public function populateASM() {
		//this is the MZ sig
		$this->asmfile->getInstructionFromFileOffset(0)->upscale();
		$this->asmfile->getInstructionFromFileOffset(0)->changeFormat(0,"string");
		
		//this is the PE header offset, DWORD
		$this->asmfile->getInstructionFromFileOffset(0x3C)->upscale();
		$this->asmfile->getInstructionFromFileOffset(0x3C)->upscale();
		$this->asmfile->getInstructionFromFileOffset(0x3C)->changeFormat(0,"hex");
		$pe_sig=$this->asmfile->getInstructionFromFileOffset(0x3C)->getRawValue(0);
		log_msg("PE sig at 0x%08X",$pe_sig);
		
		//PE header
		$this->asmfile->getInstructionFromFileOffset($pe_sig+0)->upscale();
		$this->asmfile->getInstructionFromFileOffset($pe_sig+0)->upscale();
//		$this->asmfile->getInstructionFromFileOffset($pe_sig+0)->changeFormat(0,"string");
		$this->asmfile->getInstructionFromFileOffset($pe_sig+0)->setComment("pe.magic");
		$pe_base=$pe_sig+4;
		
		//PE struct
		//Machine, WORD
		$this->asmfile->getInstructionFromFileOffset($pe_base+0)->upscale();
//		$this->asmfile->getInstructionFromFileOffset($pe_base+0)->changeFormat(0,"hex");
		$pedata["machine"]=$this->asmfile->getInstructionFromFileOffset($pe_base+0)->getRawValue(0);
		if(!isset(self::$pe_machines[$pedata["machine"]]))
			$pedata["machine"]=0; //unknown
		$this->asmfile->getInstructionFromFileOffset($pe_base+0)->setComment("pe.machine = %x (%s)",$pedata["machine"],self::$pe_machines[$pedata["machine"]]);
		//NumberOfSections, WORD
		$this->asmfile->getInstructionFromFileOffset($pe_base+2)->upscale();
//		$this->asmfile->getInstructionFromFileOffset($pe_base+2)->changeFormat(0,"hex");
		$val=$this->asmfile->getInstructionFromFileOffset($pe_base+2)->getRawValue(0);
		$this->asmfile->getInstructionFromFileOffset($pe_base+2)->setComment("pe.numberofsections = %d",$val);
		//TimeDateStamp, DWORD
		$this->asmfile->getInstructionFromFileOffset($pe_base+4)->upscale();
		$this->asmfile->getInstructionFromFileOffset($pe_base+4)->upscale();
//		$this->asmfile->getInstructionFromFileOffset($pe_base+4)->changeFormat(0,"int");
		$val=$this->asmfile->getInstructionFromFileOffset($pe_base+4)->getRawValue(0);
		$this->asmfile->getInstructionFromFileOffset($pe_base+4)->setComment("pe.timedatestamp = %d",$val);
		
		
		/*
		
		$pe_header["NumberOfSections"]=le_to_int($pe_header_raw[2],$pe_header_raw[3]);
		//todo: fix time stamp
		$pe_header["TimeDateStamp"]=le_to_int($pe_header_raw[4],$pe_header_raw[5],$pe_header_raw[6],$pe_header_raw[7]);
		$pe_header["TimeDateStamp_str"]=date("d.m.Y H:i:s",$pe_header["TimeDateStamp"]);
		$pe_header["PointerToSymbolTable"]=le_to_int($pe_header_raw[8],$pe_header_raw[9],$pe_header_raw[10],$pe_header_raw[11]);
		$pe_header["NumberOfSymbols"]=le_to_int($pe_header_raw[12],$pe_header_raw[13],$pe_header_raw[14],$pe_header_raw[15]);
		$pe_header["SizeOfOptionalHeader"]=le_to_int($pe_header_raw[16],$pe_header_raw[17]);
		$pe_header["Characteristics"]=le_to_int($pe_header_raw[18],$pe_header_raw[19]);
		print_r($pe_header);
		return true;*/
	}
}
FileType::registerFileType("PE_EXE","FileType_PE_EXE");
