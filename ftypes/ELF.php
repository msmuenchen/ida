<?
/* Infinity Disassembler */

/* *nix ELF loader */
class FileType_ELF extends FileType {
	public static function test($asm) {
		
	}
	public function readSections($asm) {
	}
	public function populateASM($asm) {
	}
}
FileType::registerFileType("ELF","FileType_ELF");
