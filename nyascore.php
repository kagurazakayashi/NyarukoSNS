<?php
// USE: require_once "zeze/zezecore.class.php";
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir."zezeconfig.class.php";
require_once $phpfiledir."zezemsg.class.php";
require_once $phpfiledir."zezefunc.class.php";
class zezecore {
    public $cfg;
    public $msg;
    public $func;
    function __construct() {
        $this->cfg = new zezesetting();
        $this->msg = new zezemsg();
        $this->func = new zezefunc();
    }
    function applyconfig() {
    }
    function __destruct() {
        $this->cfg = null; unset($this->cfg);
        $this->msg = null; unset($this->msg);
        $this->func = null; unset($this->func);
    }
}
global $zecore;
if (!isset($zecore)) $zecore = new zezecore();
$zecore->applyconfig();
?>