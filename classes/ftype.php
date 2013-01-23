<?php
/* Infinity Disassembler */

/* Generic File Type */
abstract class FileType {
	//Test if the file in $asm is of this specific file type
	public abstract static function test($asm);
	
	//Populate the ASM object with the information from the file
	public abstract function populateASM($asm);
	
	//Read section information from file type
	public abstract function readSections($asm);
	
	//Registered file type handlers
	private static $handlers=array();
	
	//get the file type of the given asm file
	public static function getFromASM($asm) {
		log_msg("Looping through handlers");
		$found=NULL;
		foreach(self::$handlers as $handler) {
			log_msg("Testing %s",$handler);
			$res=$handler::test($asm);
			log_msg("Result is %d",$res);
			if($res===true) {
				$found=$handler;
				break;
			}
		}
		if($found===NULL)
			err_out("Could not determine file type");
		else
			log_msg("File type is %s",$found);
		return $found;
	}
	
	//register a file type handler
	public static function registerFileType($name,$class) {
		if(!isset(self::$handlers))
			self::$handlers=array();
		self::$handlers[$name]=$class;
		log_msg("Registered handler %s for %s",$class,$name);
	}
}