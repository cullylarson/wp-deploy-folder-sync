# Wordpress Deploy FolderSync

A library for syncing Wordpress folders.  Can be used as part of a deployment
system. Useful for deploying or pulling resources like the 'uploads' folder,
since it likely contains files that the site depends on.

This project is meant to be a composable component. It does one thing, sync
folders. If you want to do more, as part of a deployment system, then check
out the other projects in the `Wordpress\Deploy` namespace.

## Dependencies

* The `rsync` linux command is used to perform the sync.  So, it must be
available via the command-line.  You'll get a `RuntimeException` if you
try to call `FolderSync::sync` without the `rsync` command.

* All other dependencies are defined in `composer.json`.

## Install

```
curl -s http://getcomposer.org/installer | php
php composer.phar require cullylarson/wp-deploy-folder-sync
```

## Usage

Everything is done through an instance of the `Wordpress\Deploy\FolderSync`
class.

### Construction

The `Wordpress\Deploy\FolderSync` constructor takes three arguments:

* __source__ The source folder.  It must have a trailing slash (`/`).
* __dest__ The destination folder.  After the sync is run, the destination folder
will match the source folder.  It must have a trailing slash (`/`).
* __options__ A set of options, described below.

### Source and Destination Paths

These paths provided in the `source` and `dest` params can be any paths that
the `rsync` command recognizes.  So you can provide either remote or local
paths.  For example:

```
username@example.com:path/to/source/
relative/path/to/destination/
/absolute/path/to/destination/
```

### Options

The constructor accepts the following options:

* __delete__ _boolean (Default: true)_ Whether to delete files when syncing
(e.g. if a file no longer exists at the source, then delete it from the
destination).

* __exclude__ _array (Default: [])_ An array of patterns to exclude from the
sync.  See the rsync man page for details on --exclude.

### Syncing

The sync is performed by calling the `Wordpress\Deploy\FolderSync::sync` function.
This will examine your `source` folder and `dest` folder, and will make any changes
necessary so that your `dest` folder ends up just like your `source` folder.  In
this way, the folders will be synchronized.

### Example

```
<?php

class MyWordpressDeployer {
    public function deploy() {
        // ...
        
        $folderSync = new Wordpress\Deploy\FolderSync(
            "path/to/local/uploads/",
            "username@example.com:path/to/remote/uploads/",
            ['delete' => true, 'exclude' => ['.gitkeep']]);
            
        $folderSync->sync();
        
        // ...
    }
}
```

### Status Callback

The `Wordpress\Deploy\FolderSync::sync` function can optionally accept a callback
function.  This callback will be called whenever the sync function wants to post
a status update (e.g. "I'm running", "Here's the output of the rsync command",
"Something went wrong", etc.).  It allows you to have some control over whether
and how messages are handled.

The callback must take one parameter, an instance of `Wordpress\Deploy\FolderSync\Status`.

Here's an example:

```
<?php

class MyWordpressDeployer {
    public function deploy() {
        // ...
        
        $statusCallback = function(Wordpress\Deploy\FolderSync\Status $status) {
            echo $status->Timestamp;
            
            if( $status->isError() ) echo "ERROR: ";
            if( $status->isWarning() ) echo "WARNING: ";
            if( $status->isRawOutput() ) echo "================\n";
            
            echo $status->Message;
            
            if( $status->isRawOutput() ) echo "================\n";
        }
        
        $folderSync = new Wordpress\Deploy\FolderSync(
            "path/to/local/uploads/",
            "username@example.com:path/to/remote/uploads/");
            
        $folderSync->sync($statusCallback);
        
        // ...
    }
}
```