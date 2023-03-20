<?php

/*
 * The MIT License
 *
 *  Copyright (c) 2020, Mahmoud Ben Hassine (mahmoud.benhassine@icloud.com)
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, meremerge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

interface IWorkUnit
{
    public function __construct(array $workUnitContext);
    public function getName(): string;
    public function run(array $workFlowContext);
}

class abstractWorkUnit
{
    function workReport()
    {
        // return "DefaultWorkReport {" +
        //         "status=" + status +
        //         ", context=" + workContext +
        //         ", error=" + (error == null ? "''" : error) +
        //         '}';
    }
}


class WorkFlowEngine
{
    public $context = [];

    public $workflowJSON = '';

    public $aWorkUnits = [];        // uuid => object
    public $stack = '';

    function load(string $workflowJSON)
    {
    }

    function add(string $workflowJSON)
    {
    }
    // runs an object with its internal data
    function run(array $context)
    {
        $ret = $obj->run();
    }



    function register(string $workUnitName, array $context)
    {
        //TODO cannot be a uniqid, must be
        $uniqid = uniqid();
        $workObject = new $workUnitName($context);
        $this->aWorkUnits[$uniqid] = $workObject;
        return $uniqid;
    }

    function runEngine(array $workFlow, string $workContext, string $startAt = '', int $safety = 20)
    {

        printNice($this->aWorkUnits);
        printNice($workFlow, 'workflow');

        if ($safety < 0) {
            echo 'infinite loop in workflow';
            return;
        }

        // decide where to start
        $w = empty($startAt) ? array_key_first($workFlow) : $startAt;


        foreach ($workFlow[$w] as $workStepKey => $value) {
            switch ($workStepKey) {
                case 'named':
                    echo "Running a unit named '$value'<br>";
                    break;
                case 'repeat':
                    // TODO: make into  stack
                    $this->stack = $value;
                    break;
                case 'times':
                    echo "Times is '$value'<br>";
                    assertTrue(isset($this->aWorkUnits[$this->stack]), "don't have a record for UUID '$value', did you forget 'repeat'?");
                    $obj = $this->aWorkUnits[$this->stack];
                    for ($i = 0; $i < intval($value); $i++) {
                        $obj->run();
                    }
                    break;
                case 'when':
                    echo "When is '$value'<br>";
                    //TODO:  decide to run the 'then' or 'otherwise' clause
                    break;
                case 'then':
                    // just a single flow to execute
                    // $this->run();  // recursive call
                    // echo "Execute the work unit with UUID '$value'<br>";
                    // $subWorkFlow = [$value=>$workFlow[$value]];
                    // printNice($subWorkFlow,"subWorkFlow");
                    // $this->run([$value => $workFlow[$value]], $workContext);

                    $this->run($workFlow, $workContext, $value, $safety - 1);   // recursive call
                    break;
                case 'otherwise':
                    $this->run($workFlow, $workContext, $value, $safety - 1);   // recursive call
                    break;
                case 'execute':
                    if (is_array($value)) {
                        // run each element ('in parallel', but really sequential)
                        foreach ($value as $multi) {
                            $obj = $this->aWorkUnits[$multi];
                            $obj->run();
                        }
                    } else {
                        $obj = $this->aWorkUnits[$value];
                        $obj->run();
                    }


                default:
                    assertTrue("Don't know what to do with instruction " . serialize($workStepKey));
            }
            // printNice($workStepKey, $value);
        }
        // switch ($workStep)
        // printNice((workFlow.getName(),'Running workflow ');
    }
}
