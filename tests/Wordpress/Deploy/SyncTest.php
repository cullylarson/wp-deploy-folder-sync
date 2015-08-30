<?php

namespace Test\Wordpress\Deploy\FolderSync;

use Wordpress\Deploy\FolderSync;

class SyncTest extends \PHPUnit_Framework_TestCase {
    private $sourceFiles;
    private $destFiles;
    private $sharedFiles;
    private $folders;

    public function setUp() {
        $sourceFiles = $this->sourceFiles = [
            "source-file-1",
            "source-file-2",
        ];

        $destFiles = $this->destFiles = [
            "dest-file-1",
            "dest-file-2",
        ];

        $sharedFiles = $this->sharedFiles = [
            "shared-file-1",
            "shared-file-2",
        ];

        $folders = $this->folders = [
            "source" => TESTS_WPDFS_TMP_FOLDER . "/source/",
            "dest" => TESTS_WPDFS_TMP_FOLDER . "/dest/",
        ];

        /*
         * create the folders
         */

        foreach($folders as $folderPath) {
            mkdir($folderPath);
        }

        /*
         * Create the files
         */

        $sourceFilesMerged = array_merge($sourceFiles, $sharedFiles);
        $destFilesMerged = array_merge($destFiles, $sharedFiles);

        foreach($sourceFilesMerged as $filename) {
            $path = $folders['source'] . "/" . $filename;
            touch($path);
        }

        foreach($destFilesMerged as $filename) {
            $path = $folders['dest'] . "/" . $filename;
            touch($path);
        }

        /*
         * Teardown stuff
         */

        register_shutdown_function(function() use($sourceFiles, $destFiles, $sharedFiles, $folders) {
            return;//stub
            /*
             * Remove the files
             */

            $allFiles = array_merge($sourceFiles, $destFiles, $sharedFiles);

            foreach($allFiles as $filename) {
                foreach($folders as $folderPath) {
                    $filePath = "{$folderPath}/{$filename}";

                    if(file_exists($filePath)) unlink($filePath);
                }
            }

            /*
             * Remove the folders
             */

            foreach($folders as $folderPath) {
                rmdir($folderPath);
            }
        });
    }

    public function testSync() {
        $source = $this->folders['source'];
        $dest = $this->folders['dest'];

        $folderSync = new FolderSync($source, $dest);
        $folderSync->sync();

        // all of the file source and shared files should be in the destination
        $sourceFilesMerged = array_merge($this->sourceFiles, $this->sharedFiles);
        foreach($sourceFilesMerged as $filename) {
            $filePath = "{$dest}/$filename";

            $this->assertFileExists($filePath);
        }

        // none of the destination files should be there
        foreach($this->destFiles as $filename) {
            $filePath = "{$dest}/$filename";

            $this->assertFileNotExists($filename);
        }
    }
}