<?php
class ListDirectory {
    private function __construct() {}

    public static function getFiles($pattern = '*', $subDir = false) {
        $files = new GlobIterator(__DIR__ . "/$pattern");
        $fileList = [];

        foreach($files as $file) {
            if (!$subDir && $file->getType() === "dir") continue;
            $fileList[] = [
                'name' => $file->getBasename("." . $file->getExtension()),
                'ext' => $file->getExtension(),
                'type' => $file->getType() === "dir" ? 'directory' : mime_content_type($file->getRealPath()),
                'size' => $file->getSize(),
                'date' => (new DateTimeImmutable())->setTimestamp($file->getMTime())->format('Y-m-d H:i:s P'),
            ];
        }
        return $fileList;
    }
}