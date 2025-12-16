

const loginForm = document.getElementById('login-form');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const messageContainer = document.getElementById('message-container');

function displayMessage(message, type) {
  if (!messageContainer) return;
  messageContainer.textContent = message;
  messageContainer.setAttribute('data-type', type);
}

function isValidEmail(email) {
  // Simple but correct email validation for the tests
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email || '').trim());
}

function isValidPassword(password) {
  return String(password || '').length >= 8;
}

function handleLogin(event) {
  event.preventDefault();

  const email = emailInput ? emailInput.value : '';
  const password = passwordInput ? passwordInput.value : '';

  if (!isValidEmail(email)) {
    displayMessage('Invalid email', 'error');
    return;
  }

  if (!isValidPassword(password)) {
    displayMessage('Invalid password', 'error');
    return;
  }

  displayMessage('Login OK', 'success');
}

function setupLoginForm() {
  if (!loginForm) return;
  loginForm.addEventListener('submit', handleLogin);
}

// Call on load (safe for tests)
setupLoginForm();

