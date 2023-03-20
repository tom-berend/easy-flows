### NOT READY - DO NOT USE

## Easy-Flow for PHP

This is based on the wonderfully intuitive **Easy-Flow** package at [j-easy/easy-flows](https://github.com/j-easy/easy-flows) but with the following differences:

- PHP (of course)
- workflows stored in json files (so users can edit them)
- support for workflow overloading (so specific flows can override general flows)
- no parallelism, just script orchestration (because PHP)



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

Easy Flows is a workflow engine for PHP, providing building blocks to create and run composable, overloadable workflows.


<p align="center">
    <img src="https://communityreading.org/assets/easy-flows.png" width="70%">
</p>

You don't need to learn a complex notation or concepts, these are the only flows that you need to think about.

## How does it work?

A unit of work in Easy Flows is a PHP method represented by the `WorkUnit` interface.  Let's write some work that prints a message to the webpage.

```php
<?php

class WorkUnitEngine{
    public array $workUnitContext = [];
}




class PrintMessage extends WorkUnitEngine implements WorkUnit
{

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
    <img src="https://communityreading.org/assets/easy-flows-example.png" width="70%">
</p>


* `flow1` is a `RepeatFlow` of `work1` which is printing "foo" three times
* `flow2` is a `SequentialFlow` of `work2` and `work3` which respectively print "hello" and "world".  `work3` sets a flag to guide the workflow.
* `flow3` is a `ConditionalFlow`. It first executes `flow2` (a workflow is also a work), then if `flow2` is completed, it executes `work4`, otherwise `work5` which respectively print "ok" and "nok"
* `flow4` is a `SequentialFlow`. It executes `flow1` then `flow3` in sequence.

With Easy Flows, this workflow can be implemented with the following JSON string:


```php
// I have created some methods ('workunits') in my workflow class.

function work1(){ print 'foo';   }
function work2(){ print 'hello'; }
function work3(){ print 'world'; $this->context['completed']=true; }
function work4(){ print 'done';   }

```

The `set` command assignes a value a value to the context array.  Of course the first workunit method could do that, but sometimes you want your users to be able
to set parameter.

Valid datatypes are strings, integers, and booleans


```json
{
    "flow4": {
        "comment": "run flow1 and then run flow3",
        "set": ["completed",false],
        "set": ["repeatcount",3],
        "launch": "flow1",
        "launch": "flow3",
    },
    "flow1": {
        "comment": "call work1 three times (print foo 3 times) then...",
        "repeat": ["work1","repeatcount"],
        "launch": "flow3"
    },
    "flow2": {
        "comment": "run $work2 and $work3 (parallel flow)'",
        "execute": [ "work2", "work3" ],
    },
    "flow3"{
        "comment": "run flow2, then test something and run W4 or W5",
        "launch": "flow2",
        "if" : ["completed", true, "$work2"],
        "if" : ["completed",false,"$work3"],
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

