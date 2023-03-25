<?php
define("LOOK", 90);
define("READ", 91);
define("KEEP", 92);
define("DISCARD", 93);
define("EMIT", 94);
define("NOEMIT", 95);

// types of scanner output
define("X_IDENTIFIER", 1);
define("X_STRING", 2);
define("X_NUMERIC", 3);
define("X_OPERATOR", 4);
define("X_LEFTPAREN", 5);
define("X_RIGHTPAREN", 6);
define("X_EOF", 7);

// types of scanner input
define('C_LETTER', 10);
// define('C_MINUS', 11);     // maybe start of number, maybe operator
define('C_SINGLEQUOTE', 12);
define('C_DOUBLEQUOTE', 13);
define('C_DIGIT', 14);
define('C_WHITESPACE', 15);
define('C_OPERATOR', 17);
define('C_PERIOD', 18);   // because maybe multiple uses
define('C_LEFTPAREN', 19);
define('C_RIGHTPAREN', 20);
define('C_EOF', 21);

define('C_OTHERWISE', 99);   // matches anything, it's the default case

// states in scanner

define('S_START', 30);
define('S_SINGLEQUOTE', 31);
define('S_DOUBLEQUOTE', 32);
define('S_MINUS', 33);
define('S_IDENTIFIER', 34);
define('S_NUMBER', 35);
define('S_OPERATOR', 36);






// given 'ab!=21 scanner returns array [['ab', TOKEN], ['!=', OPERATOR], ['21', NUMBER], ['',EOF] ]
// check out https://homepage.divms.uiowa.edu/~jones/compiler/notes/03.shtml

class SafeEval extends UnitTestCase
{

    function eval(string $expression)
    {

        // first convert $expression into array of tokens
        $scanner = new Scanner();
        $aTokens = $scanner->lexical($expression);
    }
}


class Scanner extends UnitTestCase
{
    public $state = S_START;
    public $pointer = 0;
    public $tokens = [];
    public $token = '';
    public $chars = [];

    public $match = 0;   // when match not zero, scanning pauses until next loop



    // break the expression into array of tokens, eg: ab!=21 becomes three tokens
    function lexical(string $expression): array
    {
        $this->token = '';
        $this->state = S_START;
        $this->tokens = [];
        $this->pointer = 0;

        $this->chars = mb_str_split(trim($expression));   // might as well handle unicode, str_split() is adequate
        if (empty($this->chars))
            return [];


        $safety = 0;
        while (true) {
            if ($safety++ > 100) {
                assertTrue(false, "Endless loop with state={$this->state} and look={$this->look()}");
                return $this->tokens;
            }

            // printNice($this->tokens, 'tokens');
            // printNice("Looping with token = {$this->token} and state={$this->state} and look={$this->look()}");

            $this->match = 0;       // RESET - VERY IMPORTANT - only one match per loop
            switch ($this->state) {

                    // the start state
                case S_START:
                    $this->token = '';        // RESET - VERY IMPORTANT

                    if ($this->look() == C_EOF) {      // if eof in start state, end scanner
                        return ($this->tokens);        // TERMINATES
                    }

                    $this->scan(C_LETTER, READ, KEEP, NOEMIT, S_IDENTIFIER);   // may set match...
                    $this->scan(C_DIGIT, READ, KEEP, NOEMIT, S_NUMBER);
                    $this->scan(C_OPERATOR, READ, KEEP, NOEMIT, S_OPERATOR);

                    $this->scan(C_SINGLEQUOTE, READ, DISCARD, NOEMIT, S_SINGLEQUOTE);
                    $this->scan(C_DOUBLEQUOTE, READ, DISCARD, NOEMIT, S_DOUBLEQUOTE);

                    $this->scan(C_LEFTPAREN, READ, KEEP, X_LEFTPAREN);
                    $this->scan(C_RIGHTPAREN, READ, KEEP, X_RIGHTPAREN);

                    $this->scan(C_OPERATOR, READ, KEEP, NOEMIT, S_OPERATOR);

                    $this->scan(C_OTHERWISE, READ, DISCARD);

                    break;


                    // identifiers are alpha and numbers
                case S_IDENTIFIER:

                    $this->scan(C_LETTER, READ, KEEP);
                    $this->scan(C_DIGIT, READ, KEEP);
                    $this->scan(C_OTHERWISE, LOOK, DISCARD, X_IDENTIFIER, S_START);
                    break;


                case S_NUMBER:
                    // this will capture 123. as a number
                    $this->scan(C_PERIOD, READ, KEEP);
                    $this->scan(C_DIGIT, READ, KEEP);
                    $this->scan(C_OTHERWISE, LOOK, DISCARD, X_NUMERIC, S_START);
                    break;


                case S_OPERATOR:
                    $this->scan(C_OPERATOR, READ, KEEP);
                    $this->scan(C_OTHERWISE, LOOK, DISCARD, X_OPERATOR, S_START);
                    break;

                case S_SINGLEQUOTE:
                    $this->scan(C_SINGLEQUOTE, READ, DISCARD, X_STRING, S_START);
                    $this->scan(C_OTHERWISE, READ, KEEP);
                    break;

                case S_DOUBLEQUOTE:
                    $this->scan(C_DOUBLEQUOTE, READ, DISCARD, X_STRING, S_START);
                    $this->scan(C_OTHERWISE, READ, KEEP);
                    break;


                default:
                    assertTrue(false, "should never get here, {$this->state}");
            }
        }
        return $this->tokens;
    }

    // eg: scan(C_LETTER,LOOK/READ,KEEP/DISCARD,NEXT)
    function scan($class, $lookRead, $keepDiscard, $emit = NOEMIT, $nextState = -1)
    {
        if ($this->match == 0) {   // only process until match - once per loop

            // always a risk of running over the end of the expression
            if ($this->pointer < count($this->chars)) {
                $value = $this->chars[$this->pointer];
            } else {
                $value = '';
            }

            // always match if C_OTHERWISE, else look at actual class
            if ($class == C_OTHERWISE or $class == $this->look()) {
                if ($lookRead == READ)
                    $this->pointer += 1;     // advance
                if ($keepDiscard == KEEP)
                    $this->token .= $value;  // keep
                if ($emit !== NOEMIT)       // maybe need to emit something?
                    $this->tokens[] = [$this->token, $emit];
                if ($nextState >= 0)
                    $this->state = $nextState;
                $this->match = 1;
            }
        }
    }


    function testScanner()
    {
        $a = [
            ['', []],
            ['abc', [['abc', X_IDENTIFIER]]],
            ['123', [['123', X_NUMERIC]]],
            ['+', [['+', X_OPERATOR]]],
            ['a+b', [['a', X_IDENTIFIER], ['+', X_OPERATOR], ['b', X_IDENTIFIER]]],
            ['(a)', [['(', X_LEFTPAREN], ['a', X_IDENTIFIER], [')', X_RIGHTPAREN]]],
            ["'123'", [['123', X_STRING]]],
            ['"123"+"234"', [['123', X_STRING], ['+', X_OPERATOR], ['234', X_STRING]]],     // quotes are not in string

        ];
        foreach ($a as $expr) {
            $aTokens = $this->lexical($expr[0]);
            assertTrue(count($aTokens) == count($expr[1]), 'count is different');
            for ($i = 0; $i < count($aTokens); $i++) {
                assertTrue($aTokens[$i][0] == $expr[1][$i][0], "'{$expr[0]}'  value is different {$aTokens[$i][0]} {$expr[1][$i][0]}");
                assertTrue($aTokens[$i][1] == $expr[1][$i][1], "'{$expr[0]}'  X_TYPE is different {$aTokens[$i][0]} is {$aTokens[$i][1]} should be {$expr[1][$i][1]}");
            }
            // assertTrue(empty(array_diff(, $expr[1])), "error scanning '$expr[0]'");
        }
        return true;
    }

    function look(): int
    {
        if ($this->pointer >= count($this->chars))
            return C_EOF;

        while ($this->typeOfChar($this->chars[$this->pointer]) == C_WHITESPACE) {
            $this->pointer++;     // skip over whitespace ahead
            if ($this->pointer >= count($this->chars))
                return C_EOF;
        }
        return $this->typeOfChar($this->chars[$this->pointer]);
    }

    function testLook()
    {
        $this->chars = mb_str_split('abc');   // might as well handle unicode, str_split() is adequate
        $this->pointer = 0;

        $look = $this->look();
        assertTrue($look == C_LETTER, "char at {$this->pointer} is of class {$look}");
        return true;
    }

    function typeOfChar(string $char): int
    {
        if (ctype_alpha($char)) return C_LETTER;
        if (ctype_digit($char)) return C_DIGIT;
        // if ($char == '-') return C_MINUS;
        if ($char == "'") return C_SINGLEQUOTE;
        if ($char == '"') return C_DOUBLEQUOTE;
        if ($char == '.') return C_PERIOD;
        if ($char == '(') return C_LEFTPAREN;
        if ($char == ')') return C_RIGHTPAREN;
        if (ctype_punct($char)) return C_OPERATOR;  // after tests for specific operators
        if (ctype_space($char)) return C_WHITESPACE;

        assertTrue(false, "did not handle '$char'");
        return C_WHITESPACE;
    }

    // https://en.wikipedia.org/wiki/Shunting_yard_algorithm
    function reversePolish(array $tokens)
    {
        global $operators, $functions, $safeEvalContext;

        // printNice($tokens, 'STARTING - token queue');

        $operatorStack = [];
        $outputQueue = [];

        assertTrue($operators['+']['precedence'] == 2);

        foreach ($tokens as $token) {
            switch ($token[1]) {
                case X_NUMERIC:
                    array_push($outputQueue, $token);
                    break;

                case X_STRING:
                    array_push($outputQueue, $token);
                    break;

                case X_IDENTIFIER:
                    array_push($operatorStack, $token);
                    break;

                case X_OPERATOR:
                    printNice($token, 'token');
                    printNice($operators[$token[0]]['precedence']);
                    $end = end($operatorStack);
                    printNice($end, 'end');
                    // assertTrue(isset($operators[($end[0])]), "unknown operator '{$end[0]}'");
                    assertTrue(isset($operators[$token[0]]), "unknown operator '{$token[0]}'");
                    while (
                        $end !== false and    // check for empty stack
                        $end[1] !== X_LEFTPAREN and
                        // isset($operators[($end[0])]) and  // known operator?
                        // isset($operators[($token[0])]) and  // known operator?
                        ($operators[$end[0]]['precedence'] > $operators[$token[0]]['precedence'] or
                            ($operators[$end[0]]['precedence'] == $operators[$token[0]]['precedence'] and $operators[$end[0]]['assoc'] == 'left')
                        )
                    ) {
                        array_push($outputQueue, array_pop($operatorStack));
                        $end = end($operatorStack);  // refresh $end
                    }
                    array_push($operatorStack, $token);
                    break;

                case X_LEFTPAREN:
                    array_push($operatorStack, $token);
                    break;

                case X_RIGHTPAREN:
                    $end = end($operatorStack);
                    assertTrue($end, "mismatched parentheses");
                    while ($end[1] !== X_LEFTPAREN) {
                        array_push($outputQueue, array_pop($operatorStack));
                        $end = end($operatorStack);  // refresh $end
                    }
                    assertTrue($end[1] == X_LEFTPAREN, "expected leftparen at top of operator stack");
                    array_pop($operatorStack);  // discard

                    $end = end($operatorStack);  // refresh $end
                    if ($end[1] == X_IDENTIFIER) {
                        array_push($outputQueue, array_pop($operatorStack));
                    }
                    break;
            }
        }

        // / * After the while loop, pop the remaining items from the operator stack into the output queue. */
        // while there are tokens on the operator stack:
        while (count($operatorStack) > 0) {
            //     / * If the operator token on the top of the stack is a parenthesis, then there are mismatched parentheses. */
            //     {assert the operator on top of the stack is not a (left) parenthesis}
            $end = end($operatorStack);
            assertTrue($end[1] !== X_LEFTPAREN, "mismatched parenthesis");
            //     pop the operator from the operator stack onto the output queue
            array_push($outputQueue, array_pop($operatorStack));
        }
        // printNice($outputQueue, 'COMPLETED - output queue');
        return $outputQueue;
    }

    // strip the token array into a readable string, so can write tests
    function readable(array $rpnTokens): string
    {
        $ret = '';
        foreach ($rpnTokens as $token) {
            $ret .= $token[0];
        }
        return $ret;
    }

    function testReversePolish()
    {
        $a = [
            ['2+3*5', '235*+'],
            ['a = 2+3*5', '235*+=a'],  // assignment
            ['abs(x)', 'xabs'],     // 2-param function
            ['max(x,3)', '3,xmax'],     // 2-param function
        ];

        foreach ($a as $test) {
            $rpn = $this->readable($this->reversePolish($this->lexical($test[0])));
            // printNice("{$test[0]} generated $rpn ");
            assertTrue($rpn == $test[1], "{$test[0]} generated $rpn instead of{$test[1]}");
        }

        return true;
    }

    function safeEval(array $rpnTokens)
    {
        global $operators, $functions, $safeEvalContext;

        $stack = [];



        // printNice($execution['+'][1](2, 3), 'should be 5');
        // printNice($execution['.'][1]('a', 'bc'), "should be 'abc'");


        printNice($rpnTokens);
        $atAssignment = false;
        foreach ($rpnTokens as $token) {
            printNice($token);

            if ($atAssignment) {  // special case for ASSIGNMENT.  the value on the stack is to be assigned
                assertTrue($token[1] == X_IDENTIFIER, 'trying to assign. Did you mean to use == ?');
                $safeEvalContext[$token[0]] = array_pop($stack);
                assertTrue(count($stack) == 0, 'Expecting the stack to be empty after an assignment');
                array_push($stack, true);    // but we need to return something, so...
                continue;
            }

            switch ($token[1]) {
                case X_OPERATOR:
                    // printNice($stack);
                    if ($token[0] == '=') {        // this is an assignment, like a=1+1
                        $atAssignment = true;   // just discard and set flag for exit
                    }

                    if (isset($operators[$token[0]])) {     // ok, we know this operator

                        if ($operators[$token[0]]['n'] == 1) {   // 2-operand operator
                            $op1 = array_pop($stack);
                            $ret = $operators[$token[0]]['perform']($op1);  // note order
                            printNice(" $op1  {$token[0]}   $ret");
                            array_push($stack, $ret);
                        }

                        if ($operators[$token[0]]['n'] == 2) {   // 2-operand operator
                            $op1 = array_pop($stack);
                            $op2 = array_pop($stack);
                            $ret = $operators[$token[0]]['perform']($op2, $op1);  // note order
                            printNice(" $op1  {$token[0]}  $op2  ->  $ret");
                            array_push($stack, $ret);
                        }
                    } elseif ($token[0] == ",") {
                    } else {
                        assertTrue(false, "unknown function or operator: '{$token[0]}'");
                        continue 2;  // exit the foreach
                    }
                    break;

                case X_NUMERIC:
                    array_push($stack, floatval($token[0]));
                    break;

                case X_STRING:
                    array_push($stack, $token[0]);
                    break;

                case X_IDENTIFIER:
                    if (isset($functions[$token[0]])) {     // ok, we know this operator

                        if ($functions[$token[0]][0] == 0) {   // constants
                            $func = $functions[$token[0]][1];
                            $ret = $func();
                            array_push($stack, $ret);
                        } elseif ($functions[$token[0]][0] == 1) {
                            printNice($stack, 'stack before 1-op function');
                            $op1 = array_pop($stack);

                            $func = $functions[$token[0]][1];
                            $ret = $func($op1);
                            array_push($stack, $ret);
                        } elseif ($functions[$token[0]][0] == 2) {
                            printNice($stack, 'stack before 2-op function');
                            $op1 = array_pop($stack);
                            $op2 = array_pop($stack);

                            $func = $functions[$token[0]][1];
                            $ret = $func($op1, $op2);
                            array_push($stack, $ret);
                        } elseif ($functions[$token[0]][0] == 3) {
                            printNice($stack, 'stack before 3-op function');
                            $op1 = array_pop($stack);
                            $op2 = array_pop($stack);
                            $op3 = array_pop($stack);

                            $func = $functions[$token[0]][1];
                            $ret = $func($op1, $op2, $op3);
                            array_push($stack, $ret);
                        } else {
                            assertTrue("can't handle '{$token[0]}'");
                        }
                        assertTrue("don't yet know what to do with '{$token[0]}'");
                    }
            }
        }


        return array_pop($stack);
    }

    function testSafeEval()
    {
        global $operators, $functions, $safeEvalContext;

        $a = [
            // ['2+3*5', 17],
            // ['15-3', 12],
            // ['"ab"+"cd"', 'abcd'],      // js style concat
            // ['2^3', 8],
            // ['2' + 3, 5],
            // [2 + '3', 5],
            // ["'2'+'3'", '23'],
            // ['5>1', true],
            // ['5<=1', false],
            // ['true', true],
            // ['!true', false],
            // ['!1==1', false],
            // ['max(2,1)', 2],
            // ['max(1,2)', 2],
            // ['PI', 3.14159],
            // ["strtolower('Hello')", 'hello'],
            // ["strtoupper('Hello')", 'HELLO'],
            ['max(1,2)', 2],
            ['min(1,2)', 1],
            ['a=1+1', true],
            // ["a==5?'five':'notFive'"],


            // ['a = 2+3*5', '235*+=a'],  // assignment
            // ['abs(x)', 'xabs'],     // 2-param function
            // ['max(x,3)', '3,xmax'],     // 2-param function
        ];

        foreach ($a as $test) {
            // printNice($this->lexical($test[0]));
            // printNice($this->reversePolish($this->lexical($test[0])));

            $ret = $this->safeEval($this->reversePolish($this->lexical($test[0])));
            // printNice("{$test[0]} generated $ret ");
            assertTrue($ret == $test[1], "{$test[0]} generated $ret instead of{$test[1]}");
        }
        printNice($safeEvalContext,'safeEvalContext');
    }
}
