// ============================================================
//  CloudJobs - Frontend JavaScript
//  File: public_html/js/app.js
// ============================================================

'use strict';

// ---- Toggle Save Job (AJAX) ----
async function toggleSave(btn, jobId) {
  try {
    const res = await fetch('/api/save-job.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ job_id: jobId, csrf: getCsrfToken() })
    });
    const data = await res.json();
    if (data.saved) {
      btn.textContent = '♥';
      btn.classList.add('saved');
      showToast('Job saved!', 'success');
    } else {
      btn.textContent = '♡';
      btn.classList.remove('saved');
      showToast('Job removed from saved.', 'info');
    }
  } catch (err) {
    showToast('Please sign in to save jobs.', 'error');
  }
}

// ---- Toast Notifications ----
function showToast(message, type = 'info') {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#2563EB'};
    color: white; padding: 12px 20px; border-radius: 8px;
    font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,.15);
    animation: slideIn .2s ease-out;
  `;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ---- Get CSRF Token from meta tag ----
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

// ---- Character Counter for Textareas ----
document.querySelectorAll('textarea[maxlength]').forEach(ta => {
  const counter = document.createElement('small');
  counter.className = 'char-counter';
  counter.style.cssText = 'display:block; text-align:right; color:#64748B; margin-top:4px; font-size:12px;';
  ta.insertAdjacentElement('afterend', counter);
  const update = () => counter.textContent = `${ta.value.length} / ${ta.maxLength}`;
  ta.addEventListener('input', update);
  update();
});

// ---- Skills Tag Input ----
const skillsInput = document.getElementById('skills');
if (skillsInput) {
  const wrapper = document.createElement('div');
  wrapper.className = 'skills-tags';
  wrapper.style.cssText = 'display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;';
  skillsInput.insertAdjacentElement('afterend', wrapper);

  function renderTags() {
    wrapper.innerHTML = '';
    (skillsInput.value.split(',').map(s => s.trim()).filter(Boolean)).forEach(skill => {
      const tag = document.createElement('span');
      tag.style.cssText = 'background:#EFF6FF; color:#1E40AF; border:1px solid #BFDBFE; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:500;';
      tag.textContent = skill;
      wrapper.appendChild(tag);
    });
  }
  skillsInput.addEventListener('input', renderTags);
  renderTags();
}

// ---- Smooth scroll for anchor links ----
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const target = document.querySelector(link.getAttribute('href'));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

// ---- Add slide-in animation keyframe ----
const style = document.createElement('style');
style.textContent = `@keyframes slideIn { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }`;
document.head.appendChild(style);
