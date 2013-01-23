<?
/* Infinity Disassembler */

/* *nix ELF loader */
class FileType_ELF extends FileType {
	public static function test($asm) {
		
	}
	public function readSections() {
	}
	public function populateASM() {
	}
}
FileType::registerFileType("ELF","FileType_ELF");
