<?php

$year = date('Y');
$month = date('m');
$relativeDir = 'uploads/participants/' . $year . '/' . $month;
$absoluteDir = __DIR__ . '/' . $relativeDir;

if (!is_dir($absoluteDir)) {
    if (!@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        $relativeDir = 'uploads/participants';
        $absoluteDir = __DIR__ . '/' . $relativeDir;
        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            die("Could not create directory\n");
        }
    }
}

$fileName = 'test_upload.pdf';
$absolutePath = $absoluteDir . '/' . $fileName;

$tmpName = __DIR__ . '/dummy.pdf';

// Because we aren't using an actual HTTP POST upload in CLI, move_uploaded_file won't work.
// We must simulate it or use copy() to test permissions.
// BUT wait, move_uploaded_file FAILS if the file was not uploaded via HTTP POST!
// That's exactly how move_uploaded_file works!
