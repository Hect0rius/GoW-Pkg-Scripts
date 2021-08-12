<?php

if(!file_exists('PHP-FileStream/FileStream.php')) {
    die("You need to git clone the Hect0rius/PHP-FileStream to GoW-Pkg-Scripts/PHP-FileStream\n");
}

require('PHP-FileStream/FileStream.php');

printf("Gears of War 1 Config Manager for Xbox 360 By Hect0rius @ Hect0r.com\n");

if($argc == 1 || ($argc == 2 && $argv[1] == 'help')) {
    printf("php -f gow.php [mode] [args]\n");
    printf("Modes:\n");
    printf("extract [input]\n");
    printf("            input config file, .ini, .int, langauge files.\n");
    printf("            extracts files to the same dir as the php file.\n");
    return;
}

switch($argv[1]) {
    case "extract":
        if($argc < 3) {
            printf("extract [input]\n");
            printf("            input config file, .ini, .int, langauge files.\n");
            printf("            extracts files to the same dir as the php file.\n");
            return;
        }
        
        // CHeck config exists.
        if(!file_exists($argv[2])) {
            printf("Could not find the package file: " . $argv[2] . "\n");
            return;
        }
        
        if(filesize($argv[2]) < 1) {
            printf("The input file is empty!\n");
            return;
        }
        
        $Out = __DIR__. '/';
        $Input = $argv[2];
        
        $IO = new FileStream($Input, "r");
        $IO->setPosition(0);
        $files = $IO->readUInt32(Endian::LOW);

        if($files <64) {
            // We have a config file.
            $files = ($files - 1) / 2;
            
            $buf = array();
            for($i = 0; $i < $files; $i++) {
                $x = $IO->readUInt32(Endian::LOW);
                $fn = $IO->readAsciiString($x);
                $x = $IO->readUInt32(Endian::LOW);
                $data = $IO->readAsciiString($x);
                
                $buf[trim($fn, '\0')] = trim($data, '\0');
                unset($data);
                unset($x);
                unset($fn);
            }
            
            $ohd = array();
            
            foreach($buf as $File => $Data) {
               $x = explode('\\', $File);               
               $Dir = "";
               
               $Name = $x[sizeof($x) - 1];
               $Name = substr($Name, 0, strlen($Name) - 1);
               
               foreach($x as $v) {
                   if($v !== "..") {
                       $Dir .= (strpos($v, '.') == false ? $v . '/':'');
                       if(strpos($v, '.') === false) {
                            if(!file_exists($Out . $Dir)) {
                                mkdir($Out . $Dir);
                            }
                       }
                   }
               }
               if(file_exists($Out . $Dir . $Name)) {
                   unlink($Out . $Dir . $Name);
               }
               $ohd[$Name] = $Out . $Dir;
               file_put_contents($Out . $Dir . $Name, substr($Data, 0, strlen($Data) - 2));
               printf("Exported %s...\n", $Out . $Dir . $Name);
               $IO->close();
               
            }
            if(file_exists($Out . '/contents.json')) {
                unlink($Out . '/contents.json');
            }
            file_put_contents($Out . 'contents.json', json_encode($ohd));
            printf("All Done :)\n");
            unset($IO);
            unset($buf);
            return;
        }
        
        printf("Not A Gears of War Config / Localisation File...\n");
        return;
        
    default:
        printf("Unable to continue, no valid mode selected.\n");
        return;
}

