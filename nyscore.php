<?php
// USE: require_once "nys/nscore.class.php";
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir."nysconfig.class.php";
require_once $phpfiledir."nysmsg.class.php";
require_once $phpfiledir."nysfunc.class.php";
class nyscore {
    public $cfg;
    public $msg;
    public $func;
    function __construct() {
        $this->cfg = new nssetting();
        $this->msg = new nsmsg();
        $this->func = new nsfunc();
    }
    function applyconfig() {
    }
    function __destruct() {
        $this->cfg = null; unset($this->cfg);
        $this->msg = null; unset($this->msg);
        $this->func = null; unset($this->func);
    }
}
global $nscore;
if (!isset($nscore)) $nscore = new nscore();
$nscore->applyconfig();
?>