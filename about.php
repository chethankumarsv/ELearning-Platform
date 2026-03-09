<?php
// about.php - About / About Us page
session_start();
require_once __DIR__ . '/includes/config.php';

$total_courses = isset($total_courses) ? $total_courses : null;
$total_learners = isset($total_learners) ? $total_learners : null;
$avg_rating = isset($avg_rating) ? $avg_rating : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>About — E-Learning Platform</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg-dark-900: #0a0f1a;
  --bg-dark-800: #131c2b;
  --text-dark: #f3f6fa;
  --muted-dark: #9caec5;
  --accent-1: #ff6b6b;
  --accent-2: #feca57;
  --card-radius: 18px;
  --max-width: 1180px;
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:'Poppins',system-ui,Segoe UI,Roboto,Arial;
  color:var(--text-dark);
  background: radial-gradient(circle at top left, #1b2838, #0a0f1a);
  -webkit-font-smoothing:antialiased;
  padding-bottom:80px;
}
a{color:inherit;text-decoration:none;transition:0.3s ease}
a:hover{color:var(--accent-2)}

.container{max-width:var(--max-width);margin:0 auto;padding:28px}

/* NAV */
header{position:sticky;top:16px;z-index:100}
.nav{
  display:flex;align-items:center;justify-content:space-between;gap:14px;
  padding:16px 22px;border-radius:14px;
  background: rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  box-shadow:0 10px 30px rgba(0,0,0,0.5);
  backdrop-filter: blur(8px);
}
.brand{display:flex;align-items:center;gap:12px;font-weight:800;font-size:20px;color:var(--accent-2)}
nav ul{list-style:none;display:flex;gap:30px;margin:0;padding:0;align-items:center}
nav a{padding:8px 12px;border-radius:10px;font-weight:600;color:var(--text-dark);opacity:0.95}
nav a:hover{background:rgba(255,255,255,0.05)}
.nav-actions{display:flex;align-items:center;gap:12px}

.btn{
  background: linear-gradient(135deg,var(--accent-1),var(--accent-2));
  color:white;padding:10px 18px;border-radius:14px;border:none;
  font-weight:700;cursor:pointer;transition:0.3s ease
}
.btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.4)}
.ghost{
  background:transparent;padding:9px 14px;border-radius:14px;
  border:1px solid rgba(255,255,255,0.2);color:var(--text-dark);font-weight:600;transition:0.3s ease
}
.ghost:hover{background:rgba(255,255,255,0.08);border-color:var(--accent-2)}

/* Dropdown */
.dropdown { position: relative; display: inline-block; }
.dropdown-toggle { cursor: pointer; display:flex; align-items:center; gap:8px; }
.dropdown-arrow { transition: transform 0.25s ease; display:inline-block; }
.dropdown-open .dropdown-arrow { transform: rotate(180deg); }
.dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 100%;
  margin-top: 6px;
  min-width: 180px;
  background: #1f2a3c;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.55);
  z-index: 200;
  padding: 8px 0;
}
.dropdown-menu a { display:block;padding:12px 16px;color:var(--text-dark);font-weight:600 }
.dropdown-menu a:hover { background: rgba(255,255,255,0.08); color:var(--accent-2); border-radius:8px }

/* Page head */
.page-head{
  margin-top:22px;padding:40px;border-radius:var(--card-radius);
  background:linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
  border:1px solid rgba(255,255,255,0.05);
  box-shadow:0 10px 40px rgba(0,0,0,0.5);
}
.page-head h1{margin:0;font-size:36px;color:var(--accent-2)}
.page-head p{margin:8px 0 0;color:var(--muted-dark);font-size:15px}

/* Mission / Stats */
.grid{display:grid;grid-template-columns:1fr 340px;gap:22px;margin-top:20px}
.card{
  background:rgba(255,255,255,0.03);
  padding:22px;border-radius:var(--card-radius);
  border:1px solid rgba(255,255,255,0.05);
  box-shadow:0 6px 20px rgba(0,0,0,0.4);
}
.stats{display:flex;flex-direction:column;gap:14px}
.stat-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 14px;border-radius:12px;
  background:linear-gradient(90deg, rgba(255,255,255,0.04), transparent);
}
.stat-item h3{margin:0;color:var(--accent-2)}
.stat-item p{margin:0;color:var(--muted-dark)}

/* Team */
.team-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:20px}
.team-card{
  padding:16px;border-radius:14px;
  background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
  border:1px solid rgba(255,255,255,0.05);
  text-align:left;transition:0.3s ease
}
.team-card:hover{transform:translateY(-4px);box-shadow:0 10px 24px rgba(0,0,0,0.5)}
.avatar{
  width:60px;height:60px;border-radius:12px;
  display:inline-flex;align-items:center;justify-content:center;
  font-weight:800;color:white;font-size:18px
}

/* Timeline */
.timeline{margin-top:20px;display:flex;flex-direction:column;gap:14px;position:relative}
.timeline:before{
  content:'';position:absolute;left:6px;top:0;bottom:0;width:2px;background:rgba(255,255,255,0.1);
}
.timeline-item{display:flex;gap:14px;align-items:flex-start;position:relative}
.timeline-dot{min-width:14px;height:14px;border-radius:99px;background:var(--accent-1);margin-top:6px;box-shadow:0 0 10px var(--accent-1)}

/* CTA */
.cta{
  margin-top:26px;padding:28px;border-radius:14px;
  background:linear-gradient(135deg, rgba(255,107,107,0.15), rgba(254,202,87,0.08));
  display:flex;align-items:center;justify-content:space-between;gap:14px;
  box-shadow:0 8px 24px rgba(0,0,0,0.5);
}
.cta .lead{font-weight:700;font-size:18px;color:var(--accent-2)}
.cta .sub{color:var(--muted-dark)}

/* Footer */
footer{
  margin-top:22px;padding:20px;border-radius:12px;
  border:1px solid rgba(255,255,255,0.05);
  background:rgba(255,255,255,0.02);
  text-align:center;color:var(--muted-dark);
  font-size:14px
}

/* Responsive */
@media (max-width:980px){
  .grid{grid-template-columns:1fr}
  .team-grid{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:640px){
  nav ul{display:none}
  .team-grid{grid-template-columns:1fr}
  .page-head h1{font-size:26px}
}
</style>

</head>
<body>
  <div class="container">
    <!-- NAV -->
    <header>
      <div class="nav" role="navigation" aria-label="Main navigation">
        <div class="brand"><i aria-hidden="true">🎓</i><strong>E-Learn</strong></div>

        <nav aria-label="Top navigation">
          <ul>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
        </nav>

        <div class="nav-actions">
          <div class="dropdown" id="authDropdownWrap">
            <button class="btn dropdown-toggle" id="dropdownToggle" onclick="toggleDropdown(event)">
              Login / Signup <span class="dropdown-arrow" id="dropdownArrow">▾</span>
            </button>
            <div class="dropdown-menu" id="authDropdown">
              <a href="auth.php?type=login">Login</a>
              <a href="auth.php?type=signup">Signup</a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- PAGE HEAD -->
    <section class="page-head" aria-label="About header">
      <h1>About E-Learning Platform</h1>
      <p class="muted">Practical, project-based learning for careers in web, data and AI — built for students and professionals alike.</p>
    </section>

    <!-- MISSION + STATS -->
    <div class="grid" role="region" aria-label="Mission and stats">
      <div>
        <div class="card">
          <h3>Our mission</h3>
          <p class="muted">We help learners gain job-ready skills through short, hands-on courses, mentor feedback and industry-aligned projects. We focus on retention, real assessments and shareable certificates.</p>

          <h4 style="margin-top:16px">What we believe</h4>
          <ul class="muted" style="margin:8px 0 0 18px">
            <li>Learning-by-doing beats passive watching.</li>
            <li>Mentorship accelerates growth.</li>
            <li>Accessible pricing and clear career outcomes matter.</li>
          </ul>

          <div style="height:10px"></div>

          <h4>How we work</h4>
          <ol class="muted" style="margin:8px 0 0 18px">
            <li>Project-first courses with weekly assessments.</li>
            <li>Peer reviews and mentor office hours.</li>
            <li>Live hiring drives and portfolio showcases.</li>
          </ol>
        </div>

        <!-- TEAM -->
        <h3 style="margin-top:18px">Core Team</h3>
        <div class="team-grid" role="list">
          <div class="team-card" role="listitem">
            <div style="display:flex;gap:12px;align-items:center">
              <div class="avatar" style="background:linear-gradient(90deg,var(--accent-1),var(--accent-2))">CK</div>
              <div>
                <strong>Chethan K</strong><div class="muted" style="font-size:13px">Founder & Head of Product</div>
              </div>
            </div>
            <p class="muted" style="margin-top:10px">Leads product strategy and course partnerships.</p>
          </div>

          <div class="team-card" role="listitem">
            <div style="display:flex;gap:12px;align-items:center">
              <div class="avatar" style="background:linear-gradient(90deg,var(--accent-2),var(--accent-1))">SR</div>
              <div>
                <strong>S. Ramesh</strong><div class="muted" style="font-size:13px">Lead Instructor — Data</div>
              </div>
            </div>
            <p class="muted" style="margin-top:10px">Designs practical data science projects and mentoring.</p>
          </div>

          <div class="team-card" role="listitem">
            <div style="display:flex;gap:12px;align-items:center">
              <div class="avatar" style="background:linear-gradient(90deg,#8be4b3,var(--accent-2))">AK</div>
              <div>
                <strong>A. Kumar</strong><div class="muted" style="font-size:13px">Lead Instructor — Web</div>
              </div>
            </div>
            <p class="muted" style="margin-top:10px">Runs web bootcamps and code reviews.</p>
          </div>
        </div>

      </div>

      <!-- right column: stats + quick links -->
      <aside>
        <div class="card">
          <h4>Platform snapshot</h4>
          <div class="stats" style="margin-top:10px">
            <div class="stat-item"><div><h3><?php echo isset($total_courses) ? $total_courses : '120+'; ?></h3><p class="muted">Courses</p></div></div>
            <div class="stat-item"><div><h3><?php echo isset($total_learners) ? $total_learners : '15k+'; ?></h3><p class="muted">Learners</p></div></div>
            <div class="stat-item"><div><h3><?php echo isset($avg_rating) ? $avg_rating : '4.8'; ?></h3><p class="muted">Avg rating</p></div></div>
          </div>

          <div style="height:12px"></div>
          <a class="btn" href="courses.php" style="display:inline-block">Browse courses</a>
          <a class="ghost" href="auth.php?type=signup" style="display:inline-block;margin-left:8px">Get started</a>
        </div>

        <div class="card" style="margin-top:14px">
          <h4>Quick links</h4>
          <ul class="muted" style="margin:10px 0 0 16px">
            <li><a href="projects.php">Student projects</a></li>
            <li><a href="leaderboard.php">Leaderboard</a></li>
            <li><a href="events.php">Upcoming events</a></li>
          </ul>
        </div>
      </aside>
    </div>

    <!-- TIMELINE -->
    <section style="margin-top:20px">
      <h3>Our story</h3>
      <div class="timeline">
        <div class="timeline-item">
          <div class="timeline-dot" aria-hidden="true"></div>
          <div>
            <strong>2022 — Founded</strong>
            <div class="muted">Started as a community bootcamp teaching web fundamentals.</div>
          </div>
        </div>

        <div class="timeline-item">
          <div class="timeline-dot" aria-hidden="true"></div>
          <div>
            <strong>2023 — Courses launched</strong>
            <div class="muted">Launched full-stack & data courses with mentor support.</div>
          </div>
        </div>

        <div class="timeline-item">
          <div class="timeline-dot" aria-hidden="true"></div>
          <div>
            <strong>2024 — Partnerships</strong>
            <div class="muted">Partnered with local employers to host hiring drives.</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta" aria-label="Call to action">
      <div>
        <div class="lead">Ready to build something real?</div>
        <div class="sub muted">Start with a free course and level up with hands-on projects.</div>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn" href="auth.php?type=signup">Create account</a>
        <a class="ghost" href="courses.php">Explore courses</a>
      </div>
    </section>

    <div style="height:18px"></div>

    <!-- FOOTER -->
    <footer style="margin-top:18px;padding:18px;border-radius:12px;border:1px solid rgba(255,255,255,0.02);background:linear-gradient(180deg, rgba(255,255,255,0.005), transparent);text-align:center;color:var(--muted-dark)">
      <div style="display:flex;align-items:center;justify-content:center;gap:12px;font-weight:600">
        <span>© <?= date('Y') ?> E-Learning Platform</span>
      </div>
    </footer>
  </div>

  <script>
    function toggleDropdown(e) {
      e.stopPropagation();
      const wrap = document.getElementById('authDropdownWrap');
      const menu = document.getElementById('authDropdown');
      const isOpen = menu.style.display === 'block';
      if (isOpen) {
        menu.style.display = 'none';
        wrap.classList.remove('dropdown-open');
      } else {
        menu.style.display = 'block';
        wrap.classList.add('dropdown-open');
      }
    }

    window.addEventListener('click', function(event) {
      const wrap = document.getElementById('authDropdownWrap');
      const menu = document.getElementById('authDropdown');
      if (!wrap.contains(event.target)) {
        if (menu && menu.style.display === 'block') {
          menu.style.display = 'none';
          wrap.classList.remove('dropdown-open');
        }
      }
    });

    // Close on escape
    window.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const wrap = document.getElementById('authDropdownWrap');
        const menu = document.getElementById('authDropdown');
        if (menu && menu.style.display === 'block') {
          menu.style.display = 'none';
          wrap.classList.remove('dropdown-open');
        }
      }
    });
  </script>
</body>
</html>
