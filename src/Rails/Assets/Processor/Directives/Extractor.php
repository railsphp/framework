<?php
namespace Rails\Assets\Processor\Directives;

class Extractor
{
    const HEADER_PATTERN = '/
        \A (
            (?m:\s*) (
                (\/\* (?m:(.|\v)*?) \*\/) |
                (\#\#\# (?m:.*?) \#\#\#) |
                (\/\/ .* \n?)+ |
                (\# .* \n?)+
            )
        )+
    /x';
    
    const DIRECTIVE_PATTERN = '/^ \W* = \s* (\w+.*?) (\*\/)? $/x';
    
    /**
     * Extract directives.
     * Returns an array whose first value is an array containing
     * directives data, and second value is the file contents with
     * stripped directive lines (if any).
     */
    public static function extractFromFile($file)
    {
        $contents = file_get_contents($file->originalFilePath());
        
        preg_match(self::HEADER_PATTERN, $contents, $m);
        if (!$m) {
            return [[], $contents];
        }
        
        unset($contents);
        $lines      = file($file->originalFilePath(), FILE_IGNORE_NEW_LINES);
        $directives = [];
        
        foreach (preg_split('/\v/', $m[0]) as $lineNum => $line) {
            if (preg_match(self::DIRECTIVE_PATTERN, $line, $m)) {
                $dirValue = array_filter(explode(' ', $m[1]));
                $directives[] = [
                    'command'  => $dirValue[0],
                    'argument' => isset($dirValue[1]) ? $dirValue[1] : null,
                    'line'     => $lineNum + 1
                ];
                
                unset($lines[$lineNum]);
            }
        }
        
        return [$directives, implode("\n", $lines)];
    }
}
