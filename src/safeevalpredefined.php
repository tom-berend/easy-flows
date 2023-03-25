<?php

// predefined operators and functions for SafeEval

// limitation is FIXED number of parameters.  need to add another stack
// if you want variable #of parameters

global $operators, $functions, $safeEvalContext;

$operators = [];
$functions = [];
$safeEvalContext = [];


// assignment is tricky, we don't handle it here
$operators['='] = ['n' => 0, 'precedence' => 1, 'assoc' => 'right', 'perform' =>  function ($op1) {
    return;
}];

// commas are whitespace, but don't want special handling.  so just zero-ops
$operators[','] = ['n' => 0, 'precedence' => 2, 'assoc' => 'right', 'perform' => function ($op1) {
    return;
}];





// 1 operand
$operators['!'] = ['n' => 1, 'precedence' => 2, 'assoc' => 'right', 'perform' => function ($op1) {
    // printNice($op1);
    return !$op1;
}];

// 2 operand

$operators['+'] = ['n' => 2, 'precedence' => 2, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    if (is_float(($op1)))       // may be a string concat
        return $op1 + floatval($op2);
    else
        return $op1 . $op2;
}];
$operators['-'] = ['n' => 2, 'precedence' => 2, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 - $op2;
}];
$operators['*'] = ['n' => 2, 'precedence' => 3, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 * $op2;
}];
$operators['/'] = ['n' => 2, 'precedence' => 3, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 * $op2;
}];
$operators['^'] = ['n' => 2, 'precedence' => 4, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return pow($op1, $op2);
}];
$operators['=='] = ['n' => 2, 'precedence' => 6, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 == $op2;
}];
$operators['!='] = ['n' => 2, 'precedence' => 6, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 != $op2;
}];
$operators['<'] = ['n' => 2, 'precedence' => 6, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 < $op2;
}];;
$operators['>'] = ['n' => 2, 'precedence' => 6, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 > $op2;
}];
$operators['<='] = ['n' => 2, 'precedence' => 6, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 <= $op2;
}];
$operators['>='] = ['n' => 2, 'precedence' => 6, 'assoc' => 'right', 'perform' =>  function ($op1, $op2) {
    return $op1 >= $op2;
}];

// // 3 operand

// $operators['?'] = [3, function ($op1, $op2, $op3) {
//     return $op1 >= $op2;
// }];





///////////////////////////////////
// functions  (simpler, only need to know the number of params)

// constants
$functions['true'] = [0, function () {
    return true;
}];
$functions['false'] = [0, function () {
    return true;
}];
$functions['PI'] = [0, function () {
    return 3.14159;
}];
$functions['E'] = [0, function () {
    return 2.71828;
}];


$fb = [
    // built-in functions, but limitation is FIXED # of parameters
    ['sin', 1],          // has 1 parameter
    ['sinh', 1],
    ['arcsin', 1],
    ['asin', 1],
    ['arcsinh', 1],
    ['asinh', 1],
    ['cos', 1],
    ['cosh', 1],
    ['arccos', 1],
    ['acos', 1],
    ['arccosh', 1],
    ['acosh', 1],
    ['tan', 1],
    ['tanh', 1],
    ['arctan', 1],
    ['atan', 1],
    ['arctanh', 1],
    ['atanh', 1],
    ['sqrt', 1],
    ['abs', 1],
    ['ln', 1],
    ['log', 1],
    ['date', 1],
    ['log10', 1],
    ['rand', 1],
    ['round', 1],
    ['ord', 1],
    ['chr', 1],
    ['ceil', 2],
    ['floor', 2],
    ['max', 2],
    ['min', 2],

    ['strtolower', 1],
    ['strtoupper', 1],

];







// one parameter

// $functions['strtolower'] = [1, function ($op1) {
//     return (strtolower($op1));
// }];
// $functions['strtoupper'] = [1, function ($op1) {
//     return (strtoupper($op1));
// }];

foreach ($fb as $f) {
    $fString = $f[0];
    $ret = [];
    switch ($f[1]) {    // number of parameters
        case 1:
            $ret = [1, function ($op1) use ($fString) {
                return ($fString)($op1);
            }];
            break;
        case 2:
            $ret = [2, function ($op1, $op2) use ($fString) {
                return ($fString)($op1, $op2);
            }];
            break;
        case 3:
            $ret = [3, function ($op1, $op2, $op3) use ($fString) {
                return ($fString)($op1, $op2, $op3);
            }];
            break;
    }
    $functions[$fString] = $ret;
}
