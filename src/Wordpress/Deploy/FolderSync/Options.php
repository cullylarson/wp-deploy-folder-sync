<?php

namespace Wordpress\Deploy\FolderSync;

class Options {
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options) {
        $this->options = $options;
    }

    public function shouldDelete() {
        return $this->getBoolOption("delete", true);
    }

    public function getExclude() {
        return $this->getArrayOption("exclude");
    }

    private function getBoolOption($option, $defaultVal) {
        if(!isset($this->options[$option])) return $defaultVal;
        else return ($this->options[$option] == true);
    }

    private function getArrayOption($option) {
        if( isset($this->options[$option]) && is_array($this->options[$option])) return $this->options[$option];
        else return [];
    }
}