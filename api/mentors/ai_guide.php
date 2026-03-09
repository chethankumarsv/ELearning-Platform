<?php
session_start(); require __DIR__.'/../includes/config.php';
$uid = $_SESSION['user_id'] ?? 0;

// Load prefs
$p = $conn->prepare("SELECT branch,year,goal,languages,topics FROM student_prefs WHERE user_id=?");
$p->bind_param('i',$uid); $p->execute();
$pref = $p->get_result()->fetch_assoc() ?: ['branch'=>'CSE','year'=>'3','goal'=>'placements','languages'=>'en','topics'=>'DSA'];

$prompt = [
  'system' => "You are an engineering career mentor for {$pref['branch']}, year {$pref['year']}. Goal: {$pref['goal']}. Be specific and practical.",
  'user'   => "Student topics: {$pref['topics']}. Create a 14-day plan (daily bullets), 5 key resources (links), and 3 questions to ask a human mentor next session."
];

/* TODO: call your LLM here.
   Example (if you have a Flask model proxy):
   $res = file_get_contents('http://127.0.0.1:5000/ai_guide?prompt='.urlencode(json_encode($prompt)));
   echo $res; exit;
*/
header('Content-Type: application/json');
echo json_encode([
  'plan' => [
    ['day'=>1,'task'=>'Revise arrays & strings; 20 LeetCode Easy','resource'=>'GfG DSA Sheet – arrays'],
    ['day'=>2,'task'=>'Stacks/Queues; 2 interview questions notes','resource'=>'NeetCode playlist'],
    // ... fill out to 14 days in your real LLM response
  ],
  'resources' => [
    ['title'=>'NPTEL Discrete Math (audit free)','url'=>'https://swayam.gov.in'],
    ['title'=>'GfG DSA Sheet','url'=>'https://www.geeksforgeeks.org'],
    ['title'=>'LeetCode Top 75','url'=>'https://leetcode.com'],
    ['title'=>'System Design Primer','url'=>'https://github.com/donnemartin/system-design-primer'],
    ['title'=>'Aptitude practice','url'=>'/aptitude.php']
  ],
  'mentor_questions' => [
    'Which topics should I prioritize for my target companies?',
    'Can you critique my last 3 code submissions?',
    'What project idea would strengthen my resume for placements?'
  ]
]);
