function toggleActive(event, element) {
  const links = document.querySelectorAll('.nav a');
  links.forEach(link => link.classList.remove('active'));
  element.classList.add('active');

  if (element.getAttribute('href') === '#') {
      event.preventDefault();
  }
}

// DATE AND TIME JS
function updateDateTime() {
  const now = new Date();
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' };
  const dateTimeString = now.toLocaleDateString('en-US', options);
  document.getElementById('datetime').textContent = dateTimeString;
}

// DATE TIME DAW
updateDateTime();
setInterval(updateDateTime, 1000);

function handleButtonClick() {
  alert('Button clicked!');
}

// Modal JS
var modal = document.getElementById("logoutModal");
var logoutLink = document.getElementById("logout-link");
var yesBtn = document.getElementById("yesBtn");
var noBtn = document.getElementById("noBtn");

logoutLink.onclick = function(event) {
  event.preventDefault();
  modal.style.display = "block";
}

yesBtn.onclick = function() {
  window.location.href = "logout.php";
}

noBtn.onclick = function() {
  modal.style.display = "none";
}

window.onclick = function(event) {
  if (event.target == modal) {
      modal.style.display = "none";
  }
}

// Function to log out the user after inactivity

let logoutTimer;
function resetLogoutTimer() {
    clearTimeout(logoutTimer);
    logoutTimer = setTimeout(function() {
        logInactivityAndLogout();
    }, 10 * 60 * 1000); // 10 minutes
}

function logInactivityAndLogout() {
    console.log("User inactive for 5 seconds. Logging out...");
    window.location.href = "logout.php?inactivity=true";
}

document.onmousemove = resetLogoutTimer;
document.onkeypress = resetLogoutTimer;
resetLogoutTimer();
