<?php

namespace Wordpress\Deploy\FolderSync;

class Status {
    /**
     * @var String
     */
    public $Message;
    /**
     * @var int
     */
    public $Timestamp;
    /**
     * @var string
     */
    private $messageType;

    const MT_NOTICE = "notice";
    const MT_WARNING = "warning";
    const MT_ERROR = "error";
    const MT_RAW_OUTPUT = "output";

    public function __construct($message, $messageType=self::MT_NOTICE) {
        $this->Message = $message;
        $this->Timestamp = time();
        $this->messageType = $messageType;
    }

    public function isError() {
        return ($this->messageType == self::MT_ERROR);
    }

    public function isWarning() {
        return ($this->messageType == self::MT_WARNING);
    }

    public function isNotice() {
        return (empty($this->messageType) || $this->messageType == self::MT_NOTICE);
    }

    public function isRawOutput() {
        return ($this->messageType == self::MT_RAW_OUTPUT);
    }
}