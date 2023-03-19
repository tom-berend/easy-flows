<?php


class PrintMessage extends WorkUnitEngine implements WorkUnit
{
    public array $workUnitContext = [];

    function __construct(array $workUnitContext)
    {
        if (!isset($context['message']))
            throw new Exception("PrintMessage workunit requires a message'");

        $this->registerWorkUnit($this);
        $this->workUnitContext = $workUnitContext;  // just saving it
    }

    function getName(): string
    {
        return get_class($this);   // the name of this class
    }

    function run(array $workFlowContext)
    {
        echo $this->context['message'] ?? '', '<br>';
    }
}
// function composeWorkFlowFromSeparateFlowsAndExecuteIt() {

//     $work1 = new PrintMessage("foo");
//     $work2 = new PrintMessage("hello");
//     $work3 = new PrintMessage("world");
//     $work4 = new PrintMessage("done");

//     RepeatFlow repeatFlow = aNewRepeatFlow()
//             .named("print foo 3 times")
//             .repeat(work1)
//             .times(3)
//             .build();

//     ParallelFlow parallelFlow = aNewParallelFlow()
//             .named("print 'hello' and 'world' in parallel")
//             .execute(work2, work3)
//             .with(executorService)
//             .build();

//     ConditionalFlow conditionalFlow = aNewConditionalFlow()
//             .execute(parallelFlow)
//             .when(COMPLETED)
//             .then(work4)
//             .build();

//     SequentialFlow sequentialFlow = aNewSequentialFlow()
//             .execute(repeatFlow)
//             .then(conditionalFlow)
//             .build();

//     WorkFlowEngine workFlowEngine = aNewWorkFlowEngine().build();
//     WorkContext workContext = new WorkContext();
//     WorkReport workReport = workFlowEngine.run(sequentialFlow, workContext);
//     executorService.shutdown();
//     assertThat(workReport.getStatus()).isEqualTo(WorkStatus.COMPLETED);
//     System.out.println("workflow report = " + workReport);
// }
