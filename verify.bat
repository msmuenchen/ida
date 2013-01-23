@echo off
echo Disassembling
php ida.php 1 input\%1.exe
echo Copywriting
php ida.php 4 input\%1.exe
echo Assembling...
nasm -f bin -o input\ver.exe input\%1.asm
echo Done
md5sums input\%1.exe input\ver.exe
