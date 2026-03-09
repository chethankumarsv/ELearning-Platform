<?php
session_start(); require __DIR__.'/../../includes/config.php';

$uid = $_SESSION['user_id'] ?? 0;

// load prefs or sensible defaults
$p = $conn->prepare("SELECT branch,year,goal,languages,topics FROM student_prefs WHERE user_id=?");
$p->bind_param('i',$uid); $p->execute();
$pref = $p->get_result()->fetch_assoc() ?: ['branch'=>'CSE','year'=>'3','goal'=>'placements','languages'=>'en','topics'=>'DSA, System Design'];

$topics = array_filter(array_map('trim', explode(',', $pref['topics'])));
$langs  = array_filter(array_map('trim', explode(',', $pref['languages'])));

$q = $conn->query("
  SELECT m.id,m.name,m.headline,m.branch,m.languages,m.timezone,m.rating,m.response_hours,m.hourly_rate,
         GROUP_CONCAT(s.skill) skills
  FROM mentors m
  LEFT JOIN mentor_skills s ON s.mentor_id=m.id
  WHERE m.is_active=1
  GROUP BY m.id
  LIMIT 300
");

function jaccard($a,$b){ $A=array_unique(array_map('strtolower',$a)); $B=array_unique(array_map('strtolower',$b));
  $i=count(array_intersect($A,$B)); $u=count(array_unique(array_merge($A,$B))); return $u? $i/$u:0; }
function clamp($x,$min,$max){ return max($min,min($max,$x)); }

$out=[];
while($m=$q->fetch_assoc()){
  $skills = array_filter(array_map('trim', explode(',', $m['skills'] ?? '')));
  $low    = array_map('strtolower',$skills);
  $skill_match   = jaccard($topics,$skills);
  $branch_align  = ($m['branch']===$pref['branch']) ? 1 : (in_array($pref['branch'],['CSE','ISE','AIML']) && in_array($m['branch'],['CSE','ISE','AIML']) ? .5 : 0);
  $goal_align = 0;
  if($pref['goal']==='placements' && (in_array('dsa',$low)||in_array('system design',$low))) $goal_align=1;
  if($pref['goal']==='higher'     && (in_array('gate',$low)||in_array('research',$low)))     $goal_align=1;
  if($pref['goal']==='core'       && (in_array('cad',$low)||in_array('vlsi',$low)||in_array('power systems',$low))) $goal_align=1;
  if($pref['goal']==='startup'    && (in_array('product',$low)||in_array('entrepreneurship',$low))) $goal_align=1;
  $mentor_langs = array_filter(array_map('trim', explode(',', $m['languages'] ?? '')));
  $language_match = count(array_intersect($langs,$mentor_langs)) ? 1 : (in_array('en',$mentor_langs)?0.5:0);
  $availability_overlap = 0.5; // TODO: compute from mentor_slots vs student availability
  $rating_norm = clamp(($m['rating']??4.5)/5,0,1);
  $resp_norm   = clamp(1 - min(48,(int)$m['response_hours'])/48,0,1);
  $score = 40*$skill_match + 15*$branch_align + 15*$goal_align + 10*$language_match + 10*$availability_overlap + 5*$rating_norm + 5*$resp_norm;

  $m['skills']=$skills; $m['score']=round($score,2);
  $out[]=$m;
}
usort($out, fn($a,$b)=> $b['score']<=>$a['score']);
header('Content-Type: application/json'); echo json_encode(array_slice($out,0,8));
