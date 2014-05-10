<?php namespace GM\RejectNotify;

trait PluginSettable {

    private $plugin;

    function setPlugin( Plugin $plugin ) {
        $this->plugin = $plugin;
        return $this;
    }

}