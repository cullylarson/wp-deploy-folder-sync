<?php

namespace Test\Wordpress\Deploy\FolderSync;

use Wordpress\Deploy\FolderSync;

class SyncTest extends \PHPUnit_Framework_TestCase {
    private $sourceFiles = [];
    private $destFiles = [];
    private $sharedFiles = [];
    private $folders = [];

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
    }

    public function tearDown() {
        /*
         * Remove the files
         */

        $allFiles = array_merge($this->sourceFiles, $this->destFiles, $this->sharedFiles);

        foreach($allFiles as $filename) {
            foreach($this->folders as $folderPath) {
                $filePath = "{$folderPath}/{$filename}";

                if(file_exists($filePath)) unlink($filePath);
            }
        }

        /*
         * Remove the folders
         */

        foreach($this->folders as $folderPath) {
            rmdir($folderPath);
        }
    }

    /**
     * Just a vanilla sync of two folders.
     */
    public function testSync() {
        $source = $this->folders['source'];
        $dest = $this->folders['dest'];

        $folderSync = new FolderSync($source, $dest);
        $success = $folderSync->sync();

        $this->assertTrue($success);

        // all of the source and shared files should be in the destination
        $sourceFilesMerged = array_merge($this->sourceFiles, $this->sharedFiles);
        foreach($sourceFilesMerged as $filename) {
            $filePath = "{$dest}/$filename";

            $this->assertFileExists($filePath);
        }

        // none of the destination files should be there
        foreach($this->destFiles as $filename) {
            $filePath = "{$dest}/$filename";

            $this->assertFileNotExists($filePath);
        }
    }

    /**
     * Excludes files from the sync.
     */
    public function testExclude() {
        $exclude = [$this->sourceFiles[0]];

        $source = $this->folders['source'];
        $dest = $this->folders['dest'];

        $folderSync = new FolderSync($source, $dest, ['exclude' => $exclude]);
        $success = $folderSync->sync();

        $this->assertTrue($success);

        // the excluded files should not be at the destination
        foreach($exclude as $filename) {
            $filePath = "{$dest}/$filename";

            $this->assertFileNotExists($filePath);
        }
    }

    public function testStatusCallback() {
        $statusWasCalled = false;

        $self = $this;

        $statusCallback = function($status) use (&$statusWasCalled, $self) {
            $statusWasCalled = true;
            $self->assertInstanceOf('Wordpress\Deploy\FolderSync\Status', $status);
        };

        $source = $this->folders['source'];
        $dest = $this->folders['dest'];

        $folderSync = new FolderSync($source, $dest);
        $success = $folderSync->sync($statusCallback);

        $this->assertTrue($success);

        $this->assertTrue($statusWasCalled);
    }

    /**
     * Do a sync, but don't delete any files.
     */
    public function testNoDelete() {
        $source = $this->folders['source'];
        $dest = $this->folders['dest'];

        $folderSync = new FolderSync($source, $dest, ['delete' => false]);
        $success = $folderSync->sync();

        $this->assertTrue($success);

        // all of the source, dest, and shared files should be in the destination
        $sourceFilesMerged = array_merge($this->sourceFiles, $this->sharedFiles, $this->destFiles);
        foreach($sourceFilesMerged as $filename) {
            $filePath = "{$dest}/$filename";

            $this->assertFileExists($filePath);
        }
    }
}