<?php
// USE: require_once "nys/nyscore.class.php";
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpFileDir."nysconfig.class.php";
require_once $phpFileDir."nysmsg.class.php";
require_once $phpFileDir."nysfunc.class.php";
class nyscore {
    public $cfg;
    public $msg;
    public $func;
    function __construct() {
        $this->cfg = new nyssetting();
        $this->msg = new nysmsg();
        $this->func = new nysfunc();
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
if (!isset($nscore)) $nscore = new nyscore();
$nscore->applyconfig();
?>
