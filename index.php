<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo 'Test runner for easy-flow<br><br>';


// define('CONTEXT_SYSTEM', 10);
// define('CONTEXT_USER', 30);
// define('CONTEXT_COURSECAT', 40);
// define('CONTEXT_COURSE', 50);
// define('CONTEXT_MODULE', 70);
// define('CONTEXT_BLOCK', 80);

// a WorkContext is just an array of stuff that your code will pass to a work element, for example
// myContext = ['topic'=>'myTopic', 'activity'=>'myActivity'];


$GLOBALS['debugMode']=true;
$GLOBALS['printNiceCapture'] = '';
$GLOBALS['errorString'] = '';
require_once('src/unittest.php');
require_once('../../moodle/mod/mathcode/coursebuilder/utilities.php');
require_once('src/workFlowEngine.php');

require_once('src/miniyaml.php');
require_once('src/testminiyaml.php');
require_once('src/safeevalmath.php');


$o = new Scanner();
$o->runTests();


// $o = new SafeEvalMath();
// $o->runTests();



// $o = new TestMiniYaml();
// $o->runTests();


echo $o->finalReport();
return;



////// baseball
$datafile ='workflows/baseball.yaml';
$data = file_get_contents($datafile);
assertTrue($data,"could not read '$datafile'");
printNice($data,'baseball before');

$test = miniYAML::Load($data);
printNice($test,'baseball after');


//// test
$datafile ='workflows/test.yaml';
$data = file_get_contents($datafile);
assertTrue($data,"could not read '$datafile'");
printNice($data,'test before');

$test = miniYAML::Load($data);
printNice($test,'test.yaml after');
die;



// bring in the list of all possible workunits
$GLOBALS['allWorkUnits'] = [];
$all = scandir('workunits');

foreach ($all as $workunit) {
    if (substr($workunit, -4) == '.php') {
        require_once "workunits/$workunit";

        // $workunitObj = new $workunit;
        $GLOBALS['allWorkUnits'][$workunit] = $workunit;
    }
}

$test = composeWorkFlowFromSeparateFlowsAndExecuteIt();
echo $GLOBALS['printNiceCapture'];
return;




function composeWorkFlowFromSeparateFlowsAndExecuteIt()
{



    // 'PrintMessage' is provided by the programmer.  only programmers can create workunits

    // this part is serialized to the database
    // nothing special about '$' in $work1, just looks like PHP for clarity

    $workflow = [
        'define'=> [
            '$work1' => ['PrintMessage',['message' => 'foo']],
            '$work2' => ['PrintMessage',['message' => 'hello']],
            '$work3' => ['PrintMessage',['message' => 'world']],
            '$work4' => ['PrintMessage',['message' => 'done']],
            '$work4' => ['PrintMessage',['message' => 'done']],
        ],

        'repeatFlow' => [
            'named' => 'print foo 3 times',
            'repeat' => '$work1',
            'times' => 3,
            'then' => 'parallelFlow',
            'then' => 'conditionalFlow',
        ],

        'parallelFlow' => [
            'named' => "print 'hello' and 'world' in parallel",
            'execute' => ['$work2', '$work3'],
        ],


        'conditionalFlow' => [
            'then'=>'parallelFlow',
            'named' => "run parallelFlow again and then print 'done'",
            'when' => 'Completed',
            'execute' => '$work4',
            'otherwise' =>'$work4',
        ],

        'sequentialFlow' => [
            'named' => "run repeatFlow and then run conditionalFlow",
            'then' => 'repeatFlow',
            'then' => 'conditionalFlow',
        ],
    ];

    $workflow2 = [

        'repeatFlow' => [
            'named' => 'for this client, we print foo 4 times',
            'repeat' => '$work1',
            'times' => 4,
            'then' => 'parallelFlow',
            'then' => 'conditionalFlow',
        ],
    ];


    //TODO: create as load(), add(), and run()

    printNice(json_encode($workflow));
    return;
    $engine = new WorkFlowEngine();
    $engine->load($workflow);
    $engine->add($workflow2);
    $engine->run(['context'=>'stuff']);

}



//     WorkFlowEngine workFlowEngine = aNewWorkFlowEngine().build();
//     WorkContext workContext = new WorkContext();
//     WorkReport workReport = workFlowEngine.run(sequentialFlow, workContext);
//     executorService.shutdown();
//     assertThat(workReport.getStatus()).isEqualTo(WorkStatus.COMPLETED);
//     System.out.println("workflow report = " + workReport);
// }

// @Test
// public void defineWorkFlowInlineAndExecuteIt() {

//     PrintMessageWork work1 = new PrintMessageWork("foo");
//     PrintMessageWork work2 = new PrintMessageWork("hello");
//     PrintMessageWork work3 = new PrintMessageWork("world");
//     PrintMessageWork work4 = new PrintMessageWork("done");

//     ExecutorService executorService = Executors.newFixedThreadPool(2);
//     WorkFlow workflow = aNewSequentialFlow()
//             .execute(aNewRepeatFlow()
//                         .named("print foo 3 times")
//                         .repeat(work1)
//                         .times(3)
//                         .build())
//             .then(aNewConditionalFlow()
//                     .execute(aNewParallelFlow()
//                                 .named("print 'hello' and 'world' in parallel")
//                                 .execute(work2, work3)
//                                 .with(executorService)
//                                 .build())
//                     .when(COMPLETED)
//                     .then(work4)
//                     .build())
//             .build();

//     WorkFlowEngine workFlowEngine = aNewWorkFlowEngine().build();
//     WorkContext workContext = new WorkContext();
//     WorkReport workReport = workFlowEngine.run(workflow, workContext);
//     executorService.shutdown();
//     assertThat(workReport.getStatus()).isEqualTo(WorkStatus.COMPLETED);
//     System.out.println("workflow report = " + workReport);
// }

// @Test
// public void useWorkContextToPassInitialParametersAndShareDataBetweenWorkUnits() {
//     WordCountWork work1 = new WordCountWork(1);
//     WordCountWork work2 = new WordCountWork(2);
//     AggregateWordCountsWork work3 = new AggregateWordCountsWork();
//     PrintWordCount work4 = new PrintWordCount();
//     ExecutorService executorService = Executors.newFixedThreadPool(2);
//     WorkFlow workflow = aNewSequentialFlow()
//             .execute(aNewParallelFlow()
//                         .execute(work1, work2)
//                         .with(executorService)
//                         .build())
//             .then(work3)
//             .then(work4)
//             .build();

//     WorkFlowEngine workFlowEngine = aNewWorkFlowEngine().build();
//     WorkContext workContext = new WorkContext();
//     workContext.put("partition1", "hello foo");
//     workContext.put("partition2", "hello bar");
//     WorkReport workReport = workFlowEngine.run(workflow, workContext);
//     executorService.shutdown();
//     assertThat(workReport.getStatus()).isEqualTo(WorkStatus.COMPLETED);
// }





// interface WorkFlow
// {
// }

// interface workReport{

// }

// interface WorkFlowContext
// {
//     public function ConditionalFlow(string $name, Work $initialWorkUnit, Work $nextOnPredicateSuccess, Work $nextOnPredicateFailure, WorkReportPredicate $predicate);
//     public function  ParallelFlow(String $name, array $workUnits /*, ParallelFlowExecutor $parallelFlowExecutor */);
// }

// interface WorkFlowEngine
// {
//     public function run(WorkFlow $workFlow, WorkContext $workContext):WorkFlowReport;
// }



// $workFlow = new WorkFlow('sendWelcomeEmail');


// $workContext = new WorkContext();

// // when
// workFlowEngine . run(workFlow, workContext);
