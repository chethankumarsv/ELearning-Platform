<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Check if user is admin
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Handle mentor management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_mentor') {
        $name = trim($_POST['name']);
        $branch = trim($_POST['branch']);
        $headline = trim($_POST['headline']);
        $skills = $_POST['skills'];
        $bio = trim($_POST['bio']);
        $email = trim($_POST['email']);
        
        // Validate required fields
        if (empty($name) || empty($branch) || empty($headline)) {
            echo json_encode(['success' => false, 'message' => 'Name, branch, and headline are required']);
            exit;
        }
        
        // Convert skills array to string
        $skills_str = is_array($skills) ? implode(',', $skills) : $skills;
        
        try {
            $stmt = $conn->prepare("INSERT INTO mentors (name, branch, headline, skills, bio, email, rating, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 4.5, 1, NOW())");
            $stmt->bind_param("ssssss", $name, $branch, $headline, $skills_str, $bio, $email);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Mentor added successfully', 'mentor_id' => $stmt->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add mentor: ' . $stmt->error]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_mentor') {
        $mentor_id = (int)$_POST['mentor_id'];
        
        try {
            $stmt = $conn->prepare("UPDATE mentors SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $mentor_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Mentor deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete mentor']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'add_slot') {
        $mentor_id = (int)$_POST['mentor_id'];
        $start_at = trim($_POST['start_at']);
        $duration = (int)$_POST['duration'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO mentor_slots (mentor_id, start_at, duration_minutes, is_booked, created_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt->bind_param("isi", $mentor_id, $start_at, $duration);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Time slot added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add time slot']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Find a Mentor — E-Learning</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --bg-main:#f9fbff;
  --primary:#5a60f6;
  --secondary:#37c2e0;
  --accent:#ff7b54;
  --text-dark:#1c1c1c;
  --text-muted:#677c92;
  --card-bg:#ffffff;
  --gradient-hero:linear-gradient(135deg,#8a8cff,#37c2e0);
  --gradient-btn:linear-gradient(90deg,#ff8a3d,#ff5c7c);
  --success:#10b981;
  --warning:#f59e0b;
  --danger:#ef4444;
  --max-width:1200px;
}

/* Base */
*{box-sizing:border-box}
body{margin:0;font-family:Poppins,system-ui,-apple-system,"Segoe UI",Roboto,Arial;background:var(--bg-main);color:var(--text-dark);overflow-x:hidden}

/* Header */
header{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.85);backdrop-filter:blur(10px);box-shadow:0 4px 18px rgba(0,0,0,.08)}
.nav{max-width:var(--max-width);margin:auto;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.brand{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:22px}
nav ul{list-style:none;display:flex;gap:20px;margin:0;padding:0}
nav a{text-decoration:none;color:var(--text-dark);font-weight:600}
nav a:hover{color:var(--primary)}
.btn-pill{background:var(--gradient-btn);color:#fff;padding:10px 18px;border-radius:30px;border:0;cursor:pointer;font-weight:700;box-shadow:0 4px 14px rgba(255,90,120,.2)}
.admin-badge{background:var(--success);color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}

/* Hero */
.hero{position:relative;background:var(--gradient-hero);color:#fff;text-align:center;padding:90px 24px 110px;border-radius:0 0 48px 48px;overflow:hidden}
.hero h1{margin:0;font-size:48px;font-weight:800}
.hero p{margin:10px auto 0;max-width:800px;opacity:.92}
#particles{position:absolute;inset:0;z-index:0}
.hero-content{position:relative;z-index:1}

/* Content */
.wrap{max-width:var(--max-width);margin:-50px auto 40px;padding:0 20px;position:relative;z-index:2}
.card-panel{background:var(--card-bg);border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.08);padding:26px}
.title{font-size:26px;font-weight:800;margin:0 0 6px}
.sub{color:var(--text-muted);margin:0 0 16px}

/* Admin Controls */
.admin-controls{background:var(--card-bg);border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.08);padding:26px;margin-bottom:20px}
.admin-section{margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #eee}
.admin-section h3{margin:0 0 12px;color:var(--primary)}
.form-group{margin-bottom:15px}
.form-group label{display:block;margin-bottom:5px;font-weight:600;color:var(--text-dark)}
.form-group input, .form-group textarea, .form-group select{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-family:inherit}
.form-group textarea{min-height:80px;resize:vertical}
.skills-input{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.skill-tag{background:var(--primary);color:white;padding:4px 12px;border-radius:20px;font-size:12px;display:flex;align-items:center;gap:5px}
.skill-tag .remove{background:none;border:none;color:white;cursor:pointer;font-size:14px}

/* Mentor grid */
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
.card{background:#fff;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.05);padding:20px;transition:.25s;position:relative}
.card:hover{transform:translateY(-6px);box-shadow:0 12px 30px rgba(0,0,0,.08)}
.icon{font-size:40px}
.badge{display:inline-block;padding:6px 10px;border-radius:999px;background:linear-gradient(90deg,#8a8cff,#37c2e0);color:#fff;font-weight:700;font-size:12px}
.meta{color:var(--text-muted);font-size:14px}
.btn{display:inline-block;margin-top:10px;padding:10px 14px;border-radius:10px;border:0;background:var(--gradient-btn);color:#fff;font-weight:800;text-decoration:none;cursor:pointer;margin-right:8px}
.btn-secondary{background:var(--secondary)}
.btn-danger{background:var(--danger)}
.admin-actions{position:absolute;top:15px;right:15px;display:flex;gap:5px}
.admin-btn{background:rgba(255,255,255,0.9);border:1px solid #ddd;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px}

/* Chat Box */
.chat-box {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 340px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,.25);
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 999;
}
.chat-header {
  background: var(--gradient-hero);
  color: #fff;
  padding: 10px 14px;
  font-weight: 700;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.chat-messages {
  height: 300px;
  overflow-y: auto;
  padding: 10px;
  background: #f9fbff;
}
.chat-message {
  margin: 8px 0;
  padding: 8px 12px;
  border-radius: 12px;
  max-width: 80%;
  word-wrap: break-word;
}
.chat-message.sent {
  background: var(--secondary);
  color: #fff;
  margin-left: auto;
}
.chat-message.received {
  background: #e5e9ff;
  color: #000;
  margin-right: auto;
}
.chat-input {
  display: flex;
  border-top: 1px solid #eee;
}
.chat-input input {
  flex: 1;
  padding: 10px;
  border: none;
  border-radius: 0;
  outline: none;
}
.chat-input button {
  background: var(--gradient-btn);
  color: #fff;
  border: none;
  padding: 10px 16px;
  cursor: pointer;
  font-weight: 700;
}

/* Modal */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:60}
.modal{width:min(520px,92vw);background:#fff;border-radius:16px;box-shadow:0 18px 60px rgba(0,0,0,.18);padding:18px}
.modal h3{margin:0 0 8px}
.slot{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f3f6ff;border:1px solid #e4eafe;border-radius:12px;padding:10px;margin:8px 0}
.slot .time{color:#2a2f3b;font-weight:600}
.close-x{border:0;background:transparent;font-size:20px;cursor:pointer}

/* Footer */
footer{padding:40px 20px;text-align:center;color:#445}
footer .muted{color:var(--text-muted)}

/* Toast */
.toast{position:fixed;top:20px;right:20px;padding:12px 20px;border-radius:8px;color:white;font-weight:600;z-index:1000;display:none}
.toast.success{background:var(--success)}
.toast.error{background:var(--danger)}
</style>
</head>

<body>
<header>
  <div class="nav">
    <div class="brand">🎓 E-Learning</div>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="courses.php">Courses</a></li>
        <li><a href="mentors.php">Mentors</a></li>
        <li><a href="event.php">Event</a></li>
        <?php if($is_admin): ?>
          <li><span class="admin-badge">ADMIN</span></li>
        <?php endif; ?>
      </ul>
    </nav>
    <button class="btn-pill" onclick="location.href='auth.php?type=login'">Login / Signup</button>
  </div>
</header>

<section class="hero">
  <canvas id="particles"></canvas>
  <div class="hero-content">
    <h1>Find Your Perfect Mentor</h1>
    <p>Personalized suggestions based on your branch, year and goals. Book a 30-minute session or chat instantly.</p>
  </div>
</section>

<div class="wrap">
  <?php if($is_admin): ?>
  <!-- Admin Controls -->
  <div class="admin-controls">
    <h2>Mentor Management</h2>
    
    <div class="admin-section">
      <h3>Add New Mentor</h3>
      <form id="addMentorForm">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" required>
        </div>
        <div class="form-group">
          <label>Branch *</label>
          <select name="branch" required>
            <option value="">Select Branch</option>
            <option value="CSE">Computer Science</option>
            <option value="ECE">Electronics</option>
            <option value="ME">Mechanical</option>
            <option value="CE">Civil</option>
            <option value="EE">Electrical</option>
          </select>
        </div>
        <div class="form-group">
          <label>Headline *</label>
          <input type="text" name="headline" placeholder="e.g., Senior Software Engineer at Google" required>
        </div>
        <div class="form-group">
          <label>Skills</label>
          <div class="skills-input">
            <input type="text" id="skillInput" placeholder="Add a skill">
            <button type="button" onclick="addSkill()">Add</button>
          </div>
          <div id="skillsContainer" style="margin-top:10px"></div>
          <input type="hidden" name="skills" id="skillsHidden">
        </div>
        <div class="form-group">
          <label>Bio</label>
          <textarea name="bio" placeholder="Brief description about the mentor..."></textarea>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="mentor@example.com">
        </div>
        <button type="submit" class="btn">Add Mentor</button>
      </form>
    </div>
    
    <div class="admin-section">
      <h3>Add Time Slot</h3>
      <form id="addSlotForm">
        <div class="form-group">
          <label>Select Mentor</label>
          <select name="mentor_id" id="mentorSelect" required>
            <option value="">Loading mentors...</option>
          </select>
        </div>
        <div class="form-group">
          <label>Start Date & Time</label>
          <input type="datetime-local" name="start_at" required>
        </div>
        <div class="form-group">
          <label>Duration (minutes)</label>
          <input type="number" name="duration" value="30" min="15" max="120" required>
        </div>
        <button type="submit" class="btn">Add Time Slot</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card-panel">
    <div class="title">Available Mentors</div>
    <p class="sub">Connect with experienced professionals for personalized guidance and career advice.</p>
    <div id="mentorGrid" class="grid"></div>
  </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<!-- Chat box -->
<div id="chatBox" class="chat-box">
  <div class="chat-header">
    <span id="chatWithName">Chat</span>
    <button onclick="closeChat()" class="close-x">✕</button>
  </div>
  <div id="chatMessages" class="chat-messages"></div>
  <div class="chat-input">
    <input type="text" id="chatMessage" placeholder="Type a message...">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<!-- Slot Modal -->
<div id="modalBg" class="modal-backdrop">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
      <h3 style="margin:0">Available slots</h3>
      <button class="close-x" onclick="closeModal()">✕</button>
    </div>
    <div id="slotList" style="margin-top:8px"></div>
  </div>
</div>

<footer>
  <div class="muted">Can't find a slot? Come back later—mentors add availability every week.</div>
  <div class="muted">© <?= date('Y') ?> E-Learning Platform</div>
</footer>

<script>
// Particles animation
const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');
function size(){ canvas.width = window.innerWidth; canvas.height = 280; }
window.addEventListener('resize', size); size();

class Particle {
  constructor(){ this.reset(); }
  reset(){
    this.x = Math.random()*canvas.width;
    this.y = Math.random()*canvas.height;
    this.r = 1 + Math.random()*2.5;
    this.dx = (Math.random()-0.5)*0.5;
    this.dy = (Math.random()-0.5)*0.5;
    this.alpha = 0.25 + Math.random()*0.5;
  }
  draw(){ ctx.beginPath(); ctx.arc(this.x,this.y,this.r,0,Math.PI*2); ctx.fillStyle = `rgba(255,255,255,${this.alpha})`; ctx.fill(); }
  move(){ this.x += this.dx; this.y += this.dy; if(this.x<-10||this.x>canvas.width+10||this.y<-10||this.y>canvas.height+10) this.reset(); }
}
const particles = Array.from({length:90},()=>new Particle());
function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); particles.forEach(p=>{p.move();p.draw();}); requestAnimationFrame(loop); }
loop();

// Skills management
let skills = [];
function addSkill() {
  const skillInput = document.getElementById('skillInput');
  const skill = skillInput.value.trim();
  if (skill && !skills.includes(skill)) {
    skills.push(skill);
    updateSkillsDisplay();
    skillInput.value = '';
  }
}

function removeSkill(skill) {
  skills = skills.filter(s => s !== skill);
  updateSkillsDisplay();
}

function updateSkillsDisplay() {
  const container = document.getElementById('skillsContainer');
  const hidden = document.getElementById('skillsHidden');
  container.innerHTML = skills.map(skill => `
    <span class="skill-tag">
      ${skill}
      <span class="remove" onclick="removeSkill('${skill}')">×</span>
    </span>
  `).join(' ');
  hidden.value = JSON.stringify(skills);
}

// Toast notification
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = `toast ${type}`;
  toast.style.display = 'block';
  setTimeout(() => toast.style.display = 'none', 3000);
}

// Load mentors
async function loadMentors() {
  const grid = document.getElementById('mentorGrid');
  try {
    const res = await fetch('api/mentors/get_mentors.php');
    const data = await res.json();
    
    if (!data.success || !data.mentors.length) {
      grid.innerHTML = `
        <div class="card">
          <div class="icon">🕒</div>
          <h3>No Mentors Available</h3>
          <p class="meta">We're currently onboarding mentors. Please check back soon.</p>
        </div>
      `;
      return;
    }

    grid.innerHTML = data.mentors.map(mentor => `
      <div class="card">
        ${<?= $is_admin ? 'true' : 'false' ?> ? `
          <div class="admin-actions">
            <button class="admin-btn" onclick="deleteMentor(${mentor.id})" title="Delete Mentor">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        ` : ''}
        <div class="icon">👨‍🏫</div>
        <h3 style="margin:6px 0">${mentor.name}</h3>
        <div class="meta">Branch: <b>${mentor.branch}</b> • ⭐ ${mentor.rating || '4.5'}</div>
        <p class="meta" style="margin:8px 0 0">${mentor.headline || 'Experienced Mentor'}</p>
        <p class="meta" style="margin:6px 0 10px">
          Skills: ${mentor.skills ? (Array.isArray(mentor.skills) ? mentor.skills.join(', ') : mentor.skills) : 'Not specified'}
        </p>
        ${mentor.bio ? `<p class="meta" style="margin:6px 0 10px">${mentor.bio}</p>` : ''}
        <span class="badge">Active Mentor</span>
        <div>
          <button class="btn" onclick="showSlots(${mentor.id})">Book Session</button>
          <button class="btn btn-secondary" onclick="openChat(${mentor.id}, '${mentor.name.replace(/'/g, "\\'")}')">Chat Now</button>
        </div>
      </div>
    `).join('');
    
    // Update mentor select for admin
    if (<?= $is_admin ? 'true' : 'false' ?>) {
      updateMentorSelect(data.mentors);
    }
    
  } catch (e) {
    console.error('Error loading mentors:', e);
    grid.innerHTML = `
      <div class="card">
        <div class="icon">❌</div>
        <h3>Error Loading Mentors</h3>
        <p class="meta">Please try refreshing the page.</p>
      </div>
    `;
  }
}

// Update mentor select for admin
function updateMentorSelect(mentors) {
  const select = document.getElementById('mentorSelect');
  select.innerHTML = '<option value="">Select Mentor</option>' + 
    mentors.map(mentor => `<option value="${mentor.id}">${mentor.name} (${mentor.branch})</option>`).join('');
}

// Admin functions
async function deleteMentor(mentorId) {
  if (!confirm('Are you sure you want to delete this mentor?')) return;
  
  try {
    const formData = new FormData();
    formData.append('action', 'delete_mentor');
    formData.append('mentor_id', mentorId);
    
    const res = await fetch('', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (data.success) {
      showToast('Mentor deleted successfully');
      loadMentors();
    } else {
      showToast(data.message, 'error');
    }
  } catch (e) {
    showToast('Error deleting mentor', 'error');
  }
}

// Form handlers
document.getElementById('addMentorForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  formData.append('action', 'add_mentor');
  formData.append('skills', JSON.stringify(skills));
  
  try {
    const res = await fetch('', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (data.success) {
      showToast('Mentor added successfully');
      e.target.reset();
      skills = [];
      updateSkillsDisplay();
      loadMentors();
    } else {
      showToast(data.message, 'error');
    }
  } catch (e) {
    showToast('Error adding mentor', 'error');
  }
});

document.getElementById('addSlotForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  formData.append('action', 'add_slot');
  
  try {
    const res = await fetch('', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (data.success) {
      showToast('Time slot added successfully');
      e.target.reset();
    } else {
      showToast(data.message, 'error');
    }
  } catch (e) {
    showToast('Error adding time slot', 'error');
  }
});

// Chat functionality
let currentMentorId = null;
let chatInterval = null;

function openChat(mentorId, mentorName) {
  currentMentorId = mentorId;
  document.getElementById('chatWithName').textContent = "Chat with " + mentorName;
  document.getElementById('chatBox').style.display = 'flex';
  loadMessages();
  chatInterval = setInterval(loadMessages, 3000);
}

function closeChat() {
  document.getElementById('chatBox').style.display = 'none';
  clearInterval(chatInterval);
}

async function loadMessages() {
  try {
    const res = await fetch(`api/chat/fetch_messages.php?partner_id=${currentMentorId}`);
    const messages = await res.json();
    const box = document.getElementById('chatMessages');
    box.innerHTML = messages.map(m => `
      <div class="chat-message ${m.sender_id == <?= $_SESSION['user_id'] ?? 0 ?> ? 'sent' : 'received'}">${m.message}</div>
    `).join('');
    box.scrollTop = box.scrollHeight;
  } catch (e) {
    console.error('Error loading messages:', e);
  }
}

async function sendMessage() {
  const msg = document.getElementById('chatMessage').value.trim();
  if (!msg) return;
  
  try {
    const fd = new FormData();
    fd.append('receiver_id', currentMentorId);
    fd.append('message', msg);
    await fetch('api/chat/send_message.php', { method: 'POST', body: fd });
    document.getElementById('chatMessage').value = '';
    loadMessages();
  } catch (e) {
    console.error('Error sending message:', e);
  }
}

// Slot booking functionality
const modalBg = document.getElementById('modalBg');
const slotList = document.getElementById('slotList');

function openModal(){ modalBg.style.display='flex'; }
function closeModal(){ modalBg.style.display='none'; slotList.innerHTML=''; }
modalBg.addEventListener('click', e => { if(e.target===modalBg) closeModal(); });

async function showSlots(mentorId){
  slotList.innerHTML = `<div class="meta">Loading available slots…</div>`;
  openModal();
  try{
    const r = await fetch('api/mentors/get_slots.php?mentor_id='+mentorId);
    const data = await r.json();
    
    if(!data.success || !data.slots.length){ 
      slotList.innerHTML = `<div class="meta">No available slots at the moment.</div>`; 
      return; 
    }
    
    slotList.innerHTML = data.slots.map(slot => `
      <div class="slot">
        <div class="time">${new Date(slot.start_at.replace(' ','T')).toLocaleString()} (${slot.duration_minutes} min)</div>
        <button class="btn" onclick="bookSlot(${slot.id})">Book Now</button>
      </div>
    `).join('');
  } catch(e) { 
    console.error('Error loading slots:', e);
    slotList.innerHTML = `<div class="meta">Failed to load available slots.</div>`; 
  }
}

async function bookSlot(slotId){
  try{
    const fd = new FormData(); 
    fd.append('slot_id', slotId);
    const r = await fetch('api/mentors/book_slot.php', { method: 'POST', body: fd });
    const data = await r.json();
    
    if(data.success){ 
      slotList.innerHTML = `<div class="slot"><div class="time">✅ Booking confirmed!</div></div>`; 
      setTimeout(closeModal, 1500); 
    } else { 
      alert('Booking failed: ' + (data.message || 'Please try again')); 
    }
  } catch(e) { 
    alert('Booking failed. Please try again.'); 
  }
}

// Initialize
loadMentors();
</script>
</body>
</html>