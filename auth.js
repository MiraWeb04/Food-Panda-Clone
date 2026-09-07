document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  const feedback = document.getElementById('login-feedback');

  if (!form) return;

  form.addEventListener('submit', (event) => {
    const role = document.getElementById('role-select').value;
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!role || !username || !password) {
      event.preventDefault();
      feedback.textContent = 'Please fill in all fields.';
      return;
    }

    feedback.textContent = 'Signing in...';
    if (role === 'admin') {
      form.action = 'admin/login.php';
    } else if (role === 'delivery') {
      form.action = 'rider/login.php';
    } else {
      form.action = 'staff/login.php';
    }
  });
});
