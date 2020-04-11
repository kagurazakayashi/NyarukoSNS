<?php
// USE: require_once "nyas/nyascore.class.php";
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyasconfig.class.php";
require_once $phpfiledir."nyasmsg.class.php";
require_once $phpfiledir."nyasfunc.class.php";
class nyascore {
    public $cfg;
    public $msg;
    public $func;
    function __construct() {
        $this->cfg = new nyassetting();
        $this->msg = new nyasmsg();
        $this->func = new nyasfunc();
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
if (!isset($zecore)) $zecore = new nyascore();
$zecore->applyconfig();
?>