<?php
/**
 * Var-dumps all arguments passed.
 */
function vd() {
    call_user_func_array('var_dump', func_get_args());
}
/**
 * Same as vd() but ends the script with `exit`.
 * This function also shows the file and line from which it was called.
 */
function vde() {
    call_user_func_array('var_dump', func_get_args());
    $trace = (new Exception())->getTrace();
    echo '<p>'.$trace[0]['file'] . ':' . $trace[0]['line'] . '</p>';
    exit;
}

/**
 * Same as vd() but the dumps are wrapped in <pre> tags.
 */
function vp() {
    echo '<pre>';
    call_user_func_array('var_dump', func_get_args());
    echo '</pre>';
}

/**
 * Same as vde()  but the dumps are wrapped in <pre> tags.
 */
function vpe() {
    call_user_func_array('vp', func_get_args());
    $trace = (new Exception())->getTrace();
    echo '<p><small>'.$trace[0]['file'] . ':' . $trace[0]['line'] . '</small></p>';
    exit;
}
