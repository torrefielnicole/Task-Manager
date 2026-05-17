@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;500;700&family=DM+Mono:wght@400;500&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Syne', sans-serif;
  background: #f5f5f3;
  display: flex;
  min-height: 100vh;
}

.page-content {
  flex: 1;
  padding: 32px;
  background: #ffffff;
  min-height: 100vh;
}

/* ── Shared components ── */
.progress-bar {
  height: 4px;
  background: rgba(0,0,0,0.08);
  border-radius: 4px;
  overflow: hidden;
}
.progress-fill { height: 100%; border-radius: 4px; }

/* ── index.php (Dashboard) ── */
.dash-title { font-size: 24px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 4px; color: #1a1a18; }
.dash-sub { font-size: 13px; color: #5f5e5a; margin-bottom: 24px; }
.stats-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 28px; }
.stat-card { background: #f5f5f3; border-radius: 8px; padding: 16px; }
.stat-card .label { font-size: 11px; color: #888780; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.stat-card .value { font-size: 30px; font-weight: 700; color: #1a1a18; line-height: 1; }
.stat-card .delta { font-size: 11px; color: #1D9E75; margin-top: 4px; }
.activity-list { border-top: 0.5px solid rgba(0,0,0,0.10); padding-top: 20px; }
.activity-list h3 { font-size: 12px; font-weight: 500; color: #888780; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; }
.act-item { display: flex; align-items: center; gap: 12px; padding: 9px 0; border-bottom: 0.5px solid rgba(0,0,0,0.08); }
.act-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.act-text { font-size: 13px; color: #5f5e5a; flex: 1; }
.act-time { font-size: 11px; color: #888780; font-family: 'DM Mono', monospace; }

/* ── academic.php ── */
.academic-header { background: #E6F1FB; border-radius: 8px; padding: 18px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; }
.academic-header .icon-big { font-size: 32px; }
.academic-header h1 { font-size: 20px; font-weight: 700; color: #0C447C; }
.academic-header p { font-size: 12px; color: #185FA5; margin-top: 2px; }
.course-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
.course-card { background: #f5f5f3; border-radius: 8px; padding: 14px; border-left: 3px solid; }
.course-card h4 { font-size: 13px; font-weight: 500; color: #1a1a18; margin-bottom: 4px; }
.course-card .due { font-size: 11px; color: #888780; margin-bottom: 10px; }
.upcoming { background: #f5f5f3; border-radius: 8px; padding: 14px 16px; }
.upcoming h3 { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.07em; color: #888780; margin-bottom: 10px; }
.up-item { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; font-size: 13px; border-bottom: 0.5px solid rgba(0,0,0,0.08); }
.up-item:last-child { border-bottom: none; }
.up-badge { font-size: 10px; padding: 2px 10px; border-radius: 20px; font-weight: 500; }

/* ── personal.php ── */
.personal-title { font-size: 22px; font-weight: 700; letter-spacing: -0.4px; color: #1a1a18; margin-bottom: 2px; }
.personal-tagline { font-size: 12px; color: #888780; margin-bottom: 24px; }
.habit-item { display: flex; align-items: center; gap: 14px; padding: 11px 0; border-bottom: 0.5px solid rgba(0,0,0,0.08); }
.habit-check { width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid rgba(0,0,0,0.22); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.habit-check.done { background: #1D9E75; border-color: #1D9E75; color: #fff; font-size: 12px; }
.habit-name { font-size: 13px; color: #1a1a18; flex: 1; }
.habit-streak { font-size: 11px; color: #888780; font-family: 'DM Mono', monospace; }
.mood-label-title { font-size: 11px; color: #888780; text-transform: uppercase; letter-spacing: 0.07em; margin: 20px 0 10px; }
.mood-row { display: flex; gap: 10px; }
.mood-btn { flex: 1; padding: 12px 4px; border-radius: 8px; border: 0.5px solid rgba(0,0,0,0.12); background: #f5f5f3; font-size: 22px; cursor: pointer; text-align: center; }
.mood-btn.sel { border-color: #1D9E75; background: #E1F5EE; }

/* ── project.php ── */
.proj-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.proj-title { font-size: 20px; font-weight: 700; color: #1a1a18; }
.proj-tag { font-size: 10px; padding: 4px 12px; background: #FAEEDA; color: #633806; border-radius: 20px; font-weight: 500; }
.kanban { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 16px; }
.k-col h3 { font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; color: #888780; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 0.5px solid rgba(0,0,0,0.08); }
.k-card { background: #f5f5f3; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; border-left: 2px solid; }
.k-card p { font-size: 12px; color: #1a1a18; line-height: 1.4; }
.k-tag { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 3px; margin-top: 6px; font-weight: 500; }
.proj-progress { background: #f5f5f3; border-radius: 8px; padding: 14px 16px; }
.pp-row { display: flex; justify-content: space-between; font-size: 13px; color: #5f5e5a; margin-bottom: 8px; }
.pp-row strong { color: #1a1a18; }

/* ── system.php ── */
.sys-title { font-size: 16px; font-weight: 500; color: #1a1a18; font-family: 'DM Mono', monospace; margin-bottom: 20px; }
.sys-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.sys-block { background: #f5f5f3; border-radius: 8px; padding: 14px 16px; border: 0.5px solid rgba(0,0,0,0.10); }
.sys-block .sk { font-size: 10px; font-family: 'DM Mono', monospace; color: #888780; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
.sys-block .sv { font-size: 15px; font-family: 'DM Mono', monospace; font-weight: 500; color: #1a1a18; }
.sys-block .sbar { height: 3px; background: rgba(0,0,0,0.08); border-radius: 3px; margin-top: 10px; overflow: hidden; }
.sys-block .sbar-fill { height: 100%; border-radius: 3px; }
.log-box { background: #f5f5f3; border-radius: 8px; padding: 14px 16px; font-family: 'DM Mono', monospace; font-size: 12px; line-height: 2; border: 0.5px solid rgba(0,0,0,0.10); }
.log-line { display: flex; gap: 12px; }
.log-t { color: #888780; }
.log-ok { color: #1D9E75; }
.log-warn { color: #BA7517; }
.log-err { color: #A32D2D; }
.log-msg { color: #5f5e5a; }

/* ── developer.php ── */
.dev-title { font-size: 20px; font-weight: 700; color: #1a1a18; letter-spacing: -0.4px; margin-bottom: 4px; }
.dev-sub { font-size: 12px; color: #888780; font-family: 'DM Mono', monospace; margin-bottom: 22px; }
.api-row { display: flex; align-items: center; gap: 12px; padding: 9px 0; border-bottom: 0.5px solid rgba(0,0,0,0.08); }
.method { font-size: 10px; font-weight: 500; font-family: 'DM Mono', monospace; padding: 3px 8px; border-radius: 3px; min-width: 44px; text-align: center; }
.m-get  { background: #EAF3DE; color: #3B6D11; }
.m-post { background: #E6F1FB; color: #185FA5; }
.m-del  { background: #FCEBEB; color: #A32D2D; }
.m-put  { background: #FAEEDA; color: #854F0B; }
.api-path { font-size: 12px; font-family: 'DM Mono', monospace; color: #1a1a18; flex: 1; }
.api-status { font-size: 11px; color: #888780; }
.code-snip { background: #f5f5f3; border-radius: 8px; padding: 14px 16px; font-family: 'DM Mono', monospace; font-size: 12px; line-height: 1.8; border: 0.5px solid rgba(0,0,0,0.10); color: #5f5e5a; margin-top: 20px; }

/* ── logout.php ── */
.logout-wrap { display: flex; align-items: center; justify-content: center; min-height: 80vh; }
.logout-card { text-align: center; max-width: 300px; }
.logout-icon { width: 56px; height: 56px; border-radius: 50%; background: #FCEBEB; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 26px; }
.logout-card h2 { font-size: 18px; font-weight: 700; color: #1a1a18; margin-bottom: 8px; }
.logout-card p { font-size: 13px; color: #5f5e5a; margin-bottom: 24px; line-height: 1.6; }
.logout-btns { display: flex; gap: 10px; justify-content: center; }
.btn { padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 0.5px solid rgba(0,0,0,0.22); background: #f5f5f3; color: #1a1a18; font-family: 'Syne', sans-serif; text-decoration: none; display: inline-block; }
.btn-danger { background: #FCEBEB; border-color: #F09595; color: #A32D2D; }