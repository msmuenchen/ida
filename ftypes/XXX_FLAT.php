<?
/* Infinity Disassembler */

/* Flat binary file type */
class FileType_FLAT extends FileType {
	//Always return true. FLAT is the generic fallback.
	public static function test($asm) {
		return true;
	}
	public function readSections($asm) {
	}
	public function populateASM($asm) {
	}
}
FileType::registerFileType("XXX_FLAT","FileType_FLAT");
