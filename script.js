const registerButton = document.getElementById('register')
const loginButton = document.getElementById('login')
const container = document.getElementById('container')

// Prefer navigation to dedicated pages, fall back to flip if present
const path = (window.location && window.location.pathname) ? window.location.pathname.toLowerCase() : ''
const isLogin = /(^|\/)login\.php$/.test(path)
const isRegister = /(^|\/)register\.php$/.test(path)

if (registerButton) {
  registerButton.onclick = function(){
    if (isLogin) {
      window.location.href = 'register.php'
    } else if (container) {
      container.className = 'active'
    }
    return false
  }
}
if (loginButton) {
  loginButton.onclick = function(){
    if (isRegister) {
      window.location.href = 'login.php'
    } else if (container) {
      container.className = 'close'
    }
    return false
  }
}

// Theme toggle for auth pages (shared)
(function(){
  const toggle = document.getElementById('themeToggle');
  const root = document.documentElement;
  const saved = localStorage.getItem('theme');
  if (saved === 'dark') root.classList.add('theme-dark');
  if (toggle) {
    toggle.addEventListener('click', function(){
      const isDark = root.classList.toggle('theme-dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
  }
})();
