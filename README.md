### NOT READY - DO NOT USE

## Easy-Flow for PHP

This is based on the wonderfully intuitive **Easy-Flow** package at [j-easy/easy-flows](https://github.com/j-easy/easy-flows) but with the following differences:

- PHP (of course)
- workflows stored in json files (so users can edit them)
- support for workflow overloading (so specific flows can override general flows)
- just script orchestration (because PHP)



***

<div align="center">
    <b><em>Easy Flows</em></b><br>
    The simple, stupid workflow engine for PHP
</div>

<div align="center">

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)


</div>

***

## What is Easy Flows?

Easy Flows is a workflow engine for PHP. It provides simple APIs and building blocks to make it easy to create and run composable, overloadable workflows.


<p align="center">
    <img src="https://raw.githubusercontent.com/wiki/j-easy/easy-flows/images/easy-flows.png" width="70%">
</p>

Those are the only basic flows you need to know to start creating workflows with Easy Flows.
You don't need to learn a complex notation or concepts, just a few natural APIs that are easy to think about.

## How does it work?

A unit of work in Easy Flows is represented by the `WorkUnit` interface.  Let's write some work that prints a message to the webpage.

```php
<?php

class PrintMessage extends WorkUnitEngine implements WorkUnit
{
    public array $workUnitContext = [];

    function __construct(array $workUnitContext) {
        if (!isset($context['message']))
            throw new Exception("PrintMessage workunit requires a message'");

        $this->registerWorkUnit($this);
        $this->workUnitContext = $workUnitContext;  // just saving it
    }

    function getName(): string {
        return get_class($this);   // the name of this class
    }

    function run(array $workFlowContext) {
        echo $this->context['message'] ?? '', '<br>';
    }
}
```

So maybe it's a workflow to check whether a client's new order has exceeded their credit limit. The first step gets the client's profile and credit limit, and loads it into the context array.  The second step adds open (unpaid) invoices.  The third step adds all open (unbilled) orders in progress.  The fourth step does the calculations, and the fifth step either puts a hold on the order or releases it.

But we have a slightly different workflow for our ten best customers, we release the workorder but notify the credit manager.

And we have a related company. We ALWAYS release their workorders.

There are three types of components here.

- 'Context' is a PHP associative array.  It is typically created by the first 'Step' and updated by subsequent steps.
- 'Steps' are PHP code, they know how to access databases, send emails, and do complex calculations on the Context.
- 'Workflow' are JSON files


'Context'is just a PHP associative array.  The workunit is initialized the ($workFlowContext) array, and each step ('workunit') of the workflow may update the workflow context for some subsequent workunit.






Let's suppose we want to create the following workflow:

1. print "foo" three times
2. then print "hello" and "world" in parallel
3. then if both "hello" and "world" have been successfully printed to the console, print "ok", otherwise print "nok"

This workflow can be illustrated as follows:

<p align="center">
    <img src="https://raw.githubusercontent.com/wiki/j-easy/easy-flows/images/easy-flows-example.png" width="70%">
</p>

t text](http://url/to/img.png)

* `flow1` is a `RepeatFlow` of `work1` which is printing "foo" three times
* `flow2` is a `ParallelFlow` of `work2` and `work3` which respectively print "hello" and "world" in parallel
* `flow3` is a `ConditionalFlow`. It first executes `flow2` (a workflow is also a work), then if `flow2` is completed, it executes `work4`, otherwise `work5` which respectively print "ok" and "nok"
* `flow4` is a `SequentialFlow`. It executes `flow1` then `flow3` in sequence.

With Easy Flows, this workflow can be implemented with the following JSON string:

```json
{
    "define": {
        "$work1": [ "PrintMessage",  { "message": "foo"  }  ],
        "$work2": [ "PrintMessage",  { "message": "hello"  }  ],
        "$work3": [ "PrintMessage",  { "message": "world"  }  ],
        "$work4": [ "PrintMessage",  { "message": "done"  }  ]
    },
    "flow4": {
        "comment": "run flow1 and then run flow3",
        "launch": "flow1",
        "launch": "flow3",
    },
    "flow1": {
        "comment": "call $work1 three times (print foo 3 times) then...",
        "repeat": ["$work1",3],
        "launch": "flow3"
    },
    "flow2": {
        "comment": "run $work2 and $work3 (parallel flow)'",
        "execute": [ "$work2", "$work3" ],
    },
    "flow3"{
        "comment": "run flow2, then test something and run W4 or W5",
        "launch": "flow2"
        "when": "Completed",
        "if" : ["isGood", "$work2"],
        "ifnot": ["isGood","$work3"],
    }
}

    $engine = new WorkFlowEngine();
    $engine->load($workflow);
    $engine->run(['context'=>'stuffFromMyApp']);  // whatever might be useful
```

But for a specific student, I want to print 'foo' four times.  I create an override of the specific step(s) I want to change for THAT student.


```
 "repeatFlow": {
        "named": "print foo 4 times for THAT student",
        "repeat": "$work1",
        "times": 4,
        "then": "conditionalFlow"
    },

    $engine = new WorkFlowEngine();
    $engine->load($workflow);
    $engine->add($workflow2);         //  <=== override just the repeat flow
    $engine->run(['context'=>'stuff']);
```

In my implementation, all JSON files are in a single directory with filenames like 'completion-course-16' or 'completion-student-153'.  I apply them in the hierarchy sequqnce, if they exist.




This is not a very useful workflow, but just to give you an idea about how to write workflows with Easy Flows.





## My Use-Case


 I'm writing an education plugin for Moodle that needs to access 'enrollment', 'gradebook', 'assignment', 'completion' and other Moodle features.  I use them to orchestrate student actions like starting a course, submitting an assignment, completing a lesson, etc.

Except that every site has different policies, and each faculty may have their own policies.  Courses have unique needs, or even multiple sets of needs (eg: some students are hybrid, others fully online on the same timetable, still others are auditing or learn-at-your-own-speed).  And of course some students may have accommodations.

So I created (in PHP) a set of 'workunits' that encapsulate Moodle's API's and can interrogate my plugin's data.  Then I wrote 'site' workflows for each activity.

An instance of running a workflow for a specific student takes the 'site' workflow and overloads it with optional course-category, course, and student workflows (in that sequence).





## License

Easy Flows is released under the terms of the [MIT license](https://opensource.org/license/mit/).  Mahmoud Ben Hassine at [j-easy/easy-flows](https://github.com/j-easy/easy-flows) is the original author.

