<?php


//////////////////////////////////////////////////
//////////////////////////////////////////////////
//    UnitTestCase is the parent of all testable classes


// param is either 'ALL' or 'CHEAP'
function runUnitTests(string $which = 'CHEAP')
{
    assertTrue($which == 'ALL' or $which == 'CHEAP');

    if (SIMPLIFIED_DRILLS) return '';

    // require_once ('coursebuilder/importlatex.php');
    // $o = new importLatex();
    // $o->runTests();


    $o = new Parsedown();
    $o->runTests();

    $o = new SafeEvalMath();
    $o->runTests();

    $o = new Cards();
    $o->runTests();


    $o = new SafeEvalMath();
    $o->runTests();

    $o = new Utils();
    $o->runTests();

    // parseDownUnitTests();
    // $GLOBALS['tests'] += 1;


    if ($which == 'ALL') {
        $GLOBALS['doingUnitTest'] = true;   // this switches to a test database

        $textbook = $_SESSION['currentOpenTextbook'];
        $filename = "{$GLOBALS['coursebuilderpath']}/$textbook/MATHCODE-TEST.SQLite3";
        unlink($filename);  // physically delete it
        setupDatabases($filename);           // make sure they exist

        loadTestData();             // our standard test data


        // if (empty($which)) {

        $o = new MathDrills();
        $o->runTests();

        $o = new backupdata();
        $o->runTests();

        $o = new ExportMathcode();
        $o->runTests();

        $o = new Flashcards();
        $o->runTests();

        $o = new HTMLtester();
        $o->runTests();

        $o = new ZettleView(AUTHORVIEW);
        $o->runTests();





        //     testNeutered();
        //     $GLOBALS['tests'] += 1;

        //     // $o = new StepCode3();
        //     // $o->runTests();

        //     $o = new HTMLTester();
        //     $o->runTests();

        //     $o = new BackupData();
        //     $o->runTests();

        //     $o = new Paragraphs();
        //     $o->runTests();

        //     $o = new Utils();
        //     $o->runTests();

        //     $o = new MoodleStuff();
        //     $o->runTests();
        // }
    }


    $GLOBALS['doingUnitTest'] = false;   // this switches back to production database

    $GLOBALS['printNiceCapture'] .= finalReport();

    $GLOBALS['errorString'] = '';  // in case we had an error
}


function loadTestData()
{

    $courses = new Courses();
    $topics = new Topics();
    $activities = new Activities();
    $steps = new Steps();
    $paragraphs = new Paragraphs();



    // // ok, let's try adding something
    // $courseuniq = $courses->addCourse('Testbook', 'New Chapter');
    // assertTrue($this->countRecordsForThisbook($courses, 'Unittesting') == 1);
    // $new = $courses->getCourse($courseuniq);
    // assertTrue($new['coursename'] == 'Testbook', "got {$new['coursename']}");

    $allCourses = $courses->allCourses();   // we should be empty
    assertTrue(count($allCourses) == 0);

    $courseUniq = $courses->addCourse('test textbook', 'test');
    $courses->updateCourse($courseUniq, ['coursename' => 'test course', 'coursesummary' => 'test summary']);

    $allCourses = $courses->allCourses();    // we should have a course now
    assertTrue(count($allCourses) == 1);

    //////////////////////////////////////
    //////////////////////////////////////
    // add a topic

    $topicUniq = $topics->addTopic($courseUniq);
    $topics->updateTopic($topicUniq, ['topicname' => 'first topic', 'topicsummary' => 'first topic summary']);

    $topics->resequenceTopics($courseUniq);
    $form = $topics->getTopic($topicUniq);
    // printNice($form);
    assertTrue($form['topicsequence'] == 10);


    //////////////////////////////////////
    //////////////////////////////////////
    // add an activity


    $activityUniq = $activities->addActivity($topicUniq, 'First Activity');
    $form = $activities->getActivity($activityUniq);
    assertTrue(($form['activityname']) == 'First Activity');

    $activities->resequenceActivities($topicUniq);
    $form = $activities->getActivity($activityUniq);
    assertTrue($form['act_seq'] == 10);



    //////////////////////////////////////
    //////////////////////////////////////
    // add a step

    $stepUniq = $steps->addStep($activityUniq, $GLOBALS['stepTypes'][0]);  // not sure what valid stepTypes are yet

    $steps->updateStep($stepUniq, ['title' => 'title of first step', 'intent' => 'intent of first step']);

    $form = $steps->getStep($stepUniq);
    assertTrue($form['title'] == 'title of first step');

    // we should have a single paragraph that belongs to this step (added by 'addStep()')

    // $paragraphs = new Paragraphs();
    // $allP = $paragraphs->getParagraphsByStep($stepUniq);
    // assertTrue(count($allP) == 1);   // we always create an empty paragraph
    // assertTrue($allP[0]->stepuniq == $stepUniq); // paragraphs are OBJECTS

}







class UnitTestCase
{

    public $ClassBeingTested = "Unknown";
    public $testFunction = "Unknown";

    public function __construct()
    {
        $GLOBALS['tests'] = 0;
        $GLOBALS['assertions'] = 0;
        $GLOBALS['fails'] = 0;
    }
    public function runTests()
    {
        $this->ClassBeingTested = get_class($this);
        $functions = get_class_methods($this->ClassBeingTested);
        $GLOBALS['tests'] = 0;

        foreach ($functions as $function) {
            if (substr($function,0, 4) == 'test') { // only function test----(),
                $GLOBALS['tests'] += 1;
                $this->testFunction = $function;
                $this->$function(); // and run each test function
            }
        }

        // // now check that there are no lower-case conflicts (helps debugging)
        // $o = get_object_vars($this);
        //     foreach($o as $key=>$value){
        //         $k=strtolower($key).'123123';
        //         //$this->assertTrue(!isset($lowerCase[$k]),"likely a case conflict with '$key' and '{$lowerCase[$k]}'");
        //         if(isset($lowerCase[$k])){
        //             $message = "likely a case conflict in '".get_class($this)."' with '$key' and '{$lowerCase[$k]}'";
        //       $this->fail++;
        //       $this->errorstring .= "<br>{$this->testFunction}:    <b>$message</b>";
        //         }
        //         $lowerCase[$k] = $key;
        //     }
    }

    function finalReport()
    {
        $span = "<span style=\"padding: 8px; margin-top: 1em; background-color: green; color: white;\">";
        if ($GLOBALS['fails'] > 0) {
            $span = "<span style=\"padding: 8px; margin-top: 1em; background-color: red; color: white;\">";
        }

        $HTML = "<br><br>" . $span;
        $HTML .= " <strong>{$GLOBALS['tests']}</strong> tests,";
        $HTML .= "  <strong>{$GLOBALS['assertions']}</strong> assertions,";
        $HTML .= "  <strong>{$GLOBALS['fails']}</strong> fails";
        $HTML .= " </span>";
        // $HTML .= " <br>{$GLOBALS['errorString']}";
        return ($HTML);
    }


    function assertEquals($a, $b, $comment = 'Assert Failed')
    {
        assertTrue($a == $b, $comment);
    }
    function assertTrue($a, $comment = 'Assert Failed')
    {
        assertTrue($a, $comment);
    }
}




function assertTrue($assertion, $comment = "Assert Failed")
{
    // if (!isset($GLOBALS['assertions'])) { // must be initialized, whether testing or not
    //     $GLOBALS['assertions'] = 0;
    //     $GLOBALS['fails'] = 0;
    // }

    $GLOBALS['assertions'] += 1;
    if ($assertion) {
        return ($assertion);
    } else {
        $neuteredComment = htmlentities($comment);
        $errorString = "<br><span style='color:red;'>Assertion Failed:   </span><b>,$neuteredComment</b>";
        $errorText = PHP_EOL . PHP_EOL . "Assertion Failed:  $neuteredComment " . PHP_EOL;
        $array = debug_backtrace();
        foreach ($array as $key => $value) {
            if (isset($value['file']) and isset($value['line'])) {
                $errorString .= "<br>&nbsp;{$value['file']}({$value['line']})";
                $errorText .= "{$value['file']}({$value['line']}),   ";
            }
        }

        $GLOBALS['printNiceCapture'] .= "<br> $errorString;";

        // // always write to the log file
        // file_put_contents($GLOBALS['logfilename'], $errorText, FILE_APPEND);

        $GLOBALS['fails'] += 1;
        echo $errorString;
    }
    return ($assertion); // allows chained assertions
}



function printNice($elem, $message = '')
{
    $emergency = true;  // everything is broken, and debugging doesn't help


    if (!$GLOBALS['debugMode'] and !($GLOBALS['emergency'])??'') {
        return;
    }


    if (!isset($GLOBALS['printNiceCapture'])) {
        $GLOBALS['printNiceCapture'] = '';
    }

    // watch out for infinite loops here
    if (!isset($GLOBALS['printNiceSafety']))
        $GLOBALS['printNiceSafety'] = 10000;

    $GLOBALS['printNiceSafety'] -= 1;

    if ($GLOBALS['printNiceSafety'] < 0)
        return;
    // watch out for infinite loops here


    $HTML = '<br /><p>from ';
    $HTML .= Utils::backTrace();

    $span = $span2 = '';

    // if (!$GLOBALS['debugMode']) {
    $span = "<span style='color:blue;'>";
    $span2 = "</span>";
    // }
    // if debug is off, write to error.log
    if (is_string($elem)) {
        $msg = str_replace('<br />', "\n", $HTML);
        $msg = str_replace('<p>', " ", $msg);
        $msg = str_replace('</p>', "", $msg);
        // file_put_contents('./error.log', "\n" . date('Y-M-d TH:i:s') . " $elem $msg", FILE_APPEND);
        // return;
    } // debugging isn't on
    // }



    if (is_object($elem)) {
        // just cast it to an array
        //  $HTML .= "<b>(OBJECT)</b> $span $message $span2" . printNiceHelper((array)$elem) . '</p>';
        $HTML .= "$span $message $span2" . printNiceHelper((array)$elem) . '</p>';
    } else {
        // print whatever we got
        $HTML .= "$span $message $span2" . printNiceHelper($elem) . '</p>';
    }

    echo $HTML;

}

// printNice utility for debugging

function printNiceR($elem)
{
    $HTML = printNiceHelper($elem);
    return ($HTML);
}

// helper function for printNice()
function printNiceHelper($elem, $max_level = 12, $print_nice_stack = array(), $HTML = '')
{

    // // show where we were called from
    // $backtrace = debug_backtrace(); // if no title, then show who called us
    // if ($backtrace[1]['function'] !== 'printNice' and $backtrace[1]['function'] !== 'printNiceHelper') {
    //     if (isset($backtrace[1]['class'])) {
    //         $HTML .= "<hr /><h1>class {$backtrace[1]['class']}, function {$backtrace[1]['function']}() (line:{$backtrace[1]['line']})</h1>";
    //     }
    // }

    // $MAX_LEVEL = 5;


    if (is_array($elem) || is_object($elem)) {
        // if (in_array($elem, $print_nice_stack, true)) {
        //     $HTML .= "<hr /><h1>class {$backtrace[1]['class']}, function {$backtrace[1]['function']}() (line:{$backtrace[1]['line']})</h1>";
        //     return ($HTML);
        // }
        if ($max_level < 1) {
            //print_r(debug_backtrace());
            //die;
            $HTML .= "<FONT COLOR=RED>MAX STACK LEVEL EXCEEDED</FONT>";
            return ($HTML);
        }

        $print_nice_stack[] = &$elem;
        $max_level--;

        $HTML .= "<table border=1 cellspacing=0 cellpadding=3 width=100%>";
        if (is_array($elem)) {
            $HTML .= '<tr><td><b>ARRAY</b></td></tr>';
        } elseif (is_object($elem)) {
            $HTML .= '<tr><td><b>OBJECT</b></td></tr>';
        } else {
            $HTML .= '<tr><td colspan=2 style="background-color:#333333;"><strong>';
            $HTML .= '<font color=white>OBJECT Type: ' . get_class($elem) . '</font></strong></td></tr>';
        }
        $color = 0;
        foreach ((array)$elem as $k => $v) {
            if ($max_level % 2) {
                $rgb = ($color++ % 2) ? "#888888" : "#BBBBBB";
            } else {
                $rgb = ($color++ % 2) ? "#8888BB" : "#BBBBFF";
            }
            $HTML .= '<tr><td valign="top" style="width:40px;background-color:' . $rgb . ';">';
            $HTML .= '<strong>' . $k . "</strong></td><td>";
            $HTML .= printNiceHelper($v, $max_level, $print_nice_stack);

            $HTML .= "</td></tr>";
        }

        $HTML .= "</table>";
        return ($HTML);
    }
    if ($elem === null) {
        $HTML .= "<font color=green>NULL</font>";
    } elseif ($elem === 0) {
        $HTML .= "0";
    } elseif ($elem === true) {
        $HTML .= "<font color=green>TRUE</font>";
    } elseif ($elem === false) {
        $HTML .= "<font color=green>FALSE</font>";
    } elseif ($elem === "") {
        $HTML .= "<font color=green>EMPTY STRING</font>";
    } elseif (is_integer($elem)) {
        $HTML .= "<font color=blue>$elem</font>";
    } elseif (is_double($elem)) {
        $HTML .= "<font color=blue>" . round($elem, 3) . "</font>";
    } elseif (is_string($elem)) {
        $HTML .= neutered($elem);
    } else {
        printNice(getType($elem), 'dealing with this in printNice()');
        $HTML .= $elem;
    }
    return ($HTML);
}

