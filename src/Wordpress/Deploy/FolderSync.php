<?php

namespace Wordpress\Deploy;

use Wordpress\Deploy\FolderSync\Status;
use Wordpress\Deploy\FolderSync\Options;

class FolderSync {
    /**
     * @var string
     */
    private $source;
    /**
     * @var string
     */
    private $dest;
    /**
     * @var Options
     */
    private $options;

    /**
     * The source and destination strings are values that could be passed to
     * rsync/scp (e.g. username@host:path/to/a/folder, or simply path/to/a/folder).
     *
     * Options:
     *
     * - <b>delete</b> <i>boolean (Default: true)</i> Whether to delete files when syncing
     *   (e.g. if a file no longer exists at the source, then delete it from the
     *   destination)
     *
     * - <b>exclude</b> <i>array (Default: [])</i> An array of patterns to exclude from
     *   the sync.  See the rsync man page for details on --exclude.
     *
     * @param string    $source     Must have a trailing slash (e.g. /)
     * @param string    $dest       Must have a trailing slash (e.g. /)
     * @param array     $options
     * @throws \InvalidArgumentException
     */
    public function __construct($source, $dest, array $options=[]) {
        // make sure source and dest end in a slash
        if( !preg_match(";/^;", $source) ) throw new \InvalidArgumentException("'source' must end with a backslash.");
        if( !preg_match(";/^;", $dest) ) throw new \InvalidArgumentException("'dest' must end with a backslash.");

        $this->source = $source;
        $this->dest = $dest;
        $this->options = new Options($options);
    }

    /**
     * @param \Closure|null $statusCallback
     * @return bool
     * @throws \RuntimeException
     */
    public function sync(\Closure $statusCallback=null) {
        $this->ensureRsyncCommand();

        /*
         * Run sync
         */

        $this->doStatusCallback(
            new Status(sprintf("Syncing source (%s) to destination (%s)", $this->source, $this->dest)),
            $statusCallback);

        $command = $this->buildCommand();

        exec($command, $output, $ret);

        /*
         * Get output
         */

        $this->doStatusCallback(
            new Status(implode("\n", $output), Status::MT_RAW_OUTPUT),
            $statusCallback);

        /*
         * Process errors
         */

        if(!$ret) {
            $this->doStatusCallback(
                new Status("Something went wrong. Sync did not complete successfully.", Status::MT_ERROR),
                $statusCallback);
        }

        return boolval($ret);
    }

    /**
     * @throws \RuntimeException
     */
    private function ensureRsyncCommand() {
        exec("which rsync", $output, $ret);

        // doesn't exist
        if(!$ret) {
            throw new \RuntimeException("Could not find the 'rsync' command on your system.");
        }
    }

    private function doStatusCallback(Status $status, \Closure $statusCallback) {
        if(!$statusCallback) return;
        else $statusCallback($status);
    }

    private function buildCommand() {
        $baseCommand = "rsync";
        $baseOpts = "--recursive --verbose --compress --links --no-g --no-o";
        $excludeOpts = $this->buildExclude();
        $userOpts = "";

        if($this->options->shouldDelete()) $userOpts .= " --delete --force";

        return "{$baseCommand} {$baseOpts} {$userOpts} {$excludeOpts} {$this->source} {$this->dest}";
    }

    private function buildExclude() {
        $exclude = "";

        foreach($this->options->getExclude() as $excludeItem) {
            $exclude .= sprintf(" --exclude '%s'", escapeshellcmd($excludeItem));
        }

        return $exclude;
    }
}