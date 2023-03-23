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
define('C_MINUS', 11);     // maybe start of number, maybe operator
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

        // now identify the predefined functions
        $screener = new Screener();
        $aTokens = $screener->screen($aTokens);
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

        ];
        foreach ($a as $expr) {
            $aTokens = $this->lexical($expr[0]);
            // printNice($aTokens);
            // printNice($expr[1]);
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
        if ($char == '-') return C_MINUS;
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
        printNice($tokens, 'STARTING - token queue');

        $operatorStack = [];
        $outputQueue = [];

        $operators = [];
        $operators['^'] = ['precedence' => 4, 'associativity' => 'right'];
        $operators['x'] = ['precedence' => 3, 'associativity' => 'left'];
        $operators['/'] = ['precedence' => 3, 'associativity' => 'left'];
        $operators['+'] = ['precedence' => 2, 'associativity' => 'left'];
        $operators['-'] = ['precedence' => 2, 'associativity' => 'left'];

        assertTrue($operators['+']['precedence'] == 2);

        // while there are tokens to be read:
        //     read a token
        foreach ($tokens as $token) {
            switch ($token[1]) {
                    //     if the token is:
                    //     - a number:
                    //         put it into the output queue
                case X_NUMERIC:
                    array_push($outputQueue, $token);
                    break;

                    //     - a function:
                    //         push it onto the operator stack
                case X_IDENTIFIER:
                    array_push($operatorStack, $token);
                    break;


                    //     - an operator o1:
                    //         while (
                    //             there is an operator o2 at the top of the operator stack which is not a left parenthesis,
                    //             and (o2 has greater precedence than o1 or (o1 and o2 have the same precedence and o1 is left-associative))
                    //         ):
                    //             pop o2 from the operator stack into the output queue
                    //         push o1 onto the operator stack
                case X_OPERATOR:
                    $end = end($operatorStack);
                    printNice($end);
                    while (
                        $end !== false and    // check for empty stack
                        $end[1] !== X_LEFTPAREN and
                        isset($operators[($end[0])]) and  // known operator?
                        isset($operators[($token[0])]) and  // known operator?
                        ($operators[($end[0])['precedence']] > $operators[($token[0])['precedence']] or
                            ($operators[($end[0])['precedence']] == $operators[($token[0])['precedence']] and $operators[($end[0])['associative']] == 'left')
                        )
                    ) {
                        array_push($outputQueue, array_pop($operatorStack));
                        $end = end($operatorStack);  // refresh $end
                    }
                    array_push($operatorStack, $token);
                    break;

                    //     - a left parenthesis (i.e. "("):
                    //         push it onto the operator stack
                case X_LEFTPAREN:
                    array_push($operatorStack, $token);
                    break;

                    //     - a right parenthesis (i.e. ")"):
                    //         while the operator at the top of the operator stack is not a left parenthesis:
                    //             {assert the operator stack is not empty}
                    //             /* If the stack runs out without finding a left parenthesis, then there are mismatched parentheses. */
                    //             pop the operator from the operator stack into the output queue
                    //         {assert there is a left parenthesis at the top of the operator stack}
                    //         pop the left parenthesis from the operator stack and discard it
                    //         if there is a function token at the top of the operator stack, then:
                    //             pop the function from the operator stack into the output queue
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

        // /* After the while loop, pop the remaining items from the operator stack into the output queue. */
        // while there are tokens on the operator stack:
        while (count($operatorStack) > 0) {
            //     /* If the operator token on the top of the stack is a parenthesis, then there are mismatched parentheses. */
            //     {assert the operator on top of the stack is not a (left) parenthesis}
            $end = end($operatorStack);
            assertTrue($end[1] !== X_LEFTPAREN, "mismatched parenthesis");
            //     pop the operator from the operator stack onto the output queue
            array_push($outputQueue, array_pop($operatorStack));
        }
        printNice($outputQueue, 'COMPLETED - output queue');
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
            printNice("{$test[0]} generated $rpn ");
            assertTrue($rpn == $test[1], "{$test[0]} generated $rpn instead of{$test[1]}");
        }



        return true;
    }
}





/*
  ================================================================================

  EvalMath - PHP Class to safely evaluate math expressions
  Copyright (C) 2005 Miles Kaufmann <http://www.twmagic.com/>

  Logic and Dates added by Lee Eden https://www.phpclasses.org/discuss/package/2695/thread/4/

  // added rand(), round(), ord(), chr()  as valid math functions.   Tom.
  // added ceil(), floor(), log10(), max(), min()
  ================================================================================

  NAME
  EvalMath - safely evaluate math expressions

  SYNOPSIS
  <?php
  include 'evalmath.class.php';
  $m = new EvalMath;
  // basic evaluation:
  $result = $m->evaluate('2+2');
  // supports: order of operation; parentheses; negation; built-in functions
  $result = $m->evaluate('-8(5/2)^2*(1-sqrt(4))-8');
  // create your own variables
  $m->evaluate('a = e^(ln(pi))');
  // or functions
  $m->evaluate('f(x,y) = x^2 + y^2 - 2x*y + 1');
  // and then use them
  $result = $m->evaluate('3*f(42,a)');
  ?>

  DESCRIPTION
  Use the EvalMath class when you want to evaluate mathematical expressions
  from untrusted sources.	 You can define your own variables and functions,
  which are stored in the object.	 Try it, it's fun

  METHODS
  $m->evalute($expr)
  Evaluates the expression and returns the result. If an error occurs,
  prints a warning and returns false. If $expr is a function assignment,
  returns true on success.

  $m->e($expr)
  A synonym for $m->evaluate().

  $m->vars()
  Returns an associative array of all user-defined variables and values.

  $m->funcs()
  Returns an array of all user-defined functions.

  PARAMETERS
  $m->suppress_errors
  Set to true to turn off warnings when evaluating expressions

  $m->last_error
  If the last evaluation failed, contains a string describing the error.
  (Useful when suppress_errors is on).

  BUILT-IN FUNCTIONS
  the following mathematical functions can be called within the expression:
  sin(n), sinh(n), arcsin(n), asin(n), arcsinh(n), asinh(n),
  cos(n), cosh(n), arccos(n), acos(n), arccosh(n), acosh(n),
  tan(n), tanh(n), arctan(n), atan(n), arctanh(n), atanh(n),
  sqrt(n), abs(n), ln(n), log(n)
  the following logical functions have also been defined
  if(a,b,c) - a is a logical expression, b returned if true, c if false
  or(a,b)
  and(a,b)
  not(a)
  the date(y,m,d,h,m) returns a timestamp in unix format (seconds since 1970)
  by utilising php's strtotime() function on "yyyy-mm-dd hh:mm:00 UTC"

  AUTHOR INFORMATION
  Copyright 2005, Miles Kaufmann.

  LICENSE
  Redistribution and use in source and binary forms, with or without
  modification, are permitted provided that the following conditions are
  met:

  1 Redistributions of source code must retain the above copyright
  notice, this list of conditions and the following disclaimer.
  2. Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in the
  documentation and/or other materials provided with the distribution.
  3. The name of the author may not be used to endorse or promote
  products derived from this software without specific prior written
  permission.

  THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
  IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
  INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
  HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
  STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
  ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
  POSSIBILITY OF SUCH DAMAGE.

 */

// namespace Cognito\EvalMath;

class SafeEvalMath extends UnitTestCase
{

    public $suppress_errors = true;
    public $last_error = null;
    public $v = array(
        'e' => 2.71,
        'pi' => 3.14,
    ); // variables (and constants)
    public $f = array(); // user-defined functions
    public $vb = array(
        'e',
        'pi',
    ); // constants
    public $fb = array(
        // built-in functions
        'sin',
        'sinh',
        'arcsin',
        'asin',
        'arcsinh',
        'asinh',
        'cos',
        'cosh',
        'arccos',
        'acos',
        'arccosh',
        'acosh',
        'tan',
        'tanh',
        'arctan',
        'atan',
        'arctanh',
        'atanh',
        'sqrt',
        'abs',
        'ln',
        'log',
        'date',
        'log10',
        'rand',
        'round',
        'ord',
        'chr',
        'ceil',
        'floor',
        'max',
        'min'
    );

    public function evalMath()
    {
        // make the variables a little more accurate
        $this->v['pi'] = pi();
        $this->v['e'] = exp(1);

        // create logical functions (by defining as if user-defined)
        $this->evaluate('if(x,y,z) = (x*y)+((1-x)*z)');
        $this->evaluate('and(x,y) = x&y');
        $this->evaluate('or(x,y) = x|y');
        $this->evaluate('not(x) = 1!x');
    }

    public function testEvalMath()
    {

        printNice('in testEvalMath()');
        $a = [];


        $a[] = ['2+2', 4];

        // create your own values
        $a[] = ['a = 2 + 3', true];
        $a[] = ['a', 5];
        $a[] = ['a + 1', 6];

        $a[] = ['b = 5', true];
        $a[] = ['b', 5];
        $a[] = ['a + b', 10];




        // create your own functions
        $a[] = ['sum(x,y) = x + y', true];
        $a[] = ['sum(2,3)', 5];

        // TODO: doesn't support zero params
        // create a closure (eg: to save a random value)
        $a[] = ['five = min(5,10)', true];
        $a[] = ['five', 5];

        // $a[] = ['sum(rand(0,5),rand(0,5))',5];    // jcan't test random values

        $a[] = ['ceil(4 + .1)', 5];
        $a[] = ['round(4,2)', 4];
        $a[] = ['round(4.123,2)', 4.12];
        $a[] = ['chr(51)', '3'];

        $a[] = ['min(200,500)', 200];
        $a[] = ['min(500,200)', 200];
        $a[] = ['max(200,500)', 500];
        $a[] = ['max(500,200)', 500];

        $a[] = ['val1 = hello', true];
        $a[] = ['val1', 'hello'];
        $a[] = ['val1', '4'];

        $a[] = ['v = ?', true];
        $a[] = ['val2', 'worlds'];


        // a function that doesn't exist
        // $a[] = ['foo(0)',false];


        foreach ($a as $test) {
            $result = $this->evaluate($test[0]);
            // printNice($test[0],"result is '$result'");
            assertTrue($result == $test[1], "EVALUATE failed: {$test[0]} returns {$result} instead of {$test[1]}");
        }

        printNice($this->v, 'v');
        printNice($this->f, 'f');
        printNice($this->vb, 'vb');
    }


    public function e($expr)
    {
        return $this->evaluate($expr);
    }

    public function evaluate($expr)
    {
        $this->last_error = null;
        $expr = trim($expr);
        $expr = str_replace(">=", "`", $expr); // because the operators cannot by made up of 2 characters, use a temporary code
        $expr = str_replace("<=", "~", $expr); // ">=" replaced by "`", "<=" replaced by "~"

        if (substr($expr, -1, 1) == ';') {
            $expr = substr($expr, 0, strlen($expr) - 1); // strip semicolons at the end
        }

        //===============
        // is it a variable assignment?  look for   var = something
        if (preg_match('/^\s*([a-z]\w*)\s*=\s*(.+)$/', $expr, $matches)) {
            if (in_array($matches[1], $this->vb)) { // make sure we're not assigning to a constant
                return $this->trigger("cannot assign to constant '$matches[1]'");
            }
            if (($tmp = $this->pfx($this->nfx($matches[2]))) === false) {
                printNice($matches[2], "test assignment");
                return false; // get the result and make sure it's good
            }
            $this->v[$matches[1]] = $tmp; // if so, stick it in the variable array
            printNice("assignment {$matches[1]} = $tmp");
            return $this->v[$matches[1]]; // and return the resulting value
            //===============
            // is it a function assignment?
        } else if (preg_match('/^\s*([a-z]\w*)\s*\(\s*([a-z]\w*(?:\s*,\s*[a-z]\w*)*)\s*\)\s*=\s*(.+)$/', $expr, $matches)) {
            $fnn = $matches[1]; // get the function name
            if (in_array($matches[1], $this->fb)) { // make sure it isn't built in
                return $this->trigger("cannot redefine built-in function '$matches[1]()'");
            }
            $args = explode(",", preg_replace("/\s+/", "", $matches[2])); // get the arguments
            if (($stack = $this->nfx($matches[3])) === false) {
                return false; // see if it can be converted to postfix
            }
            for ($i = 0; $i < count($stack); $i++) { // freeze the state of the non-argument variables
                $token = $stack[$i];
                if (preg_match('/^[a-z]\w*$/', $token) and !in_array($token, $args)) {
                    if (array_key_exists($token, $this->v)) {
                        $stack[$i] = $this->v[$token];
                    } else {
                        return $this->trigger("undefined variable '$token' in function definition");
                    }
                }
            }
            $this->f[$fnn] = array(
                'args' => $args,
                'func' => $stack
            );
            return true;
            //===============
        } else {
            return $this->pfx($this->nfx($expr)); // straight up evaluation, woo
        }
    }


    public function vars()
    {
        $output = $this->v;
        unset($output['pi']);
        unset($output['e']);
        return $output;
    }

    public function funcs()
    {
        $output = array();
        foreach ($this->f as $fnn => $dat) {
            $output[] = $fnn . '(' . implode(',', $dat['args']) . ')';
        }
        return $output;
    }

    //===================== HERE BE INTERNAL METHODS ====================\\
    // Convert infix to postfix notation
    public function nfx($expr)
    {
        $index = 0;
        $stack = new EvalMathStack;
        $output = array(); // postfix form of expression, to be passed to pfx()
        $expr = trim(strtolower($expr));

        $ops = array(
            '+',
            '-',
            '*',
            '/',
            '^',
            '_',
            '=',
            '<',
            '>',
            '!',
            '&',
            '|',
            "`",
            "~",
        ); // "`" is ">=" and "~" is "<="
        $ops_r = array(
            '+' => 0,
            '-' => 0,
            '*' => 0,
            '/' => 0,
            '^' => 1,      // a/b becomes push a, div b, but a^2 becomes push 2,
            '=' => 0,
            '<' => 0,
            '>' => 0,
            '!' => 0,
            '&' => 0,
            '|' => 0,
            '`' => 0,
            '~' => 0,
        ); // right-associative operator?
        $ops_p = array(
            '+' => 1,
            '-' => 1,
            '*' => 2,
            '/' => 2,
            '_' => 2,
            '^' => 3,
            '=' => 0,
            '<' => 0,
            '>' => 0,
            '!' => 0,
            '&' => 0,
            '|' => 0,
            '`' => 0,
            '~' => 0,
        ); // operator precedence

        $expecting_op = false; // we use this in syntax-checking the expression
        // and determining when a - is a negation

        if (preg_match("/[^\w\s><=!&|+`~*^\/()\.,-]/", $expr, $matches)) { // make sure the characters are all good
            return $this->trigger("illegal character '{$matches[0]}'");
        }
        while (1) { // 1 Infinite Loop ;)
            $op = substr($expr, $index, 1); // get the first character at the current index
            // find out if we're currently at the beginning of a number/variable/function/parenthesis/operand
            $ex = preg_match('/^([a-z]\w*\(?|\d+(?:\.\d*)?|\.\d+|\()/', substr($expr, $index), $match);
            //===============
            if ($op == '-' and !$expecting_op) { // is it a negation instead of a minus?
                $stack->push('_'); // put a negation on the stack
                $index++;
            } else if ($op == '_') { // we have to explicitly deny this, because it's legal on the stack
                return $this->trigger("illegal character '_'"); // but not in the input expression
                //===============
            } else if ((in_array($op, $ops) or $ex) and $expecting_op) { // are we putting an operator on the stack?
                if ($ex) { // are we expecting an operator but have a number/variable/function/opening parethesis?
                    $op = '*';
                    $index--; // it's an implicit multiplication
                }
                // heart of the algorithm:
                while ($stack->count > 0 and ($o2 = $stack->last()) and in_array($o2, $ops) and ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])) {
                    $output[] = $stack->pop(); // pop stuff off the stack into the output
                }
                // many thanks: http://en.wikipedia.org/wiki/Reverse_Polish_notation#The_algorithm_in_detail
                $stack->push($op); // finally put OUR operator onto the stack
                $index++;
                $expecting_op = false;
                //===============
            } else if ($op == ')' and $expecting_op) { // ready to close a parenthesis?
                while (($o2 = $stack->pop()) != '(') { // pop off the stack back to the last (
                    if (is_null($o2)) {
                        return $this->trigger("unexpected ')'");
                    } else {
                        $output[] = $o2;
                    }
                }
                if (preg_match("/^([a-z]\w*)\($/", $stack->last(2), $matches)) { // did we just close a function?
                    $fnn = $matches[1]; // get the function name
                    $arg_count = $stack->pop(); // see how many arguments there were (cleverly stored on the stack, thank you)
                    $output[] = $stack->pop(); // pop the function and push onto the output
                    if (in_array($fnn, $this->fb)) { // check the argument count
                        if ($fnn == 'date') {
                            if ($arg_count != 5) {
                                return $this->trigger("wrong number of arguments for $fnn ($arg_count given, 5 expected)");
                            }
                        } elseif ($fnn == 'rand' or $fnn == 'round' or $fnn == 'max' or $fnn == 'min') {

                            if ($arg_count != 2) {
                                return $this->trigger("wrong number of arguments for $fnn ($arg_count given, 2 expected)");
                            }
                        } else {
                            if ($arg_count > 1) {
                                return $this->trigger("too many arguments for $fnn ($arg_count given, 1 expected)");
                            }
                        }
                    } else if (array_key_exists($fnn, $this->f)) {
                        if ($arg_count != count($this->f[$fnn]['args'])) {
                            return $this->trigger("wrong number of arguments ($arg_count given, " . count($this->f[$fnn]['args']) . " expected)");
                        }
                    } else { // did we somehow push a non-function on the stack? this should never happen
                        return $this->trigger("internal error - trying to process '$fnn'");
                    }
                }
                $index++;
                //===============
            } else if ($op == ',' and $expecting_op) { // did we just finish a function argument?
                while (($o2 = $stack->pop()) != '(') {
                    if (is_null($o2)) {
                        return $this->trigger("unexpected ','"); // oops, never had a (
                    } else {
                        $output[] = $o2; // pop the argument expression stuff and push onto the output
                    }
                }
                // make sure there was a function
                if (!preg_match("/^([a-z]\w*)\($/", $stack->last(2), $matches)) {
                    // printNice($matches,);
                    return $this->trigger("could not understand expr '$expr'- op: '$op' stack: 0:{$stack->last()} 1:{$stack->last(1)} 2:{$stack->last(2)} ");
                }
                // printNice($matches,'preg_match succeeds!  subject:' .$stack->last(2));
                $stack->push($stack->pop() + 1); // increment the argument count
                $stack->push('('); // put the ( back on, we'll need to pop back to it again
                $index++;
                $expecting_op = false;
                //===============
            } else if ($op == '(' and !$expecting_op) {
                $stack->push('('); // that was easy
                $index++;
                $allow_neg = true;
                //===============
            } else if ($ex and !$expecting_op) { // do we now have a function/variable/number?
                $expecting_op = true;
                $val = $match[1];
                if (preg_match("/^([a-z]\w*)\($/", $val, $matches)) { // may be func, or variable w/ implicit multiplication against parentheses...
                    if (in_array($matches[1], $this->fb) or array_key_exists($matches[1], $this->f)) { // it's a func
                        $stack->push($val);
                        $stack->push(1);
                        $stack->push('(');
                        $expecting_op = false;
                    } else { // it's a var w/ implicit multiplication
                        $val = $matches[1];
                        $output[] = $val;
                    }
                } else { // it's a plain old var or num
                    $output[] = $val;
                }
                $index += strlen($val);
                //===============
            } else if ($op == ')') { // miscellaneous error checking
                return $this->trigger("unexpected ')'");
            } else if (in_array($op, $ops) and !$expecting_op) {
                return $this->trigger("unexpected operator '$op'");
            } else { // I don't even want to know what you did to get here
                return $this->trigger("an unexpected error occured");
            }
            if ($index == strlen($expr)) {
                if (in_array($op, $ops)) { // did we end with an operator? bad.
                    return $this->trigger("operator '$op' lacks operand");
                } else {
                    break;
                }
            }
            while (substr($expr, $index, 1) == ' ') { // step the index past whitespace (pretty much turns whitespace
                $index++; // into implicit multiplication if no operator is there)
            }
        }
        while (!is_null($op = $stack->pop())) { // pop everything off the stack and push onto output
            if ($op == '(') {
                return $this->trigger("expecting ')'"); // if there are (s on the stack, ()s were unbalanced
            }
            $output[] = $op;
        }
        return $output;
    }

    // evaluate postfix notation
    public function pfx($tokens, $vars = array())
    {
        // printNice($tokens,'pfx tokens');

        if ($tokens == false) {
            return false;
        }

        $stack = new EvalMathStack;

        foreach ($tokens as $token) { // nice and easy
            // if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
            if (in_array($token, array(
                '+',
                '-',
                '*',
                '/',
                '^',
                '=',
                '<',
                '>',
                '!',
                '&',
                '|',
                "`",
                "~"
            ))) {
                if (is_null($op2 = $stack->pop())) {
                    return $this->trigger("internal error");
                }
                if (is_null($op1 = $stack->pop())) {
                    return $this->trigger("internal error");
                }

                // $op1 = (float) $op1;
                // $op2 = (float) $op2;

                switch ($token) {
                    case '+':
                        $stack->push($op1 + $op2);
                        break;
                    case '-':
                        $stack->push($op1 - $op2);
                        break;
                    case '*':
                        $stack->push($op1 * $op2);
                        break;
                    case '/':
                        if ($op2 == 0) {
                            return $this->trigger("division by zero");
                        }
                        $stack->push($op1 / $op2);
                        break;
                    case '^':
                        $stack->push(pow($op1, $op2));
                        break;
                    case '=':
                        if (abs($op1 - $op2) < 0.000001) { // =
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '>':
                        if ($op1 > $op2) {
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '<':
                        if ($op1 < $op2) {
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '!':
                        if (abs($op1 - $op2) > 0.000001) { // !=
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '&':
                        if (($op1 == 1) && ($op2 == 1)) {
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '|':
                        if (($op1 == 1) || ($op2 == 1)) {
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '`':
                        if (($op1 - $op2) > -0.000001) { // >=
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                    case '~':
                        if (($op1 - $op2) < 0.000001) { // <=
                            $stack->push(1);
                            break;
                        } else {
                            $stack->push(0);
                            break;
                        }
                }
                // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
            } else if ($token == "_") {
                $stack->push(-1 * $stack->pop());
                // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
            } else if (preg_match("/^([a-z]\w*)\($/", $token, $matches)) { // it's a function!
                $fnn = $matches[1];
                if (in_array($fnn, $this->fb)) { // built-in function:
                    if ($fnn == "date") {
                        $mins = $stack->pop();
                        $hrs = $stack->pop();
                        $dys = $stack->pop();
                        $mnths = $stack->pop();
                        $yrs = $stack->pop();
                        $dtstr = sprintf("%04d", $yrs) . "-" . sprintf("%02d", $mnths) . "-" . sprintf("%02d", $dys) . " " . sprintf("%02d", $hrs) . ":" . sprintf("%02d", $mins) . ":00 UTC";
                        eval('$stack->push(strtotime("' . $dtstr . '"));'); // perfectly safe eval()
                    } elseif ($fnn == 'rand' or $fnn == 'round' or $fnn == 'max' or $fnn == 'min') {
                        $a = $stack->pop();
                        $b = $stack->pop();
                        $code = '$stack->push' . "($fnn($b,$a));";    // ha ha, watch that params are in reverse order
                        eval($code); // can't see any way to sneak trouble in here
                    } else {
                        if (is_null($op1 = $stack->pop())) {
                            return $this->trigger("internal error - nothing to pop");
                        }
                        $fnn = preg_replace("/^arc/", "a", $fnn); // for the 'arc' trig synonyms
                        if ($fnn == 'ln') {
                            $fnn = 'log';
                        }
                        eval('$stack->push(' . $fnn . '($op1));'); // perfectly safe eval()
                    }
                } else if (array_key_exists($fnn, $this->f)) { // user function
                    // get args
                    $args = array();
                    for ($i = count($this->f[$fnn]['args']) - 1; $i >= 0; $i--) {
                        if (is_null($args[$this->f[$fnn]['args'][$i]] = $stack->pop())) {
                            return $this->trigger("internal error");
                        }
                    }
                    $stack->push($this->pfx($this->f[$fnn]['func'], $args)); // yay... recursion!!!!
                }
                // if the token is a number or variable, push it on the stack
            } else {
                if (is_numeric($token)) {
                    $stack->push($token);
                } else if (array_key_exists($token, $this->v)) {
                    $stack->push($this->v[$token]);
                } else if (array_key_exists($token, $vars)) {
                    $stack->push($vars[$token]);
                } else {
                    return $this->trigger("undefined variable '$token'");
                }
            }
        }
        // when we're out of tokens, the stack should have a single element, the final result
        if ($stack->count != 1) {
            return $this->trigger("internal error");
        }
        return $stack->pop();
    }

    // trigger an error, but nicely, if need be
    public function trigger($msg)
    {
        $this->last_error = $msg;
        // printNice($msg,"Error in evaluate.php");
        if (!$this->suppress_errors) {
            throw new \Exception($msg);
        }
        return false;
    }
}

// for internal use
class EvalMathStack
{

    public $stack = array();
    public $count = 0;

    public function push($val)
    {
        $this->stack[$this->count] = $val;
        $this->count++;
    }

    public function pop()
    {
        if ($this->count > 0) {
            $this->count--;
            return $this->stack[$this->count];
        }
        return null;
    }

    public function last($n = 1)
    {
        if ($this->count < $n) {
            return $this->stack[0];
        } else {
            return $this->stack[$this->count - $n];
        }
    }
}
